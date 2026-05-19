<!DOCTYPE html>
<html>
<head>
    <style>
        .container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 600px;
            margin: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 40px;
            color: #1a202c;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 20px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 10px;
            color: #3182ce;
            background-color: #ebf8ff;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            margin: 30px 0;
        }
        .footer {
            font-size: 12px;
            color: #718096;
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Security Verification</h2>
        </div>
        
        <p>Hello,</p>
        <p>A login attempt to the <strong>Attendance Management System</strong> was detected outside of standard working hours (08:00 - 17:00).</p>
        <p>Please use the verification code below to complete your sign-in:</p>
        
        <div class="otp-code">
            {{ $otp }}
        </div>
        
        <p>This code is valid for <strong>5 minutes</strong>. If you did not attempt this login, please secure your account immediately.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} Attendance Management System. All rights reserved.
        </div>
    </div>
</body>
</html>