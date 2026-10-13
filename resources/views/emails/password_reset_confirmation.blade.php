<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Confirmation</title>
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
            background-color: #059669;
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
        .success-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            color: #065f46;
            font-size: 14px;
        }
        .alert {
            font-size: 13px;
            color: #991b1b;
            line-height: 1.5;
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
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
                Your account password was successfully reset on <strong>{{ $resetTime ?? now()->toDayDateTimeString() }}</strong>.
            </div>
            
            <div class="success-box">
                All existing active application sessions have been invalidated. Please log in with your new password.
            </div>

            <div class="alert">
                <strong>Didn't make this change?</strong> If you did not reset your password, your account may be compromised. Please reach out to your system administrator immediately to secure your account.
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'HRM / CRM') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
