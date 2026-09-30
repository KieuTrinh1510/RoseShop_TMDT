<?php

namespace App\Mail;

use App\Models\KhachHang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public KhachHang $khachHang,
        public string $maXacThuc,
        public string $resetUrl,
        public int $expireMinutes = 60
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RoseShop] Mã xác thực và liên kết đặt lại mật khẩu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'khachHang'     => $this->khachHang,
                'tenKhachHang'  => $this->khachHang->ten_khach_hang ?? 'Quý khách',
                'maXacThuc'     => $this->maXacThuc,
                'resetUrl'      => $this->resetUrl,
                'expireMinutes' => $this->expireMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
