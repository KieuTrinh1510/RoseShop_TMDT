<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực tài khoản RoseShop</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #fff1f2;
            margin: 0;
            padding: 0;
            color: #374151;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08);
            border: 1px solid #fecdd3;
        }
        .email-header {
            background: linear-gradient(135deg, #e11d48, #f43f5e);
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .email-body {
            padding: 32px 30px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 30px 0;
        }
        .btn-verify {
            display: inline-block;
            background-color: #e11d48;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 32px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
            font-size: 15px;
            transition: background-color 0.2s;
        }
        .btn-verify:hover {
            background-color: #be123c;
        }
        .notice {
            background-color: #fff1f2;
            border-left: 4px solid #e11d48;
            padding: 14px 16px;
            border-radius: 6px;
            font-size: 13px;
            color: #9f1239;
            margin-bottom: 24px;
        }
        .raw-link {
            word-break: break-all;
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #e5e7eb;
        }
        .raw-link a {
            color: #e11d48;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🌸 RoseShop</h1>
            <p style="margin: 6px 0 0 0; font-size: 14px; opacity: 0.95;">Tiệm Hoa Tươi & Quà Tặng</p>
        </div>

        <div class="email-body">
            <div class="greeting">Xin chào {{ $tenKhachHang }},</div>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>RoseShop</strong>! Để kích hoạt và bắt đầu sử dụng tài khoản, vui lòng bấm vào nút xác nhận bên dưới:</p>

            <div class="btn-wrapper">
                <a href="{{ $verificationUrl }}" class="btn-verify" target="_blank">
                    ✓ Xác thực tài khoản của tôi
                </a>
            </div>

            <div class="notice">
                ⏰ <strong>Lưu ý:</strong> Liên kết xác thực này có hiệu lực trong vòng <strong>{{ $expireMinutes }} phút</strong>. Sau thời gian này, bạn sẽ cần yêu cầu gửi lại email xác thực mới.
            </div>

            <p style="font-size: 13px; color: #6b7280;">
                Nếu bạn không thực hiện đăng ký tài khoản tại RoseShop, vui lòng bỏ qua email này. Tài khoản sẽ không được kích hoạt nếu không có xác nhận từ bạn.
            </p>

            <div class="raw-link">
                Nếu không bấm được nút phía trên, bạn có thể sao chép và dán liên kết sau vào trình duyệt:<br>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
            </div>
        </div>

        <div class="email-footer">
            © {{ date('Y') }} RoseShop TMDT. Mọi quyền được bảo lưu.<br>
            Hotline: 0987.654.321 • Địa chỉ: Hà Nội
        </div>
    </div>
</body>
</html>
