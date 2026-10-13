<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #1e293b;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 36px 32px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
            color: #0f172a;
        }
        .message {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .otp-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 28px 0;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #0284c7;
            margin: 0;
        }
        .warning {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 24px 0 16px;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'HRM / CRM') }}</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello{{ !empty($userName) ? ' ' . e($userName) : '' }},</div>
            <div class="message">
                We received a request to reset your password. Use the verification code below to complete your password reset:
            </div>
            
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="warning">
                <strong>Important:</strong> This verification code is valid for <strong>{{ $expiresMinutes }} minutes</strong>. If you did not request this password reset, please ignore this email or contact your administrator immediately.
            </div>

            <div class="message" style="margin-top: 24px; font-size: 13px; color: #94a3b8;">
                Never share this code with anyone. Our support team will never ask for your verification code.
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'HRM / CRM') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
