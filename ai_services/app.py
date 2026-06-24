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
import urllib.request
import mediapipe as mp
try:
    from mediapipe.tasks import python
    from mediapipe.tasks.python import vision
    mp_tasks_available = True
except Exception as e:
    print(f"Warning: MediaPipe Tasks API failed to import: {e}")
    mp_tasks_available = False

# Auto-download face landmarker model if it doesn't exist
MODEL_DIR = os.path.dirname(__file__)
TASK_FILE_PATH = os.path.join(MODEL_DIR, 'face_landmarker.task')

if mp_tasks_available and not os.path.exists(TASK_FILE_PATH):
    try:
        print("Downloading Face Landmarker model asset (face_landmarker.task)...")
        url = "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task"
        urllib.request.urlretrieve(url, TASK_FILE_PATH)
        print("Model downloaded successfully!")
    except Exception as e:
        print(f"Error downloading face landmarker model: {e}")


# Disable DeepFace logging spam and fix OpenMP duplicate issues on Windows
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 
os.environ['KMP_DUPLICATE_LIB_OK'] = 'True'

app = Flask(__name__)
CORS(app)

# ============================================
# CONFIG / SETTINGS
# ============================================
# TRIAL MODE: Set to True to skip uniform detection (face-only mode), or False to enable it.
TRIAL_MODE = True
if TRIAL_MODE:
    print("⚠️  TRIAL MODE ACTIVE: Uniform detection is DISABLED. Face-only mode.")

# Toggle which liveness challenges are active/available
LIVENESS_CHALLENGES = {
    'blink': True,
    'turn_left': True,
    'turn_right': True
}

# Load Model
MODEL_PATH = os.path.join(os.path.dirname(__file__), 'yolov8_cnk_best (3).pt')

print(f"Loading YOLO model from {MODEL_PATH}...")
model = YOLO(MODEL_PATH)

# Initialize MediaPipe Face Landmarker globally at startup
global_landmarker = None
if mp_tasks_available and os.path.exists(TASK_FILE_PATH):
    try:
        print("Initializing MediaPipe Face Landmarker globally...")
        base_options = python.BaseOptions(model_asset_path=TASK_FILE_PATH)
        options = vision.FaceLandmarkerOptions(
            base_options=base_options,
            output_face_blendshapes=False,
            output_facial_transformation_matrixes=False,
            num_faces=1
        )
        global_landmarker = vision.FaceLandmarker.create_from_options(options)
        print("MediaPipe Face Landmarker initialized successfully.")
    except Exception as e:
        print(f"Error initializing MediaPipe Face Landmarker: {e}")

def decode_image(base64_string):
    if ',' in base64_string:
        header, encoded = base64_string.split(',', 1)
    else:
        encoded = base64_string
    img_data = base64.b64decode(encoded)
    nparr = np.frombuffer(img_data, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)

def calculate_ear(eye_landmarks):
    def dist(pt1, pt2):
        return np.linalg.norm(np.array([pt1.x, pt1.y]) - np.array([pt2.x, pt2.y]))
    
    p1, p2, p3, p4, p5, p6 = eye_landmarks
    ear = (dist(p2, p6) + dist(p3, p5)) / (2.0 * dist(p1, p4))
    return ear

def detect_head_turn(face_landmarks):
    nose = face_landmarks[1]
    left_edge = face_landmarks[234]
    right_edge = face_landmarks[454]
    
    width = right_edge.x - left_edge.x
    if width <= 0:
        return 0.5
    
    ratio = (nose.x - left_edge.x) / width
    return ratio

def get_face_embeddings_with_fallbacks(frame, enforce_detection=True):
    """
    Attempts to detect faces and extract embeddings using multiple backends.
    Tries: retinaface, opencv, mtcnn in order.
    """
    for backend in ['retinaface', 'opencv', 'mtcnn']:
        try:
            embeddings = DeepFace.represent(
                img_path=frame,
                model_name="ArcFace",
                enforce_detection=True,
                detector_backend=backend
            )
            if embeddings and len(embeddings) > 0 and 'embedding' in embeddings[0]:
                return embeddings
        except Exception:
            continue
            
    # If all enforced detections fail, fallback to retinaface/opencv with enforce_detection=False
    if not enforce_detection:
        for backend in ['retinaface', 'opencv']:
            try:
                embeddings = DeepFace.represent(
                    img_path=frame,
                    model_name="ArcFace",
                    enforce_detection=False,
                    detector_backend=backend
                )
                if embeddings and len(embeddings) > 0 and 'embedding' in embeddings[0]:
                    return embeddings
            except Exception:
                pass
    return None

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

        # Extract embedding using multi-backend fallback
        embeddings = get_face_embeddings_with_fallbacks(frame, enforce_detection=False)
        
        if len(embeddings) > 0:
            return jsonify({'status': 'success', 'embedding': embeddings[0]['embedding']})
        else:
            return jsonify({'status': 'error', 'message': 'No face detected'})

    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/liveness-config', methods=['GET'])
