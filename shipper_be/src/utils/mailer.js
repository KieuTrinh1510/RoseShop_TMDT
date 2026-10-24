// src/utils/mailer.js
// Gửi email bằng SMTP, đọc cấu hình từ các biến MAIL_* trong .env (cùng tên với Laravel).

const nodemailer = require('nodemailer');
const { taoLoi } = require('./loi');

let transporter = null;

function layTransporter() {
    if (transporter) return transporter;

    const { MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION } = process.env;
    if (!MAIL_HOST || !MAIL_USERNAME || !MAIL_PASSWORD) {
        throw taoLoi(500, 'MAIL_NOT_CONFIGURED', 'Server chưa cấu hình gửi email (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD).');
    }

    const port = Number(MAIL_PORT) || 587;
    const ma = String(MAIL_ENCRYPTION || '').toLowerCase();
    transporter = nodemailer.createTransport({
        host: MAIL_HOST,
        port,
        secure: port === 465 || ma === 'ssl', // 465/SSL: bọc TLS ngay từ đầu
        requireTLS: port !== 465 && ma === 'tls', // 587/TLS: bắt buộc nâng cấp STARTTLS
        auth: { user: MAIL_USERNAME, pass: MAIL_PASSWORD },
    });
    return transporter;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

// Gửi mã 6 số đặt lại mật khẩu cho shipper
async function guiMaDatLaiMatKhau({ email, ten, ma, phut }) {
    const tenHienThi = escapeHtml(ten || 'bạn');
    const from = `"${process.env.MAIL_FROM_NAME || 'RoseShop'}" <${process.env.MAIL_FROM_ADDRESS || process.env.MAIL_USERNAME}>`;

    const html = `
<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #f1dfe7;border-radius:16px;background:#ffffff">
  <h2 style="color:#ec3d8f;margin:0 0 12px">🌸 RoseShop - Đặt lại mật khẩu</h2>
  <p style="color:#3b1f2e;font-size:14px">Xin chào <strong>${tenHienThi}</strong>,</p>
  <p style="color:#3b1f2e;font-size:14px">Bạn vừa yêu cầu đặt lại mật khẩu tài khoản shipper. Nhập mã xác thực dưới đây vào ứng dụng:</p>
  <div style="text-align:center;margin:20px 0">
    <span style="display:inline-block;font-size:32px;letter-spacing:8px;font-weight:700;color:#ec3d8f;background:#fde8f1;padding:12px 24px;border-radius:12px">${escapeHtml(ma)}</span>
  </div>
  <p style="color:#8c6f7e;font-size:13px">Mã có hiệu lực trong <strong>${phut} phút</strong> và chỉ dùng được một lần.</p>
  <p style="color:#8c6f7e;font-size:13px">Nếu bạn không yêu cầu, hãy bỏ qua email này. Mật khẩu của bạn sẽ không bị thay đổi.</p>
</div>`;

    await layTransporter().sendMail({
        from,
        to: email,
        subject: 'RoseShop - Mã đặt lại mật khẩu shipper',
        text: `Mã đặt lại mật khẩu của bạn là ${ma}. Mã có hiệu lực trong ${phut} phút.`,
        html,
    });
}

module.exports = { guiMaDatLaiMatKhau };