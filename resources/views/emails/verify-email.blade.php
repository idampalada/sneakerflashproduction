{{-- File: resources/views/emails/verify-email.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi Akun SneakerFlash</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👟 SneakerFlash</h1>
        <h2>Aktivasi Akun Anda</h2>
    </div>
    
    <div class="content">
        <p>Halo <strong>{{ $user->first_name ?? $user->name }}</strong>,</p>
        
        <p>Terima kasih telah mendaftar di SneakerFlash! Untuk melengkapi pendaftaran Anda, silakan klik tombol di bawah ini untuk mengaktivasi akun Anda:</p>
        
        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">
                Aktivasi Akun Sekarang
            </a>
        </div>
        
        <p>Atau copy dan paste link berikut ke browser Anda:</p>
        <p style="background: #f3f4f6; padding: 10px; border-radius: 4px; word-break: break-all; font-size: 14px;">
            {{ $verificationUrl }}
        </p>
        
        <p><strong>Penting:</strong></p>
        <ul>
            <li>Link aktivasi ini berlaku selama 1 jam</li>
            <li>Jika Anda tidak mendaftar di SneakerFlash, abaikan email ini</li>
            <li>Setelah aktivasi, Anda bisa login dan mulai berbelanja</li>
        </ul>
    </div>
    
    <div class="footer">
        <p>Email ini dikirim otomatis dari SneakerFlash<br>
        Jangan reply email ini karena tidak akan dibalas</p>
        
        <p>Butuh bantuan? Hubungi kami di support@sneakerflash.com</p>
    </div>
</body>
</html>