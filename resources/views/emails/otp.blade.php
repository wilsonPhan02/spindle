<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OTP Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #18181b;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .content {
            padding: 32px;
            color: #3f3f46;
            line-height: 1.6;
        }
        .otp-box {
            background-color: #f4f4f5;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #18181b;
            margin: 0;
        }
        .footer {
            background-color: #fafafa;
            padding: 24px;
            text-align: center;
            color: #71717a;
            font-size: 12px;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SPINDLE</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for registering with Spindle. To complete your registration and verify your email address, please use the following One-Time Password (OTP):</p>
            
            <div class="otp-box">
                <p class="otp-code">{{ $otpCode }}</p>
            </div>
            
            <p>This code will expire in 15 minutes. If you did not request this verification, please ignore this email.</p>
            <p>Best regards,<br>The Spindle Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Spindle. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
