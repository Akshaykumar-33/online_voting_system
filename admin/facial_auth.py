import cv2
import numpy as np
import os
from mtcnn import MTCNN
from keras.models import load_model
from sklearn.svm import SVC
import pickle

def extract_face(image_path, detector):
    image = cv2.imread(image_path)
    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    faces = detector.detect_faces(image_rgb)
    if len(faces) == 0:
        return None
    x, y, width, height = faces[0]['box']
    face = image_rgb[y:y+height, x:x+width]
    face = cv2.resize(face, (160, 160))
    return face

def get_embedding(model, face_pixels):
    face_pixels = face_pixels.astype('float32')
    mean, std = face_pixels.mean(), face_pixels.std()
    face_pixels = (face_pixels - mean) / std
    face_pixels = np.expand_dims(face_pixels, axis=0)
    return model.predict(face_pixels)[0]

def train_classifier(embedding_dir, facenet_model_path):
    detector = MTCNN()
    facenet_model = load_model(facenet_model_path)
    embeddings, labels = [], []
    
    for person_name in os.listdir(embedding_dir):
        person_path = os.path.join(embedding_dir, person_name)
        for image_name in os.listdir(person_path):
            image_path = os.path.join(person_path, image_name)
            face = extract_face(image_path, detector)
            if face is not None:
                embedding = get_embedding(facenet_model, face)
                embeddings.append(embedding)
                labels.append(person_name)
    
    model = SVC(kernel='linear', probability=True)
    model.fit(embeddings, labels)
    with open('face_classifier.pkl', 'wb') as f:
        pickle.dump(model, f)

def authenticate_user(image_path, facenet_model_path, classifier_path):
    detector = MTCNN()
    facenet_model = load_model(facenet_model_path)
    with open(classifier_path, 'rb') as f:
        model = pickle.load(f)
    
    face = extract_face(image_path, detector)
    if face is None:
        return "No face detected."
    
    embedding = get_embedding(facenet_model, face)
    prediction = model.predict([embedding])
    confidence = model.predict_proba([embedding]).max()
    return f"User: {prediction[0]}, Confidence: {confidence:.2f}"

# Example usage
# train_classifier('dataset/', 'facenet_keras.h5')
# print(authenticate_user('test.jpg', 'facenet_keras.h5', 'face_classifier.pkl'))
