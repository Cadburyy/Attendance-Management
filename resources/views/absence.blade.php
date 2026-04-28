<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        :root {
            --primary: #0f172a;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --bg: #f8fafc;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--primary);
            overflow: hidden;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Camera Section */
        .camera-section {
            flex: 1.5;
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        .overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 40px solid rgba(0,0,0,0.3);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .scanner-box {
            width: 60%;
            height: 60%;
            border: 2px solid var(--accent);
            border-radius: 20px;
            box-shadow: 0 0 0 4000px rgba(0,0,0,0.4);
            position: relative;
        }

        .scanner-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--accent);
            box-shadow: 0 0 15px var(--accent);
            animation: scan 2s infinite ease-in-out;
        }

        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }

        /* Info Section */
        .info-section {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #e2e8f0;
            box-shadow: -10px 0 30px rgba(0,0,0,0.05);
            z-index: 10;
        }

        .header {
            padding: 30px;
            background: var(--primary);
            color: white;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            margin: 5px 0 0;
            opacity: 0.7;
            font-size: 14px;
        }

        .current-status {
            padding: 20px 30px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        #status-display {
            font-weight: 600;
            font-size: 18px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .attendance-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .attendance-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #f1f5f9;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .attendance-item:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
            margin-right: 15px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
            display: block;
        }

        .user-time {
            font-size: 13px;
            color: #64748b;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-in { background: #dcfce7; color: #166534; }
        .badge-out { background: #fee2e2; color: #991b1b; }

        .footer {
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .btn-back {
            text-decoration: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: color 0.2s;
        }

        .btn-back:hover { color: var(--accent); }

        .btn-manual-trigger {
            background: var(--primary);
            border: none;
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.2);
        }

        .btn-manual-trigger:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }

        /* Notification Toast */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 100;
        }

        #toast.show { transform: translateX(-50%) translateY(0); }

        /* Confirmation Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .confirm-modal {
            background: white;
            padding: 40px;
            border-radius: 24px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalPop {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .confirm-title { font-size: 24px; font-weight: 700; margin-bottom: 10px; color: var(--primary); }
        .confirm-name { font-size: 32px; font-weight: 800; color: var(--accent); margin-bottom: 30px; display: block; }
        
        .btn-group { display: flex; gap: 15px; justify-content: center; margin-bottom: 25px; }
        .btn-confirm { 
            padding: 12px 30px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: all 0.2s;
            flex: 1;
        }
        .btn-yes { background: var(--success); color: white; }
        .btn-no { background: #f1f5f9; color: #64748b; }
        .btn-confirm:hover { transform: translateY(-2px); filter: brightness(1.1); }

        /* Manual Search Section */
        #manual-search-section { display: none; margin-top: 20px; text-align: left; }
        .search-container { position: relative; }
        .search-input {
            width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px;
            font-family: inherit; font-size: 16px; outline: none; transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--accent); }
        .suggestions {
            position: absolute; top: 100%; left: 0; right: 0; background: white;
            border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 5px;
            max-height: 200px; overflow-y: auto; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: none; z-index: 10;
        }
        .suggestion-item {
            padding: 12px 15px; cursor: pointer; transition: background 0.2s;
            border-bottom: 1px solid #f8fafc;
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: #f1f5f9; color: var(--accent); }
    </style>
</head>
<body>

<div class="container">
    <div class="camera-section">
        <video id="video" autoplay muted></video>
        <div class="overlay">
            <div class="scanner-box">
                <div class="scanner-line"></div>
            </div>
        </div>
        <canvas id="canvas" style="display:none;"></canvas>
    </div>

    <div class="info-section">
        <div class="header">
            <h1><i class="fas fa-robot"></i> AI Presence</h1>
            <p>Smart Attendance Identification</p>
        </div>

        <div class="current-status">
            <div id="status-display">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>Initializing System...</span>
            </div>
        </div>

        <div class="attendance-list" id="attendance-list">
            <!-- List will be populated by JS -->
        </div>

        <div class="footer">
            <button class="btn-manual-trigger" onclick="showManualSearch()">
                <i class="fas fa-keyboard"></i> Absen Manual
            </button>
            <a href="/" class="btn-back" id="btn-back-dashboard">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div id="toast">
    <i class="fas fa-check-circle" id="toast-icon"></i>
    <span id="toast-message">Check-in Successful!</span>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirm-modal">
    <div class="confirm-modal">
        <div id="detection-view">
            <p class="confirm-title">Apakah ini Anda?</p>
            <span class="confirm-name" id="detected-name">RIVAN</span>
            <div class="btn-group">
                <button class="btn-confirm btn-yes" onclick="confirmIdentity()">Ya, Benar</button>
                <button class="btn-confirm btn-no" onclick="showManualSearch()">Bukan Saya</button>
            </div>
        </div>

        <div id="manual-search-section">
            <p class="confirm-title" style="font-size: 18px;">Pilih Nama Anda</p>
            <div class="search-container">
                <input type="text" id="user-search" class="search-input" placeholder="Ketik nama anda..." oninput="handleSearch(this.value)">
                <div class="suggestions" id="search-suggestions"></div>
            </div>
            <button class="btn-confirm btn-no" style="width: 100%; margin-top: 15px;" onclick="backToDetection()">Kembali</button>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const statusDisplay = document.getElementById('status-display');
    const attendanceList = document.getElementById('attendance-list');
    const toast = document.getElementById('toast');
    
    let isProcessing = false;
    let isModalOpen = false;
    let detectedUser = null;
    let detectedUserId = null;
    let lastProcessedUser = null;
    let lastProcessedTime = 0;
    let allUsers = []; // Local cache for instant search
    let lastCapturedImage = null;
    let analysisInterval = null;
    let currentAbortController = null;

    // Start Camera
    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            video.srcObject = stream;
            statusDisplay.innerHTML = '<i class="fas fa-eye text-primary"></i> <span>Ready to Scan</span>';
            
            // Analyze every 2s to reduce server load
            analysisInterval = setInterval(captureAndAnalyze, 2000); 
        } catch (err) {
            statusDisplay.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> <span>Camera Error</span>';
            console.error(err);
        }
    }

    // Stop everything (Camera, AI Request, Interval)
    const stopEverything = () => {
        if (analysisInterval) clearInterval(analysisInterval);
        if (currentAbortController) currentAbortController.abort();
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
        isProcessing = true;
    };

    window.addEventListener('beforeunload', stopEverything);
    if (document.getElementById('btn-back-dashboard')) {
        document.getElementById('btn-back-dashboard').addEventListener('click', stopEverything);
    }

    async function captureAndAnalyze() {
        if (isProcessing) return;

        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        
        isProcessing = true;
        statusDisplay.innerHTML = '<i class="fas fa-sync fa-spin text-accent"></i> <span>Analyzing Frame...</span>';

        currentAbortController = new AbortController();

        try {
            const response = await fetch('{{ route("absence.analyze") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ image: imageData }),
                signal: currentAbortController.signal
            });
            
            if (response.status === 419) {
                location.reload();
                return;
            }

            const data = await response.json();
            
            if (data.status === 'success') {
                const now = Date.now();
                if (isModalOpen) return;

                if (data.already_attended) {
                    statusDisplay.innerHTML = '<i class="fas fa-check-double text-success"></i> <span>Anda Sudah Absen</span>';
                    return;
                }

                const currentName = data.name ? data.name.toLowerCase() : "";
                const prevName = lastProcessedUser ? lastProcessedUser.toLowerCase() : "";

                if (currentName !== prevName || (now - lastProcessedTime > 15000)) {
                    detectedUserId = data.user_id;
                    showConfirmation(data.name);
                } else {
                    statusDisplay.innerHTML = '<i class="fas fa-user-check text-success"></i> <span>Scan Complete</span>';
                }
            } else if (data.status === 'no_uniform') {
                statusDisplay.innerHTML = `<i class="fas fa-tshirt text-warning"></i> <span>${data.message}</span>`;
            } else if (data.status === 'unknown') {
                statusDisplay.innerHTML = '<i class="fas fa-user-secret text-danger"></i> <span>Wajah Tidak Dikenal</span>';
            } else {
                statusDisplay.innerHTML = `<i class="fas fa-search text-muted"></i> <span>${data.message || 'Scanning...'}</span>`;
            }
        } catch (err) {
            statusDisplay.innerHTML = '<i class="fas fa-wifi-slash text-danger"></i> <span>AI Server Offline</span>';
        } finally {
            isProcessing = false;
        }
    }

    function capturePhoto() {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        lastCapturedImage = canvas.toDataURL('image/webp', 0.8);
    }

    function showConfirmation(name) {
        isModalOpen = true;
        detectedUser = name;
        capturePhoto();
        document.getElementById('detected-name').innerText = name;
        document.getElementById('confirm-modal').style.display = 'flex';
        document.getElementById('detection-view').style.display = 'block';
        document.getElementById('manual-search-section').style.display = 'none';
        statusDisplay.innerHTML = '<i class="fas fa-pause-circle text-warning"></i> <span>Waiting for confirmation...</span>';
    }

    function confirmIdentity() {
        if (!detectedUserId && !detectedUser) return;
        
        // Jika belum ada foto (karena absen manual), ambil foto sekarang (Autocapture)
        if (!lastCapturedImage) {
            capturePhoto();
        }
        
        recordAttendance(detectedUserId || detectedUser);
        closeModal();
    }

    function closeModal() {
        isModalOpen = false;
        detectedUser = null;
        detectedUserId = null;
        lastCapturedImage = null;
        document.getElementById('confirm-modal').style.display = 'none';
        document.getElementById('user-search').value = '';
        document.getElementById('search-suggestions').style.display = 'none';
        statusDisplay.innerHTML = '<i class="fas fa-eye text-primary"></i> <span>Ready to Scan</span>';
    }

    function showManualSearch() {
        isModalOpen = true;
        document.getElementById('confirm-modal').style.display = 'flex';
        document.getElementById('detection-view').style.display = 'none';
        document.getElementById('manual-search-section').style.display = 'block';
        document.getElementById('user-search').focus();
        statusDisplay.innerHTML = '<i class="fas fa-keyboard text-accent"></i> <span>Manual Input Mode</span>';
    }

    function backToDetection() {
        if (!detectedUser) {
            closeModal();
        } else {
            document.getElementById('detection-view').style.display = 'block';
            document.getElementById('manual-search-section').style.display = 'none';
        }
    }

    async function fetchAllUsers() {
        try {
            const response = await fetch('{{ route("absence.all-users") }}');
            if (response.status === 419) location.reload();
            const data = await response.json();
            allUsers = data;
        } catch (err) {
            console.error('Error pre-loading users:', err);
        }
    }

    function handleSearch(query) {
        const suggestionsDiv = document.getElementById('search-suggestions');
        
        if (query.length < 2) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        // Search locally in allUsers array (Instant!)
        const filtered = allUsers.filter(user => 
            user.name.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 5); // Limit to top 5

        if (filtered.length > 0) {
            suggestionsDiv.innerHTML = filtered.map(user => {
                // Escape single quotes for the onclick attribute
                const escapedName = user.name.replace(/'/g, "\\'");
                return `
                    <div class="suggestion-item" onclick="selectUser('${escapedName}')">
                        ${user.name}
                    </div>
                `;
            }).join('');
            suggestionsDiv.style.display = 'block';
        } else {
            suggestionsDiv.style.display = 'none';
        }
    }

    function selectUser(name) {
        detectedUser = name;
        detectedUserId = null; // Reset ID if manual search is used
        confirmIdentity();
    }

    async function recordAttendance(identity) {
        try {
            const body = typeof identity === 'number' 
                ? { user_id: identity, image: lastCapturedImage } 
                : { name: identity, image: lastCapturedImage };
                
            const response = await fetch('{{ route("absence.record") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(body)
            });
            
            if (response.status === 419) {
                location.reload();
                return;
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                lastProcessedUser = typeof identity === 'string' ? identity : data.user;
                lastProcessedTime = Date.now();
                statusDisplay.innerHTML = '<i class="fas fa-check-circle text-success"></i> <span>' + data.message + '</span>';
                fetchRecentAttendance();
            } else {
                statusDisplay.innerHTML = '<i class="fas fa-info-circle text-info"></i> <span>' + data.message + '</span>';
            }
        } catch (err) {
            console.error('Error recording attendance:', err);
        }
    }

    async function fetchRecentAttendance() {
        try {
            const response = await fetch('{{ route("absence.recent") }}');
            const data = await response.json();
            
            attendanceList.innerHTML = '';
            data.forEach(item => {
                const initials = item.user.name.split(' ').map(n => n[0]).join('').substring(0, 2);
                const time = item.check_out || item.check_in;
                const type = item.check_out ? 'OUT' : 'IN';
                const badgeClass = item.check_out ? 'badge-out' : 'badge-in';
                
                attendanceList.innerHTML += `
                    <div class="attendance-item">
                        <div class="user-avatar">${initials}</div>
                        <div class="user-info">
                            <span class="user-name">${item.user.name}</span>
                            <span class="user-time">${time}</span>
                        </div>
                        <div class="status-badge ${badgeClass}">${type}</div>
                    </div>
                `;
            });
        } catch (err) {
            console.error('Error fetching recent:', err);
        }
    }

    function showToast(message, type) {
        const toastMsg = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        
        toastMsg.innerText = message;
        toastIcon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Init
    startCamera();
    fetchAllUsers();
    fetchRecentAttendance();
    setInterval(fetchRecentAttendance, 30000); // Update list every 30s
</script>

</body>
</html>
