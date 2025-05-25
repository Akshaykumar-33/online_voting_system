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

    // Get stored face image path from database
    try {
        if ($role == 1) { // Assuming role 1 is for 'userdata'
            $stmt = $db->prepare("SELECT photo FROM userdata WHERE mobile = :mobile");
        } else { // Assuming other roles are for 'candidate'
            $stmt = $db->prepare("SELECT cand_img FROM candidate WHERE mobile = :mobile");
        }

        $stmt->bindValue(':mobile', $phone, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if (!$row || empty($row['photo'] ?? $row['cand_img'])) {
            echo json_encode(['success' => false, 'message' => 'No registered face found for this account.']);
            exit;
        }

        $storedImagePath = '../' . ($row['photo'] ?? $row['cand_img']);
        
        if (!file_exists($storedImagePath)) {
            echo json_encode(['success' => false, 'message' => 'Registered face image not found.']);
            exit;
        }

        // Process captured image
        $img = str_replace('data:image/png;base64,', '', $imageData);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        
        $tempDir = '../uploads/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempFilePath = $tempDir . 'verify_' . $phone . '.png';
        file_put_contents($tempFilePath, $data);

        // Return paths for client-side comparison
        echo json_encode([
            'success' => true,
            'storedImage' => $storedImagePath,
            'capturedImage' => $tempFilePath
        ]);
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
    }
    exit;
}

