<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu RoseShop</title>
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
            background: linear-gradient(135deg, #D82677, #EC3D8F);
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
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 24px;
        }
        .option-box {
            background-color: #fffaf0;
            border: 1px solid #feebc8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .option-title {
            font-size: 14px;
            font-weight: 700;
            color: #9c4221;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .code-display {
            background: #ffffff;
            border: 2px dashed #dd6b20;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #c05621;
            font-family: 'SF Mono', Consolas, Monaco, monospace;
            margin: 10px 0;
        }
        .btn-wrapper {
            text-align: center;
            margin: 15px 0 10px 0;
        }
        .btn-reset {
            display: inline-block;
            background-color: #D82677;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            padding: 13px 30px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
            font-size: 14px;
        }
        .btn-reset:hover {
            background-color: #be123c;
        }
        .notice {
            background-color: #fff1f2;
            border-left: 4px solid #D82677;
            padding: 14px 16px;
            border-radius: 6px;
            font-size: 13px;
            color: #9f1239;
            margin: 24px 0 16px 0;
        }
        .raw-link {
            word-break: break-all;
            font-size: 12px;
            color: #6b7280;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e5e7eb;
        }
        .raw-link a {
            color: #D82677;
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
            <p style="margin: 6px 0 0 0; font-size: 14px; opacity: 0.95;">Yêu cầu đặt lại mật khẩu</p>
        </div>

        <div class="email-body">
            <div class="greeting">Kính gửi {{ $tenKhachHang }},</div>
            <div class="intro-text">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản liên kết với địa chỉ email này tại <strong>RoseShop</strong>. Quý khách có thể lựa chọn <strong>một trong hai cách</strong> dưới đây để thiết lập mật khẩu mới:
            </div>

            <!-- Cách 1: Sử dụng mã xác thực -->
            <div class="option-box">
                <div class="option-title">📌 Cách 1: Sử dụng Mã xác thực (6 số)</div>
                <div style="font-size: 13px; color: #4a5568;">
                    Nhập mã xác thực bên dưới tại màn hình Đặt lại mật khẩu:
                </div>
                <div class="code-display">{{ $maXacThuc }}</div>
                <div style="font-size: 12px; color: #718096; text-align: center;">
                    (Sao chép hoặc ghi nhớ mã 6 số này để xác nhận)
                </div>
            </div>

            <!-- Cách 2: Bấm liên kết trực tiếp -->
            <div class="option-box" style="background-color: #f0fdf4; border-color: #bbf7d0;">
                <div class="option-title" style="color: #166534;">🔗 Cách 2: Bấm liên kết đặt lại mật khẩu trực tiếp</div>
                <div style="font-size: 13px; color: #15803d;">
                    Bấm vào nút bên dưới để chuyển thẳng đến màn hình tạo mật khẩu mới:
                </div>
                <div class="btn-wrapper">
                    <a href="{{ $resetUrl }}" class="btn-reset" target="_blank" style="background-color: #16a34a;">
                        🔑 Đặt lại mật khẩu ngay
                    </a>
                </div>
                <div class="raw-link">
                    Nếu không bấm được nút, quý khách vui lòng truy cập đường dẫn sau:<br>
                    <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
                </div>
            </div>

            <div class="notice">
                ⏰ <strong>Thời hạn hiệu lực:</strong> Cả mã xác thực và liên kết trên chỉ có giá trị trong vòng <strong>{{ $expireMinutes }} phút</strong> kể từ thời điểm gửi. Sau khi đặt lại mật khẩu thành công, mã và liên kết sẽ tự động bị vô hiệu hóa.
            </div>

            <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
                🛡️ <strong>Bảo mật:</strong> Nếu quý khách không gửi yêu cầu này, vui lòng bỏ qua email. Mật khẩu hiện tại của quý khách vẫn được giữ an toàn tuyệt đối.
            </p>
        </div>

        <div class="email-footer">
            © {{ date('Y') }} RoseShop TMDT. Mọi quyền được bảo lưu.<br>
            Hotline: 0987.654.321 • Địa chỉ: Hà Nội
        </div>
    </div>
</body>
</html>
