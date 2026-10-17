@extends('customer.layouts.customer')

@section('title', 'Vi phạm & Thanh toán')
@section('page-id', 'vi-pham')

@section('content')
<div class="container py-5" style="max-width:900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="margin:0;font-weight:700;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i> Vi phạm & Thanh toán
            </h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">
                Danh sách các khoản vi phạm bạn cần thanh toán để mở lại quyền mua hàng COD.
            </p>
        </div>
        <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            ← Về trang chủ
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if($viPhams->isEmpty())
        <div class="text-center py-5" style="background:#fff;border-radius:12px;">
            <div style="font-size:3rem;">🛡️</div>
            <h4 class="mt-2">Bạn không có vi phạm nào</h4>
            <p class="text-muted">Lịch sử mua sắm của bạn hoàn toàn tốt. Cảm ơn bạn đã tin tưởng RoseShop 💐</p>
        </div>
    @else
        <div style="background:#fff;border-radius:12px;padding:16px;">
            <table style="width:100%;">
                <thead>
                    <tr style="border-bottom:2px solid #eee;">
                        <th style="padding:10px;text-align:left;">Mã HĐ</th>
                        <th style="padding:10px;text-align:left;">Lý do</th>
                        <th style="padding:10px;text-align:right;">Số tiền</th>
                        <th style="padding:10px;text-align:center;">Trạng thái</th>
                        <th style="padding:10px;text-align:center;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($viPhams as $vp)
                        @php
                            $daTra = $vp->thanhToanViPham->where('trang_thai', \App\Models\ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN)->isNotEmpty();
                            // Chỉ coi là "đang chờ" khi giao dịch còn trong hạn 15 phút của link VNPay
                            $dangCho = $vp->thanhToanViPham
                                ->where('trang_thai', \App\Models\ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN)
                                ->filter(fn ($t) => $t->created_at && $t->created_at->gt(now()->subMinutes(15)))
                                ->isNotEmpty();
                            // Đơn đã thanh toán trước → không cần hoàn trả
                            $daTraTruoc = $vp->hoaDon?->trang_thai_thanh_toan === 'DA_THANH_TOAN';
                            $soTien = (float) ($vp->hoaDon?->tong_tien ?? 0);
                        @endphp
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 10px;font-family:monospace;font-weight:600;">
                                #HD-{{ str_pad($vp->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td style="padding:12px 10px;font-size:.85rem;color:#475467;">
                                {{ $vp->ly_do }}
                            </td>
                            <td style="padding:12px 10px;text-align:right;font-weight:600;color:#dc2626;">
                                {{ number_format($soTien, 0, ',', '.') }} đ
                            </td>
                            <td style="padding:12px 10px;text-align:center;">
                                @if($daTra)
                                    <span style="color:#16a34a;font-size:.8rem;">✓ Đã thanh toán</span>
                                @elseif($daTraTruoc)
                                    <span style="color:#475467;font-size:.8rem;">Đơn đã thanh toán trước — không cần hoàn trả</span>
                                @elseif($dangCho)
                                    <span style="color:#ea580c;font-size:.8rem;">⏳ Chờ xác nhận</span>
                                @else
                                    <span style="color:#dc2626;font-size:.8rem;">● Chưa thanh toán</span>
                                @endif
                            </td>
                            <td style="padding:12px 10px;text-align:center;">
                                @if(!$daTra && !$daTraTruoc && !$dangCho)
                                    <a href="{{ route('customer.vi-pham.thanh-toan', $vp->ma_vi_pham) }}"
                                       style="background:#dc2626;color:#fff;padding:6px 14px;border-radius:6px;font-size:.8rem;text-decoration:none;font-weight:600;">
                                        Thanh toán
                                    </a>
                                @elseif($dangCho)
                                    <a href="{{ route('customer.vi-pham.thanh-toan', $vp->ma_vi_pham) }}"
                                       style="color:#ea580c;font-size:.8rem;font-weight:600;">
                                        Tiếp tục thanh toán
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection