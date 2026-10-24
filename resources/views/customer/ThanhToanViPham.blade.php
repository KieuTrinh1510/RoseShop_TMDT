@extends('customer.layouts.customer')

@section('title', 'Thanh toán vi phạm')
@section('page-id', 'thanh-toan-vi-pham')

@section('content')
<div class="container py-5" style="max-width:700px;">
    <h2 class="mb-3" style="font-weight:700;color:#dc2626;">
        <i class="fas fa-exclamation-triangle"></i> Thanh toán khoản vi phạm
    </h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin-bottom:20px;">
            <div style="font-size:.85rem;color:#991b1b;">
                Bạn cần thanh toán khoản vi phạm dưới đây để khôi phục quyền mua hàng COD.
            </div>
        </div>

        <table style="width:100%;font-size:.9rem;">
            <tr>
                <td style="padding:8px 0;color:#64748b;">Mã hóa đơn vi phạm</td>
                <td style="padding:8px 0;text-align:right;font-family:monospace;font-weight:600;">
                    #HD-{{ str_pad($viPham->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Lý do</td>
                <td style="padding:8px 0;text-align:right;">{{ $viPham->ly_do }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Ngày ghi nhận</td>
                <td style="padding:8px 0;text-align:right;">{{ $viPham->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr style="border-top:2px solid #f3f4f6;">
                <td style="padding:12px 0;font-weight:700;">Số tiền cần thanh toán</td>
                <td style="padding:12px 0;text-align:right;font-size:1.2rem;font-weight:700;color:#dc2626;">
                    {{ number_format((float) $viPham->hoaDon?->tong_tien, 0, ',', '.') }} đ
                </td>
            </tr>
        </table>

        <div style="margin-top:24px;">
            <form method="POST" action="{{ route('customer.vi-pham.vnpay', $viPham->ma_vi_pham) }}">
                @csrf
                <button type="submit" style="width:100%;background:#dc2626;color:#fff;border:none;
                        padding:14px 20px;border-radius:8px;font-weight:600;font-size:1rem;cursor:pointer;">
                    <i class="fas fa-credit-card"></i> Thanh toán qua VNPay
                </button>
            </form>
        </div>

        <div style="margin-top:16px;font-size:.78rem;color:#64748b;line-height:1.5;">
            <p>🔒 Bạn sẽ được chuyển sang cổng thanh toán VNPay để hoàn tất giao dịch. Sau khi thanh toán thành công, vi phạm sẽ được gỡ bỏ và quyền COD được khôi phục tự động.</p>
        </div>
    </div>

    <a href="{{ route('customer.vi-pham.index') }}" style="display:inline-block;margin-top:16px;color:#64748b;text-decoration:none;font-size:.85rem;">
        ← Quay lại danh sách vi phạm
    </a>
</div>
@endsection