def liveness_config():
    """Returns active liveness challenges."""
    return jsonify({
        'status': 'success',
        'challenges': [k for k, v in LIVENESS_CHALLENGES.items() if v]
    })

@app.route('/liveness', methods=['POST'])
def verify_liveness():
    """Verifies liveness challenge from a series of frames."""
    try:
        data = request.json
        if not data or 'frames' not in data or 'challenge' not in data:
            return jsonify({'status': 'error', 'message': 'Missing frames or challenge'}), 400
        
        frames = data['frames']
        challenge = data['challenge']
        flash_active = data.get('flash_active', False)
        
        if not LIVENESS_CHALLENGES.get(challenge, False):
            return jsonify({'status': 'failed', 'message': f'Challenge {challenge} is disabled/invalid'}), 400
            
        if not frames or len(frames) == 0:
            return jsonify({'status': 'failed', 'message': 'No frames provided'}), 400
            
        if not mp_tasks_available or not os.path.exists(TASK_FILE_PATH):
            return jsonify({'status': 'error', 'message': 'MediaPipe Tasks Face Landmarker is not ready or model file is missing.'}), 500
            
        ear_values = []
        turn_ratios = []
        depth_diffs = []
        blue_reflections = []
        
        # Use global landmarker if available, otherwise create a fallback one
        landmarker = global_landmarker
        if landmarker is None:
            base_options = python.BaseOptions(model_asset_path=TASK_FILE_PATH)
            options = vision.FaceLandmarkerOptions(
                base_options=base_options,
                output_face_blendshapes=False,
                output_facial_transformation_matrixes=False,
                num_faces=1
            )
            landmarker = vision.FaceLandmarker.create_from_options(options)

        for frame_b64 in frames:
            frame = decode_image(frame_b64)
            if frame is None:
                continue
            
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_frame)
            
            detection_result = landmarker.detect(mp_image)
            
            if detection_result.face_landmarks:
                landmarks = detection_result.face_landmarks[0]
                
                # 3D Depth Check (Anti-Spoofing flat screens/photos)
                edge_z = (landmarks[234].z + landmarks[454].z) / 2.0
                depth_diff = edge_z - landmarks[1].z
                depth_diffs.append(depth_diff)
                
                # Active Flash Color Check
                if flash_active:
                    xs = [int(pt.x * frame.shape[1]) for pt in landmarks]
                    ys = [int(pt.y * frame.shape[0]) for pt in landmarks]
                    min_x, max_x = max(0, min(xs)), min(frame.shape[1], max(xs))
                    min_y, max_y = max(0, min(ys)), min(frame.shape[0], max(ys))
                    face_roi = frame[min_y:max_y, min_x:max_x]
                    if face_roi.size > 0:
                        avg_b = np.mean(face_roi[:, :, 0])
                        avg_g = np.mean(face_roi[:, :, 1])
                        avg_r = np.mean(face_roi[:, :, 2])
                        blue_reflections.append(avg_b / (avg_r + avg_g + 1e-5))
                
                if challenge == 'blink':
                    left_eye = [landmarks[33], landmarks[160], landmarks[158], landmarks[133], landmarks[153], landmarks[144]]
                    right_eye = [landmarks[362], landmarks[385], landmarks[387], landmarks[263], landmarks[373], landmarks[380]]
                    left_ear = calculate_ear(left_eye)
                    right_ear = calculate_ear(right_eye)
                    avg_ear = (left_ear + right_ear) / 2.0
                    ear_values.append(avg_ear)
                elif challenge in ['turn_left', 'turn_right']:
                    ratio = detect_head_turn(landmarks)
                    turn_ratios.append(ratio)
        
        # Validate 3D Depth Kontur (must not be flat like a photo or screen)
        if depth_diffs:
            avg_depth = np.mean(depth_diffs)
            if avg_depth < 0.012:
                return jsonify({'status': 'failed', 'message': f'Spoofing Terdeteksi: Deteksi Kedalaman Layar 2D (Kontur 3D: {avg_depth:.4f})'})
                
        # Validate Active Flash Color Reflection
        if flash_active and blue_reflections:
            avg_blue_ratio = np.mean(blue_reflections)
            # A real face reflecting a blue screen has a higher blue ratio.
            if avg_blue_ratio < 0.22:
                return jsonify({'status': 'failed', 'message': f'Spoofing Terdeteksi: Gagal memverifikasi pantulan cahaya aktif (Rasio: {avg_blue_ratio:.3f})'})

        if challenge == 'blink':
            if not ear_values:
                return jsonify({'status': 'failed', 'message': 'No face detected in any of the frames'}), 200
            min_ear = min(ear_values)
            max_ear = max(ear_values)
            if min_ear <= 0.21 and max_ear >= 0.25:
                return jsonify({'status': 'passed', 'message': 'Blink detected successfully.'})
            else:
                return jsonify({'status': 'failed', 'message': f'Blink not detected. EAR min: {min_ear:.3f}, max: {max_ear:.3f}. Needs min <= 0.21 and max >= 0.25.'})
                
        elif challenge == 'turn_left':
            if not turn_ratios:
                return jsonify({'status': 'failed', 'message': 'No face detected in any of the frames'}), 200
            max_ratio = max(turn_ratios)
            if max_ratio >= 0.62:
                return jsonify({'status': 'passed', 'message': 'Left turn detected successfully.'})
            else:
                return jsonify({'status': 'failed', 'message': f'Left turn not detected. Max ratio: {max_ratio:.3f}. Needs >= 0.62.'})
                
        elif challenge == 'turn_right':
            if not turn_ratios:
                return jsonify({'status': 'failed', 'message': 'No face detected in any of the frames'}), 200
            min_ratio = min(turn_ratios)
            if min_ratio <= 0.38:
                return jsonify({'status': 'passed', 'message': 'Right turn detected successfully.'})
            else:
                return jsonify({'status': 'failed', 'message': f'Right turn not detected. Min ratio: {min_ratio:.3f}. Needs <= 0.38.'})
                
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

        # 1. Face Recognition via Embeddings
        try:
            target_rep = get_face_embeddings_with_fallbacks(frame, enforce_detection=False)
            if not target_rep:
                return jsonify({'status': 'unknown', 'message': 'Face not detected'})
            
            target_embedding = np.array(target_rep[0]['embedding'])
        except Exception as e:
            return jsonify({'status': 'unknown', 'message': f'Face detection failed: {str(e)}'})

        # Compare with user_embeddings
        user_embeddings = data.get('user_embeddings', [])
        best_match = None
        best_match_id = None
        min_dist = 1.0 
        threshold = 0.63 # Relaxed from 0.58 to reduce false rejection rate

        for user in user_embeddings:
            # Support both old format ('embedding') and new format ('embeddings')
            embeddings_list = user.get('embeddings', [])
            if not embeddings_list and user.get('embedding'):
                embeddings_list = [user['embedding']]
            
            for emb in embeddings_list:
                if not emb:
                    continue
                db_embedding = np.array(emb)
                # Cosine distance
                dist = 1 - (np.dot(target_embedding, db_embedding) / 
                           (np.linalg.norm(target_embedding) * np.linalg.norm(db_embedding)))
                
                if dist < min_dist:
                    min_dist = dist
                    best_match = user['name']
                    best_match_id = user.get('id')

        if not best_match or min_dist >= threshold:
            return jsonify({'status': 'unknown', 'message': f'Face unknown (Dist: {min_dist:.3f})'})

        # 2. YOLO Deteksi Pakaian dengan Filter Hari (skip in TRIAL_MODE or on Fridays)
        has_uniform = True
        hari_angka = datetime.now().weekday()
        is_friday = (hari_angka == 4)

        if not TRIAL_MODE and not is_friday:
            results = model.predict(source=frame, conf=0.67, verbose=False)
            pakaian_terdeteksi = False
            
            for result in results:
                for box in result.boxes:
                    cls_id = int(box.cls[0])
                    if cls_id == 0:
                        pakaian_terdeteksi = True
                        break
                if pakaian_terdeteksi: break

            has_uniform = pakaian_terdeteksi

        return jsonify({
            'status': 'success', 
            'name': best_match.upper(),
            'user_id': best_match_id,
            'has_uniform': has_uniform,
            'message': f'Welcome, {best_match}'
        })

    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/chat', methods=['POST'])
