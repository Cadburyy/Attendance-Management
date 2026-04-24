import cv2
from deepface import DeepFace
import os

try:
    print("Testing DeepFace...")
    # Just a dummy check
    rep = DeepFace.represent(img_path="test_image.jpg", model_name="Facenet", enforce_detection=False)
    print("Success")
except Exception as e:
    print(f"Error: {e}")
