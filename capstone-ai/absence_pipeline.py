import cv2
import datetime
import os
from ultralytics import YOLO
from deepface import DeepFace

# Load Model (1 Model, 2 Kelas)
model = YOLO('yolov8_cnk_best (3).pt')
cap = cv2.VideoCapture(1)

# ==========================================
# KONFIGURASI JAM KERJA PT SPIL
# ==========================================
# Jendela Check-in (Misal: 07:00 - 09:00)
BATAS_AWAL_MASUK = datetime.time(7, 0, 0)
BATAS_AKHIR_MASUK = datetime.time(16, 0, 0)

# Jendela Check-out (Misal: 16:00 - 18:00)
BATAS_AWAL_PULANG = datetime.time(16, 0, 0)
BATAS_AKHIR_PULANG = datetime.time(18, 0, 0) # BUG FIX: Sebelumnya 07:00:00

# Dictionary untuk menyimpan siapa saja yang sudah sukses absen hari ini
# Format: {'RIVAN': {'masuk': True, 'pulang': False}}
log_absensi_harian = {}

# ==========================================
# KONFIGURASI ANTI-FALSE POSITIVE (BUFFERING)
# ==========================================
syarat_frame_valid = 10  # YOLO harus melihat seragam 10 frame berturut-turut (~0.5 detik)
frame_beruntun = 0       # Penghitung frame saat ini

print("✅ Gerbang Absensi Cerdas (Time-Triggered & Anti-FP) Menyala...")

while cap.isOpened():
    ret, frame = cap.read()
    if not ret: break

    sekarang = datetime.datetime.now()
    hari_ini = sekarang.strftime("%A")
    jam_sekarang = sekarang.time() # Hanya mengambil jam:menit:detik

    # 1. YOLO Deteksi Pakaian
    results = model.predict(source=frame, conf=0.65, verbose=False)

    pakaian_terdeteksi_di_frame_ini = False
    kotak_pakaian = None

    for result in results:
        for box in result.boxes:
            cls_id = int(box.cls[0])
            
            # Filter Logika Hari
            if hari_ini == "Friday" and cls_id in [0, 1]:
                pakaian_terdeteksi_di_frame_ini = True
                kotak_pakaian = box.xyxy[0]
                break # Cukup temukan 1 yang valid
            elif hari_ini != "Friday" and cls_id == 0:
                pakaian_terdeteksi_di_frame_ini = True
                kotak_pakaian = box.xyxy[0]
                break

    # ==========================================
    # 2. LOGIKA PENAHAN FRAME (DEBOUNCE)
    # ==========================================
    if pakaian_terdeteksi_di_frame_ini:
        frame_beruntun += 1
        
        # Gambar kotak kuning proses verifikasi
        if kotak_pakaian is not None:
            x1, y1, x2, y2 = map(int, kotak_pakaian)
            cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 255), 2)
            cv2.putText(frame, f"Verifikasi: {frame_beruntun}/{syarat_frame_valid}", (x1, y1 - 10), 
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 255), 2)
    else:
        # Jika hilang 1 frame saja, hitungan di-reset ke 0! Kemeja biasa gagal di sini.
        frame_beruntun = 0 

    # ==========================================
    # 3. LANJUT FACE RECOGNITION (JIKA STABIL)
    # ==========================================
    if frame_beruntun >= syarat_frame_valid and kotak_pakaian is not None:
        cv2.putText(frame, "Pakaian Sah! Memindai Wajah...", (50, 50), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
        
        try:
            pengenalan = DeepFace.find(img_path=frame, db_path="database_wajah", 
                                       model_name="Facenet", enforce_detection=False, silent=True)
            
            if len(pengenalan[0]) > 0:
                # Ambil nama file dan bersihkan (misal 'database_wajah/rivan.jpg' -> 'RIVAN')
                nama_file = pengenalan[0]['identity'][0]
                nama = os.path.basename(nama_file).split('.')[0].upper()
                
                # Inisialisasi karyawan di memori jika belum ada
                if nama not in log_absensi_harian:
                    log_absensi_harian[nama] = {'masuk': False, 'pulang': False}

                pesan = ""
                warna = (200, 200, 200) # Default Abu-abu

                # ==========================================
                # 4. LOGIKA TRIGGER WAKTU ABSENSI
                # ==========================================
                if BATAS_AWAL_MASUK <= jam_sekarang <= BATAS_AKHIR_MASUK:
                    if not log_absensi_harian[nama]['masuk']:
                        log_absensi_harian[nama]['masuk'] = True
                        pesan = f"CHECK-IN SUKSES: {nama}"
                        warna = (0, 255, 0) # Hijau
                        # TODO: Insert ke Database (Waktu Masuk)
                    else:
                        pesan = f"{nama} sudah Check-In."

                elif BATAS_AWAL_PULANG <= jam_sekarang <= BATAS_AKHIR_PULANG:
                    if not log_absensi_harian[nama]['pulang']:
                        log_absensi_harian[nama]['pulang'] = True
                        pesan = f"CHECK-OUT SUKSES: {nama}"
                        warna = (0, 165, 255) # Orange
                        # TODO: Insert ke Database (Waktu Pulang)
                    else:
                        pesan = f"{nama} sudah Check-Out."
                
                else:
                    pesan = "Di Luar Jam Absen"
                    warna = (0, 0, 255) # Merah (Di luar jendela waktu)

                # Tampilkan ke layar
                cv2.putText(frame, pesan, (50, 100), cv2.FONT_HERSHEY_SIMPLEX, 0.8, warna, 3)

                # Reset frame_beruntun agar DeepFace tidak spam melakukan scan di frame berikutnya
                frame_beruntun = 0

        except Exception as e:
            # Wajah tidak terdeteksi oleh DeepFace
            pass

    cv2.imshow("Sistem Absensi Final", frame)
    if cv2.waitKey(1) & 0xFF == ord('q'): break

cap.release()
cv2.destroyAllWindows()