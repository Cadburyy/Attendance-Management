@extends('layouts.guest')

@section('content')
<style>
    :root {
        --primary-blue: #0056b3;
        --secondary-blue: #e7f0ff;
    }
    .card-login {
        border: none;
        border-radius: 20px;
        overflow: visible; 
        position: relative;
    }
    .login-left-panel {
        background-color: var(--secondary-blue);
        border-radius: 20px 0 0 20px;
    }
    .btn-login {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
        color: white;
        transition: transform 0.2s;
    }
    .btn-login:hover {
        background-color: #004494;
        color: white;
        transform: translateY(-1px);
    }
    .btn-absence {
        background: white;
        color: var(--primary-blue);
        border: 2px solid var(--primary-blue);
        font-weight: 700;
        border-radius: 50px;
        padding: 10px 25px;
        position: absolute;
        top: -20px;
        right: 40px;
        box-shadow: 0 10px 20px rgba(0, 86, 179, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 10;
    }
    .btn-absence:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 15px 25px rgba(0, 86, 179, 0.3);
    }
    .form-label-blue {
        color: var(--primary-blue);
        font-weight: 600;
    }
    .text-slogan {
        color: #6c757d;
        font-style: italic;
        font-size: 0.9rem;
    }
    .absence-icon {
        width: 10px;
        height: 10px;
        background: var(--primary-blue);
        border-radius: 50%;
        display: inline-block;
    }
    .btn-absence:hover .absence-icon {
        background: white;
    }
</style>

<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card card-login shadow-lg">
                    <a href="{{ route('absence') }}" class="btn-absence">
                        <span class="absence-icon"></span>
                        {{ __('AI System') }}
                    </a>


                    <div class="row g-0 align-items-stretch">
                        <div class="col-md-6 login-left-panel d-flex align-items-center justify-content-center p-5">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 250px;">
                        </div>

                        <div class="col-md-6 p-5 bg-white d-flex flex-column justify-content-center" style="border-radius: 0 20px 20px 0;">
                            <h3 class="mb-2 text-primary fw-bold">{{ __('Attendance Management') }}</h3>
                            <p class="text-slogan mb-4">{{ __('Attendance Made Easier') }}</p>

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label-blue">{{ __('Username') }}</label>
                                    <input id="name" type="text"
                                        class="form-control border-primary @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name') }}" required autocomplete="off" autofocus>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label-blue">{{ __('Password') }}</label>
                                    <input id="password" type="password"
                                        class="form-control border-primary @error('password') is-invalid @enderror"
                                        name="password" required autocomplete="off">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="mt-4">
                                    <button type="submit" id="btn-submit-login" class="btn btn-login w-100 py-2 shadow-sm">
                                        {{ __('Login') }}
                                    </button>
                                </div>
                            </form>
                            <div id="geolocation-status" class="mt-3 text-center text-danger small fw-bold" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const loginForm = document.querySelector('form');
    const btnSubmit = document.getElementById('btn-submit-login');
    const btnAbsence = document.querySelector('.btn-absence');
    const statusDiv = document.getElementById('geolocation-status');

    function getCoordinates() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject('Browser Anda tidak mendukung deteksi lokasi.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    });
                },
                (error) => {
                    let msg = 'Gagal mendeteksi lokasi.';
                    if (error.code === error.PERMISSION_DENIED) {
                        msg = 'Akses lokasi ditolak. Silakan izinkan lokasi di browser Anda.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        msg = 'Informasi lokasi tidak tersedia.';
                    } else if (error.code === error.TIMEOUT) {
                        msg = 'Deteksi lokasi timeout.';
                    }
                    reject(msg);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    async function checkLocation(coords) {
        const response = await fetch('{{ route("absence.verify-location") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(coords)
        });
        return await response.json();
    }

    // Intercept Login Form Submit
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // UI Loading State
        btnSubmit.disabled = true;
        const originalText = btnSubmit.innerText;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memverifikasi Lokasi...';
        statusDiv.style.display = 'none';

        try {
            const coords = await getCoordinates();
            const result = await checkLocation(coords);

            if (result.status === 'allowed') {
                // If coordinates verified, append to form and submit
                let latInput = document.createElement('input');
                latInput.type = 'hidden';
                latInput.name = 'latitude';
                latInput.value = coords.latitude;
                loginForm.appendChild(latInput);

                let lngInput = document.createElement('input');
                lngInput.type = 'hidden';
                lngInput.name = 'longitude';
                lngInput.value = coords.longitude;
                loginForm.appendChild(lngInput);

                loginForm.submit();
            } else {
                statusDiv.innerText = result.message || 'Anda berada di luar jangkauan kantor.';
                statusDiv.style.display = 'block';
                btnSubmit.disabled = false;
                btnSubmit.innerText = originalText;
            }
        } catch (error) {
            statusDiv.innerText = error;
            statusDiv.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.innerText = originalText;
        }
    });

    // Intercept AI System Button Click
    btnAbsence.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const originalText = btnAbsence.innerHTML;
        btnAbsence.style.pointerEvents = 'none';
        btnAbsence.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking Location...';

        try {
            const coords = await getCoordinates();
            const result = await checkLocation(coords);

            if (result.status === 'allowed') {
                window.location.href = btnAbsence.href;
            } else {
                alert(result.message || 'Anda berada di luar jangkauan kantor.');
                btnAbsence.style.pointerEvents = 'auto';
                btnAbsence.innerHTML = originalText;
            }
        } catch (error) {
            alert('Akses lokasi diperlukan: ' + error);
            btnAbsence.style.pointerEvents = 'auto';
            btnAbsence.innerHTML = originalText;
        }
    });
</script>
@endsection