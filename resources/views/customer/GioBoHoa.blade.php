@extends('customer.layouts.customer')
@section('title', '🛒 Giỏ bó hoa – Hoa Tươi Shop')
@section('page-id', 'gio-bo-hoa')

@section('head-styles')
<style>
.gbh-wrap{max-width:1100px;margin:40px auto;padding:0 20px 60px;color:#33282e}
.gbh-head{font:700 30px 'Be Vietnam Pro',sans-serif;margin:0 0 8px}
.gbh-sub{color:#725e67;margin:0 0 24px}
.gbh-list{display:flex;flex-direction:column;gap:16px}
.gbh-card{background:#fff;border:1px solid #eadbe1;border-radius:14px;padding:16px;display:flex;gap:16px;align-items:flex-start}
.gbh-img{width:110px;height:110px;border-radius:10px;object-fit:cover;background:#f8f4f6;flex-shrink:0}
.gbh-info{flex:1;min-width:0}
.gbh-name{font:600 18px 'Be Vietnam Pro',sans-serif;margin:0 0 4px}
.gbh-meta{font-size:13px;color:#725e67;margin-bottom:4px}
.gbh-thiep{font-size:13px;color:#8e3555;margin-top:4px}
.gbh-note{font-size:12px;color:#999;margin-top:6px;font-style:italic}
.gbh-price{text-align:right;flex-shrink:0}
.gbh-price-main{font-size:20px;font-weight:700;color:#8e3555}
.gbh-price-sub{font-size:12px;color:#725e67;margin-top:4px}
.gbh-x{border:0;background:transparent;color:#c66;font-size:20px;cursor:pointer;padding:4px 8px}
.gbh-x:hover{color:#e33}
.gbh-actions{margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.btn-primary{background:#8e3555;color:#fff;border:0;border-radius:10px;padding:13px 28px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.btn-secondary{background:#f3e8ed;color:#78304c;border:0;border-radius:10px;padding:13px 28px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.gbh-empty{padding:40px;background:#f8eef2;border-radius:14px;text-align:center;color:#725e67}
.alert-success{padding:12px 16px;background:#eaf7ee;color:#1f7a3a;border-radius:8px;margin-bottom:16px}
.alert-error{padding:12px 16px;background:#fff0ef;color:#9e2924;border-radius:8px;margin-bottom:16px}
</style>
@endsection

@section('content')
<div class="gbh-wrap">
    <h1 class="gbh-head">🛒 Giỏ bó hoa mẫu</h1>
    <p class="gbh-sub">Bó hoa mẫu được đặt riêng (tách khỏi giỏ sản phẩm đơn lẻ). Bấm ✕ để xóa khỏi giỏ.</p>

    @if(session('success'))
        <div class="alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error">❌ {{ session('error') }}</div>
    @endif

    @if(empty($dsItem))
        <div class="gbh-empty">
            <p style="font-size:48px;margin:0 0 12px;">💐</p>
            <p>Giỏ bó hoa đang trống.</p>
            <a href="{{ route('customer.bo-hoa.he-thong') }}" class="btn-primary" style="margin-top:16px;">Khám phá bó hoa mẫu</a>
        </div>
    @else
        @php
            $tongTatCa = collect($dsItem)->sum('tong');
            $tongBo    = collect($dsItem)->sum('so_luong');
        @endphp

        <div class="gbh-list">
            @foreach($dsItem as $key => $item)
                <div class="gbh-card" data-key="{{ $key }}">
                    <img class="gbh-img"
                         src="{{ \App\Http\Controllers\Admin\BoHoaController::anhMau($item['mau']) }}"
                         alt="{{ $item['mau']->ten_mau }}">
                    <div class="gbh-info">
                        <h3 class="gbh-name">{{ $item['mau']->ten_mau }}</h3>
                        <div class="gbh-meta">
                            {{ $item['mau']->size }} ·
                            {{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$item['mau']->kieu_bo_hoa] ?? $item['mau']->kieu_bo_hoa }}
                            · SL: <strong>{{ $item['so_luong'] }} bó</strong>
                        </div>
                        @if($item['thiep'])
                            <div class="gbh-thiep">
                                💌 Kèm thiệp: <strong>{{ $item['thiep']->ten_san_pham }}</strong>
                                (+{{ number_format($item['thiep']->gia_ban_hien_tai, 0, ',', '.') }}₫/bó)
                            </div>
                        @endif
                        @if($item['loi_nhan'])
                            <div class="gbh-note">📝 Lời nhắn: {{ $item['loi_nhan'] }}</div>
                        @endif
                    </div>
                    <div class="gbh-price">
                        <div class="gbh-price-main">{{ number_format($item['tong'], 0, ',', '.') }}₫</div>
                        <div class="gbh-price-sub">
                            {{ number_format($item['gia_mot_bo'], 0, ',', '.') }}₫ × {{ $item['so_luong'] }}
                        </div>
                    </div>
                    <button class="gbh-x" title="Xóa khỏi giỏ" onclick="xoaBoHoa('{{ $key }}')">✕</button>
                </div>
            @endforeach
        </div>

        <div style="margin-top:24px; text-align:right;">
            <div style="font-size:15px;color:#725e67;">Tổng: <strong>{{ $tongBo }}</strong> bó</div>
            <div style="font-size:24px;font-weight:700;color:#8e3555;margin-top:6px;">
                {{ number_format($tongTatCa, 0, ',', '.') }}₫
            </div>
        </div>

        <div class="gbh-actions">
            <a href="{{ route('customer.bo-hoa.he-thong') }}" class="btn-secondary">← Tiếp tục chọn</a>
            <a href="{{ route('customer.thanh-toan') }}" class="btn-primary">💳 Tiến hành thanh toán</a>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function xoaBoHoa(key) {
    if (!confirm('Xóa bó hoa này khỏi giỏ?')) return;

    fetch('{{ route("customer.bo-hoa.gio.remove") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ key: key })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Không xóa được.');
        }
    })
    .catch(() => alert('Có lỗi xảy ra.'));
}
</script>
@endsection