def chat():
    """AI chatbot assistant endpoint for employees."""
    try:
        data = request.json or {}
        message = data.get('message', '')
        user_name = data.get('user_name', 'Karyawan')
        attendance_info = data.get('attendance_info')
        recap_info = data.get('recap_info')
        company_stats = data.get('company_stats')
        shift_info = data.get('shift_info', {})
        current_date = data.get('current_date', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))

        if not message:
            return jsonify({'reply': 'Ada yang bisa saya bantu?'}), 400

        # Read OpenRouter API Key from environment or .env file in the current directory
        api_key = os.environ.get('OPENROUTER_API_KEY')
        if not api_key:
            env_path = os.path.join(os.path.dirname(__file__), '.env')
            if os.path.exists(env_path):
                with open(env_path, 'r') as f:
                    for line in f:
                        if line.strip().startswith('OPENROUTER_API_KEY='):
                            api_key = line.strip().split('=', 1)[1].strip()
                            break

        if api_key:
            import urllib.request
            import urllib.parse
            url = "https://openrouter.ai/api/v1/chat/completions"
            
            # Format today's attendance record
            att_text = "Belum melakukan absensi hari ini."
            if attendance_info:
                check_in = attendance_info.get('checked_in') or 'Belum check-in'
                check_out = attendance_info.get('checked_out') or 'Belum check-out'
                status = attendance_info.get('status') or '-'
                shift = attendance_info.get('shift') or '-'
                att_text = f"Check-in: {check_in}, Check-out: {check_out}, Status: {status}, Shift: {shift}."

            # Format monthly recap info
            recap_text = "Tidak ada data rekap absensi bulan ini."
            if recap_info:
                summary = recap_info.get('summary', {})
                recent_logs = recap_info.get('recent_logs', [])
                logs_str = []
                for log in recent_logs:
                    logs_str.append(f"- Tanggal: {log.get('date')}, Check-in: {log.get('check_in') or 'Belum'}, Check-out: {log.get('check_out') or 'Belum'}, Status: {log.get('status')}, Shift: {log.get('shift')}")
                logs_formatted = "\n".join(logs_str) if logs_str else "(tidak ada)"
                
                recap_text = (
                    f"Bulan: {recap_info.get('month')}\n"
                    f"- Total Hadir Tepat Waktu: {summary.get('total_present', 0)}\n"
                    f"- Total Terlambat: {summary.get('total_late', 0)}\n"
                    f"- Total Sakit: {summary.get('total_sick', 0)}\n"
                    f"- Total Izin: {summary.get('total_leave', 0)}\n"
                    f"- Total Mangkir: {summary.get('total_absent', 0)}\n"
                    f"Riwayat Absensi 10 Hari Terakhir:\n{logs_formatted}"
                )

            # Format company stats (for admin/hr queries)
            stats_text = "Anda tidak memiliki akses ke statistik perusahaan atau tidak ada data statistik."
            if company_stats:
                summary = company_stats.get('summary', {})
                checked_in_list = ", ".join(company_stats.get('checked_in_employees_today', [])) or "(tidak ada)"
                late_list = ", ".join(company_stats.get('late_employees_today', [])) or "(tidak ada)"
                not_checked_in_list = ", ".join(company_stats.get('not_checked_in_employees_today', [])) or "(tidak ada)"
                
                stats_text = (
                    f"Tanggal: {company_stats.get('date')}\n"
                    f"- Total Karyawan: {company_stats.get('total_employees', 0)}\n"
                    f"- Hadir Tepat Waktu: {summary.get('total_present_on_time', 0)} orang\n"
                    f"- Terlambat: {summary.get('total_late', 0)} orang\n"
                    f"- Sakit: {summary.get('total_sick', 0)} orang\n"
                    f"- Izin: {summary.get('total_leave', 0)} orang\n"
                    f"- Mangkir/Absen: {summary.get('total_absent', 0)} orang\n"
                    f"- Belum Absen Check-in: {summary.get('total_not_checked_in_yet', 0)} orang\n"
                    f"- Karyawan yang SUDAH absen hari ini: {checked_in_list}\n"
                    f"- Karyawan yang TERLAMBAT hari ini: {late_list}\n"
                    f"- Karyawan yang BELUM absen hari ini: {not_checked_in_list}"
                )

            system_instruction = (
                f"You are a helpful and polite company attendance assistant named 'CNK AI Assistant' for Citra Nugerah Karya.\n"
                f"Answer the employee's query concisely and friendly in Indonesian.\n"
                f"Use the formatted data context below to answer questions about stats, attendance, shifts, or recap. DO NOT make up info.\n\n"
                f"Current time: {current_date}.\n"
                f"Employee's name: {user_name}.\n\n"
                f"--- CONTEXT DATA ---\n"
                f"[Today's Attendance for {user_name}]\n{att_text}\n\n"
                f"[This Month's Attendance Recap for {user_name}]\n{recap_text}\n\n"
                f"[Overall Company Attendance Stats Today (For Admin/HR)]\n{stats_text}\n\n"
                f"[Shifts Configuration Rules]\n"
                f"- Shift 1: Check-in {shift_info.get('1', {}).get('in_start')} to {shift_info.get('1', {}).get('in_end')}. Check-out {shift_info.get('1', {}).get('out_start')} to {shift_info.get('1', {}).get('out_end')}.\n"
                f"- Shift 2: Check-in {shift_info.get('2', {}).get('in_start')} to {shift_info.get('2', {}).get('in_end')}. Check-out {shift_info.get('2', {}).get('out_start')} to {shift_info.get('2', {}).get('out_end')}.\n"
                f"- Shift 3: Check-in {shift_info.get('3', {}).get('in_start')} to {shift_info.get('3', {}).get('in_end')}. Check-out {shift_info.get('3', {}).get('out_start')} to {shift_info.get('3', {}).get('out_end')}.\n"
            )

            print(f"DEBUG CHATBOT:\nSystem Instruction:\n{system_instruction}\nUser Message: {message}\n")

            payload = {
                "model": "openai/gpt-oss-20b:free",
                "messages": [
                    {"role": "user", "content": f"{system_instruction}\n\nUser Question: {message}"}
                ]
            }
            
            req = urllib.request.Request(
                url, 
                data=json.dumps(payload).encode('utf-8'),
                headers={
                    'Content-Type': 'application/json',
                    'Authorization': f'Bearer {api_key}',
                    'HTTP-Referer': 'http://localhost:8000',
                    'X-Title': 'CNK Attendance System'
                }
            )
            
            with urllib.request.urlopen(req, timeout=10) as response:
                res_data = json.loads(response.read().decode('utf-8'))
                reply = res_data['choices'][0]['message']['content']
                return jsonify({'reply': reply.strip()})

        # Fallback rule-based responses if Gemini is not configured
        msg_lower = message.lower()
        if 'halo' in msg_lower or 'hai' in msg_lower or 'pagi' in msg_lower or 'siang' in msg_lower or 'sore' in msg_lower:
            reply = f"Halo {user_name}! Ada yang bisa saya bantu terkait jadwal shift atau absensi hari ini?"
        elif 'absen' in msg_lower or 'hadir' in msg_lower or 'check-in' in msg_lower or 'status' in msg_lower:
            if attendance_info:
                status_str = "Hadir" if attendance_info.get('status') == 'present' else "Terlambat"
                check_in_str = attendance_info.get('checked_in') or 'Belum'
                check_out_str = attendance_info.get('checked_out') or 'Belum'
                reply = f"Status Anda hari ini ({current_date.split(' ')[0]}): {status_str}.\nCheck-in: {check_in_str}\nCheck-out: {check_out_str}"
            else:
                reply = "Anda belum melakukan absensi (check-in) hari ini. Silakan lakukan scan wajah di kamera."
        elif 'shift' in msg_lower or 'jadwal' in msg_lower or 'jam' in msg_lower:
            reply = (
                f"Jadwal Shift saat ini:\n"
                f"- Shift 1 (Pagi): {shift_info.get('1', {}).get('in_start')} - {shift_info.get('1', {}).get('out_end')}\n"
                f"- Shift 2 (Siang): {shift_info.get('2', {}).get('in_start')} - {shift_info.get('2', {}).get('out_end')}\n"
                f"- Shift 3 (Malam): {shift_info.get('3', {}).get('in_start')} - {shift_info.get('3', {}).get('out_end')}"
            )
        else:
            reply = f"Maaf, saat ini Gemini API Key belum dikonfigurasi. Berikut info absensi Anda:\nNama Karyawan: {user_name}\nStatus Hari Ini: {json.dumps(attendance_info) if attendance_info else 'Belum absen'}"

        return jsonify({'reply': reply})

    except Exception as e:
        return jsonify({'reply': f'Maaf, terjadi error internal pada sistem chatbot: {str(e)}'}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})

if __name__ == '__main__':
    print("AI Server starting on port 5000...")
    app.run(host='127.0.0.1', port=5000, debug=False, threaded=True)

