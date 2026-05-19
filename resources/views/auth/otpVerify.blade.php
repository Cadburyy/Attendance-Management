@extends('layouts.app')

@section('content')
<style>
    body { background-color: #f8f9fa; }
    .otp-card {
        background: #fff; border-radius: 24px; padding: 48px;
        max-width: 480px; width: 100%; margin: auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .otp-header h2 { font-weight: 800; font-size: 28px; color: #000; }
    .otp-header p { color: #6c757d; font-size: 15px; }
    
    .otp-input-group { display: flex; gap: 10px; justify-content: center; margin: 30px 0; }
    .otp-input {
        width: 50px; height: 65px; border: 1.5px solid #e0e0e0; border-radius: 12px;
        text-align: center; font-size: 24px; font-weight: 600; transition: 0.2s;
    }
    .otp-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    
    /* NEW: Professional Button Design - Full Blue on Hover */
    .btn-verify-outline {
        background-color: transparent; /* Initially transparent background */
        border: 2px solid #3b82f6; /* Blue border */
        border-radius: 12px;
        padding: 14px;
        font-weight: 600;
        color: #3b82f6; /* Blue text */
        width: 100%;
        transition: all 0.3s ease-in-out; /* Smooth transition */
        cursor: pointer;
        display: inline-block;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
    }

    /* Hover effect: Whole button becomes blue with white text */
    .btn-verify-outline:hover {
        background-color: #3b82f6; /* Solid Blue background on hover */
        color: #fff; /* White text on hover */
        border-color: #3b82f6; /* Keep border blue */
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Subtle shadow on hover */
    }

    .timer-text { font-size: 14px; color: #6c757d; margin-bottom: 20px; }
    .resend-link { color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer; }
</style>

<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="otp-card text-center">
        <div class="otp-header">
            <h2>Verify OTP</h2>
            <p>A verification code has been sent to your email.<br>Enter it below to continue.</p>
        </div>

        <form id="otp-form" method="POST" action="{{ route('otp.verify.post') }}">
            @csrf
            <input type="hidden" name="otp" id="combined-otp">
            
            <div class="otp-input-group">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                @endfor
            </div>

            <div class="timer-text">
                OTP expires in: <span id="timer" style="color: #000; font-weight: 700;"></span>
                <br>
                Didn't receive code? <a href="{{ route('otp.resend') }}" class="resend-link">Resend OTP</a>
            </div>

            @if(session('message'))
                <div class="alert alert-success py-2 small mb-3">{{ session('message') }}</div>
            @endif

            @error('otp')
                <div class="text-danger small mb-3"><strong>{{ $message }}</strong></div>
            @enderror

            <button type="submit" class="btn-verify-outline mb-4">Verify</button>
        </form>

        <a href="{{ route('login') }}" class="text-muted text-decoration-none small">Back to Log In</a>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Initialize time from the controller
        let timeLeft = Math.floor({{ $expiresIn ?? 300 }});

        // 2. FORCE RESET: If the "New OTP sent" message is visible, 
        // we force the timer to 300 so the user sees 5:00 immediately.
        @if(session('message'))
            timeLeft = 300;
        @endif

        const timerDisplay = document.getElementById('timer');

        function updateDisplay() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        }

        // Run immediately on load
        updateDisplay();

        const countdown = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerDisplay.textContent = "0:00";
                timerDisplay.style.color = "red";
                return;
            }
            
            timeLeft--;
            updateDisplay();
        }, 1000);

        // --- Keep your existing Input Auto-focus logic below ---
        const inputs = document.querySelectorAll('.otp-input');
        const combined = document.getElementById('combined-otp');

        inputs.forEach((input, i) => {
            input.addEventListener('input', () => {
                if (input.value && i < inputs.length - 1) inputs[i + 1].focus();
                combined.value = Array.from(inputs).map(inp => inp.value).join('');
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && i > 0) inputs[i - 1].focus();
            });
        });
    });
</script>
@endsection