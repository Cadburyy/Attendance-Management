import os
import cv2
import numpy as np
import base64
import json
from datetime import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS
from ultralytics import YOLO
from deepface import DeepFace

# Disable DeepFace logging spam and fix OpenMP duplicate issues on Windows
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 
os.environ['KMP_DUPLICATE_LIB_OK'] = 'True'

app = Flask(__name__)
CORS(app)

# Load Model
MODEL_PATH = os.path.join(os.path.dirname(__file__), 'yolov8_cnk_best (3).pt')

print(f"Loading YOLO model from {MODEL_PATH}...")
model = YOLO(MODEL_PATH)

def decode_image(base64_string):
    if ',' in base64_string:
        header, encoded = base64_string.split(',', 1)
    else:
        encoded = base64_string
    img_data = base64.b64decode(encoded)
    nparr = np.frombuffer(img_data, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)

@app.route('/represent', methods=['POST'])
def represent():
    """Extracts face embedding (vector) from an image."""
    try:
        data = request.json
        if not data or 'image' not in data:
            return jsonify({'error': 'No image provided'}), 400

        frame = decode_image(data['image'])
        if frame is None:
            return jsonify({'error': 'Invalid image'}), 400

        # Extract embedding using ArcFace
        embeddings = DeepFace.represent(img_path=frame, model_name="ArcFace", 
                                        enforce_detection=False, detector_backend='retinaface')
        
        if len(embeddings) > 0:
            return jsonify({'status': 'success', 'embedding': embeddings[0]['embedding']})
        else:
            return jsonify({'status': 'error', 'message': 'No face detected'})

    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/analyze', methods=['POST'])
def analyze():
    """Analyzes frame for uniform and recognizes face using provided embeddings."""
    try:
        data = request.json
        if not data or 'image' not in data:
            return jsonify({'error': 'No image provided'}), 400

        frame = decode_image(data['image'])
        if frame is None:
            return jsonify({'error': 'Invalid image'}), 400

        # 1. YOLO Deteksi Pakaian dengan Filter Hari
        results = model.predict(source=frame, conf=0.67, verbose=False)
        
        # 0=Monday, 1=Tuesday, ..., 4=Friday, ..., 6=Sunday
        hari_angka = datetime.now().weekday() 
        pakaian_terdeteksi = False
        
        for result in results:
            for box in result.boxes:
                cls_id = int(box.cls[0])
                
                # Logika: Jika Jumat (4) boleh ID 0 & 1, Jika bukan Jumat hanya ID 0
                if hari_angka == 4:
                    if cls_id in [0, 1]:
                        pakaian_terdeteksi = True
                        break
                else:
                    if cls_id == 0:
                        pakaian_terdeteksi = True
                        break
            if pakaian_terdeteksi: break

        if not pakaian_terdeteksi:
            hari_nama = datetime.now().strftime('%A')
            return jsonify({'status': 'no_uniform', 'message': f'Uniform not valid for {hari_nama}'})

        # 2. Face Recognition via Embeddings
        # Get target embedding
        try:
            target_rep = DeepFace.represent(img_path=frame, model_name="ArcFace", 
                                           enforce_detection=False, detector_backend='retinaface')
            if not target_rep:
                return jsonify({'status': 'unknown', 'message': 'Face not detected'})
            
            target_embedding = np.array(target_rep[0]['embedding'])
        except Exception as e:
            return jsonify({'status': 'unknown', 'message': f'Face detection failed: {str(e)}'})

        # Compare with user_embeddings
        user_embeddings = data.get('user_embeddings', [])
        best_match = None
        min_dist = 1.0 
        threshold = 0.68 # Recommended threshold for ArcFace + Cosine (can be adjusted)

        for user in user_embeddings:
            if not user.get('embedding'): continue
            
            db_embedding = np.array(user['embedding'])
            # Cosine distance
            dist = 1 - (np.dot(target_embedding, db_embedding) / 
                       (np.linalg.norm(target_embedding) * np.linalg.norm(db_embedding)))
            
            if dist < min_dist:
                min_dist = dist
                best_match = user['name']

        if best_match and min_dist < threshold:
            # Find the ID associated with the best match name
            user_id = None
            for user in user_embeddings:
                if user['name'] == best_match:
                    user_id = user.get('id')
                    break

            return jsonify({
                'status': 'success', 
                'name': best_match.upper(),
                'user_id': user_id,
                'message': f'Welcome, {best_match}'
            })
        else:
            return jsonify({'status': 'unknown', 'message': 'Face unknown'})

    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})

if __name__ == '__main__':
    print("AI Server starting on port 5000...")
    app.run(host='127.0.0.1', port=5000, debug=False, threaded=True)