else {
    if (!$user_role || !$phone_number) {
        echo '
        alert("User details not present");
        <script>
            alert("Your photo is not updated!");
            window.location = "../Routes/face_reg.php?role=' . $role . '&number=' . $mobile . '";
        </script>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Face Verification</title>
    <link rel="stylesheet" href="../admin/css/admin_login.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../face_auth/face-api.min.js"></script>
    <style>
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
            width: 320px;
            height: 240px;
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
        canvas {
            z-index: 10;
        }
        #verifyButton {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #28a745;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #verifyButton:hover {
            background-color: #218838;
        }
        #verifyButton:disabled {
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
        .comparison-container {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .image-box {
            text-align: center;
            margin: 10px;
        }
        .image-box img {
            max-width: 200px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div id="bodysection">
        <h2>Face Verification</h2>
        <p>Please look directly at the camera to verify your identity.</p>
        <div id="videoContainer">
            <video id="video" width="320" height="240" autoplay muted></video>
            <canvas id="overlayCanvas"></canvas>
        </div>
        <form id="faceVerifyForm">
            <input type="hidden" name="phone" id="phone" value="<?php echo $phone_number; ?>" required>
            <input type="hidden" name="role" id="role" value="<?php echo $user_role; ?>" required>
            <button type="button" id="verifyButton" disabled>Loading...</button>
        </form>
        <div id="statusMessage"></div>
        <div id="comparisonResults" class="comparison-container" style="display:none;">
            <div class="image-box">
                <h4>Registered Face</h4>
                <img id="registeredFace" src="" alt="Registered Face" style="height:80px">
            </div>
            <div class="image-box">
                <h4>Current Capture</h4>
                <img id="capturedFace" src="" alt="Captured Face" style="height:80px">
            </div>
        </div>
        <canvas id="captureCanvas" style="display:none;"></canvas>
    </div>

    <script>
        const video = document.getElementById('video');
        const verifyButton = document.getElementById('verifyButton');
        const statusMessage = document.getElementById('statusMessage');
        const captureCanvas = document.getElementById('captureCanvas');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const ctxOverlay = overlayCanvas.getContext('2d');
        const comparisonResults = document.getElementById('comparisonResults');
        const registeredFaceImg = document.getElementById('registeredFace');
        const capturedFaceImg = document.getElementById('capturedFace');

        let modelsLoaded = false;
        let faceMatcher = null;
        let storedDescriptor = null;

        async function loadModels() {
            const MODEL_URL = '../face_auth/weights';
            statusMessage.textContent = 'Loading face detection models...';
            try {
                await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
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
                verifyButton.disabled = true;
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    video.play();
                    verifyButton.disabled = false;
                    verifyButton.textContent = 'Verify Face';
                    statusMessage.textContent = 'Camera ready. Click button to verify.';
                    overlayCanvas.width = video.videoWidth;
                    overlayCanvas.height = video.videoHeight;
                    detectFace();
                };
            } catch (err) {
                console.error("Error accessing webcam: ", err);
                statusMessage.textContent = 'Error accessing webcam. Please allow camera access.';
                statusMessage.className = 'error';
                verifyButton.disabled = true;
            }
        }
        
        async function detectFace() {
            if (video.paused || video.ended || !modelsLoaded) {
                return setTimeout(() => detectFace());
            }

            const detections = await faceapi.detectAllFaces(video, 
                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            ctxOverlay.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
            
            if (detections.length > 0) {
                faceapi.draw.drawDetections(overlayCanvas, detections);
                
                if (!verifyButton.disabled) {
                    verifyButton.disabled = false;
                    verifyButton.textContent = 'Verify Face';
                }
            } else {
                if (!verifyButton.disabled) {
                    // verifyButton.disabled = true;
                    verifyButton.textContent = 'Verify Face';
                }
            }
            requestAnimationFrame(detectFace);
        }

        verifyButton.addEventListener('click', async () => {

            statusMessage.textContent = 'Capturing image for verification...';
            verifyButton.disabled = true;

            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            const context = captureCanvas.getContext('2d');
            context.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
            
            const imageDataURL = captureCanvas.toDataURL('image/png');

            // First verify there's a face in the image
            const imgElementForDetection = await faceapi.fetchImage(imageDataURL);
            const detections = await faceapi.detectAllFaces(imgElementForDetection, 
                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.6 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (!detections || detections.length === 0) {
                statusMessage.textContent = 'No face detected in the capture. Please try again.';
                statusMessage.className = 'error';
                verifyButton.disabled = false;
                verifyButton.textContent = 'Verify Face';
                return;
            }

            statusMessage.textContent = 'Verifying face...';
            statusMessage.className = '';

            // Send to server to get stored image path
            $.ajax({
                url: 'face_auth.php',
                type: 'POST',
                data: {
                    phone: "<?php echo $_GET['number']; ?>",
                    role: "<?php echo $_GET['role']; ?>",
                    image: imageDataURL
                },
                dataType: 'json',
                success: async function(response) {
                    if (response.success) {
                        // Load both images for comparison
                        registeredFaceImg.src = response.storedImage;
                        capturedFaceImg.src = response.capturedImage;
                        comparisonResults.style.display = 'flex';

                        // Perform face matching
                        const storedImg = await faceapi.fetchImage(response.storedImage);
                        const capturedImg = await faceapi.fetchImage(response.capturedImage);

                        const storedDetection = await faceapi.detectSingleFace(storedImg, 
                            new faceapi.SsdMobilenetv1Options())
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                            
                        const capturedDetection = await faceapi.detectSingleFace(capturedImg, 
                            new faceapi.SsdMobilenetv1Options())
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        if (!storedDetection || !capturedDetection) {
                            statusMessage.textContent = 'Could not process faces for comparison.';
                            statusMessage.className = 'error';
                            verifyButton.disabled = false;
                            verifyButton.textContent = 'Try Again';
                            return;
                        }

                        // Calculate Euclidean distance between descriptors
                        const distance = faceapi.euclideanDistance(
                            storedDetection.descriptor,
                            capturedDetection.descriptor
                        );

                        // Threshold for face matching (adjust as needed)
                        const threshold = 0.6;
                        
                        if (distance < threshold) {
                            statusMessage.textContent = 'Face verified successfully!';
                            statusMessage.className = 'success';
                            
                            // Redirect to dashboard or next page
                            setTimeout(() => {
                                window.location.href = '../Routes/dashboard.php';
                            }, 2000);
                        } else {
                            statusMessage.textContent = 'Face verification failed. Please try again.';
                            statusMessage.className = 'error';
                            verifyButton.disabled = false;
                            verifyButton.textContent = 'Try Again';
                        }
                    } else {
                        statusMessage.textContent = "Error: " + response.message;
                        statusMessage.className = 'error';
                        verifyButton.disabled = false;
                        verifyButton.textContent = 'Try Again';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    statusMessage.textContent = 'An error occurred during verification.';
                    statusMessage.className = 'error';
                    verifyButton.disabled = false;
                    verifyButton.textContent = 'Try Again';
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