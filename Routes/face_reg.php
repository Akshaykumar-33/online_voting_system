<?php
session_start();
require("../admin/connect.php"); // Your database connection

$message = "";
$phone_number = isset($_GET['number']) ? htmlspecialchars($_GET['number']) : '';
$user_role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // Ensure JSON response for AJAX

    $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
    $role = isset($_POST['role']) ? $_POST['role'] : null;
    $imageData = isset($_POST['image']) ? $_POST['image'] : null;

    if (!$phone || !$role || !$imageData) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit;
    }

    // Image processing
    $img = str_replace('data:image/png;base64,', '', $imageData);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    
    $uploadDir = '../uploads/face_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = $phone . '.png';
    $filePath = $uploadDir . $fileName;
    $dbImagePath = 'uploads/face_images/' . $fileName; // Path to store in DB

    if (file_put_contents($filePath, $data)) {
        try {
            if ($role == 1) { // Assuming role 1 is for 'userdata'
                $stmt = $db->prepare("UPDATE userdata SET photo = :image_path WHERE mobile = :mobile");
                 // If your userdata table uses a different column name for face image, change 'face_image_path'
            } else { // Assuming other roles are for 'candidate'
                $stmt = $db->prepare("UPDATE candidate SET cand_img = :image_path WHERE mobile = :mobile");
            }

            if ($stmt) {
                $stmt->bindValue(':image_path', $dbImagePath, SQLITE3_TEXT);
                $stmt->bindValue(':mobile', $phone, SQLITE3_TEXT);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Face registered successfully! You can now proceed to login.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
                }
            } else {
                 echo json_encode(['success' => false, 'message' => 'Failed to prepare database statement. Error: ' . $db->lastErrorMsg()]);
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage()); // Log the detailed error
            echo json_encode(['success' => false, 'message' => 'An internal server error occurred. Please try again. DB Error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Face Registration</title>
    <link rel="stylesheet" href="../admin/css/admin_login.css"> <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../face_auth/face-api.min.js"></script> <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
        }
        #bodysection {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        #videoContainer {
            position: relative;
            width: 320px; /* Adjust as needed */
            height: 240px; /* Adjust as needed */
            margin: 0 auto 20px auto;
            border: 1px solid #ccc;
        }
        video, canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        canvas { /* For drawing detections, if needed */
            z-index: 10;
        }
        #captureButton {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #captureButton:hover {
            background-color: #0056b3;
        }
        #captureButton:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        #statusMessage {
            margin-top: 15px;
            font-size: 1em;
            color: #333;
        }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div id="bodysection">
        <h2>Register Your Face</h2>
        <p>Position your face clearly in the camera view.</p>
        <div id="videoContainer">
            <video id="video" width="320" height="240" autoplay muted></video>
            <canvas id="overlayCanvas"></canvas> </div>
        <form id="faceRegForm">
            <input type="hidden" name="phone" id="phone" value="<?php echo $phone_number; ?>" required>
            <input type="hidden" name="role" id="role" value="<?php echo $user_role; ?>" required>
            <button type="button" id="captureButton" disabled>Loading...</button>
        </form>
        <div id="statusMessage"></div>
        <canvas id="captureCanvas" style="display:none;"></canvas>
    </div>

    <script>
        const video = document.getElementById('video');
        const captureButton = document.getElementById('captureButton');
        const statusMessage = document.getElementById('statusMessage');
        const phoneInput = document.getElementById('phone');
        const roleInput = document.getElementById('role');
        const captureCanvas = document.getElementById('captureCanvas');
        const overlayCanvas = document.getElementById('overlayCanvas'); // For detection boxes
        const ctxOverlay = overlayCanvas.getContext('2d');

        let modelsLoaded = false;

        async function loadModels() {
            // Adjust the path to your models directory
            const MODEL_URL = '../face_auth/weights'; 
            statusMessage.textContent = 'Loading face detection models...';
            try {
                await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                // await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL); // Alternative smaller model
                // await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL); // If you want landmarks
                // await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL); // If you want recognition features
                modelsLoaded = true;
                statusMessage.textContent = 'Setting up camera...';
                console.log("Setup successful");
            } catch (error) {
                console.error("Error loading models: ", error);
                statusMessage.textContent = 'Error loading models. Please refresh.';
                statusMessage.className = 'error';
            }
        }

        async function startVideo() {
            if (!modelsLoaded) {
                statusMessage.textContent = 'Models not loaded yet.';
                captureButton.disabled = true;
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    video.play();
                    captureButton.disabled = false;
                    captureButton.textContent = 'Capture & Verify Face';
                    statusMessage.textContent = 'Camera ready. Click button to capture.';
                    // Set canvas dimensions based on video
                    overlayCanvas.width = video.videoWidth;
                    overlayCanvas.height = video.videoHeight;
                    detectFace(); // Start face detection loop
                };
            } catch (err) {
                console.error("Error accessing webcam: ", err);
                statusMessage.textContent = 'Error accessing webcam. Please allow camera access.';
                statusMessage.className = 'error';
                captureButton.disabled = true;
            }
        }
        
        async function detectFace() {
            if (video.paused || video.ended || !modelsLoaded) {
                return setTimeout(() => detectFace());
            }

            const detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }));
            // const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());


            ctxOverlay.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
            if (detections.length > 0) {
                // Draw detection boxes (optional)
                // faceapi.draw.drawDetections(overlayCanvas, faceapi.resizeResults(detections, { width: overlayCanvas.width, height: overlayCanvas.height }));
                // You could also draw landmarks if loaded: faceapi.draw.drawFaceLandmarks(overlayCanvas, resizedDetections);
                
                // For simplicity, just enable capture if any face is detected
                if (captureButton.disabled) { // Re-enable if it was disabled due to no face
                    captureButton.disabled = false;
                    captureButton.textContent = 'Capture & Verify Face';
                }
            } else {
                 if (!captureButton.disabled) {
                    // Optional: disable capture if no face is seen consistently
                    captureButton.disabled = true;
                    captureButton.textContent = 'Capture & Verify Face';
                 }
            }
            requestAnimationFrame(detectFace); // Loop
        }


        captureButton.addEventListener('click', async () => {
            if (!phoneInput.value || !roleInput.value) {
                alert("Error: Phone number or role is missing. Please go back and try again.");
                return;
            }

            statusMessage.textContent = 'Capturing...';
            captureButton.disabled = true;

            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            const context = captureCanvas.getContext('2d');
            context.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
            
            const imageDataURL = captureCanvas.toDataURL('image/png');

            // Verify face in the captured image before sending
            const imgElementForDetection = await faceapi.fetchImage(imageDataURL);
            const detections = await faceapi.detectSingleFace(imgElementForDetection, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.6 }));

            if (!detections) {
                statusMessage.textContent = 'No face detected in the capture. Please try again.';
                statusMessage.className = 'error';
                captureButton.disabled = false;
                captureButton.textContent = 'Capture & Verify Face';
                return;
            }

            statusMessage.textContent = 'Face detected. Uploading...';
            statusMessage.className = '';

            $.ajax({
                url: 'face_reg.php', // Submitting to itself
                type: 'POST',
                data: {
                    phone: phoneInput.value,
                    role: roleInput.value,
                    image: imageDataURL
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        statusMessage.textContent = response.message;
                        statusMessage.className = 'success';
                        // alert(response.message);
                        setTimeout(() => {
                            window.location.href = '../Routes/login.php';
                        }, 2000);
                    } else {
                        statusMessage.textContent = "Error: " + response.message;
                        statusMessage.className = 'error';
                        captureButton.disabled = false;
                        captureButton.textContent = 'Capture & Verify Face';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    statusMessage.textContent = 'An error occurred while sending data. Please check console.';
                    statusMessage.className = 'error';
                    captureButton.disabled = false;
                    captureButton.textContent = 'Capture & Verify Face';
                }
            });
        });

        // Initialize
        async function init() {
            await loadModels();
            await startVideo();
        }
        init();

    </script>
</body>
</html>