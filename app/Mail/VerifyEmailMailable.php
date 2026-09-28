<?php

namespace App\Mail;

use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NguoiDung $user,
        public string $verificationUrl,
        public string $tenKhachHang = '',
        public int $expireMinutes = 60
    ) {
        if (empty($this->tenKhachHang)) {
            $this->tenKhachHang = $this->user->khachHang?->ten_khach_hang ?? $this->user->ten_dang_nhap;
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RoseShop] Xác thực tài khoản của bạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'user'            => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'tenKhachHang'    => $this->tenKhachHang,
                'expireMinutes'   => $this->expireMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
