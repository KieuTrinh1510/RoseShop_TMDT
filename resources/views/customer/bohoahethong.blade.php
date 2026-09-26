@extends('customer.layouts.customer')
@section('title', '💐 Bó hoa hệ thống đề xuất – Hoa Tươi Shop')
@section('page-id', 'bo-hoa-he-thong')

@section('head-styles')
<style>
.bh-wrap{max-width:1200px;margin:40px auto;padding:0 20px 60px}
.bh-head{margin-bottom:28px}
.bh-head h1{font:700 34px 'Playfair Display',serif;color:#33282e;margin:0 0 8px}
.bh-head p{color:#725e67;margin:0}
.bh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px}
.bh-card{background:#fff;border:1px solid #eadbe1;border-radius:16px;overflow:hidden;transition:.25s;display:flex;flex-direction:column}
.bh-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(142,53,85,.12)}
.bh-img{height:230px;overflow:hidden;background:#f8f4f6}
.bh-img img{width:100%;height:100%;object-fit:cover;transition:.4s}
.bh-card:hover .bh-img img{transform:scale(1.05)}
.bh-body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:8px;flex:1}
.bh-name{font:600 19px 'Playfair Display',serif;color:#33282e;margin:0}
.bh-meta{font-size:13px;color:#725e67}
.bh-price{font-size:20px;font-weight:700;color:#8e3555;margin-top:auto}
.bh-btn{display:inline-block;text-align:center;background:#8e3555;color:#fff;padding:11px 20px;border-radius:9px;text-decoration:none;font-weight:600;margin-top:12px;transition:.2s}
.bh-btn:hover{background:#7a2d49}
.bh-empty{padding:40px;background:#f8eef2;border-radius:14px;text-align:center;color:#725e67}
.bh-pager{display:flex;justify-content:center;margin-top:36px;gap:10px}
.bh-pager a,.bh-pager span{padding:8px 14px;border:1px solid #e0cfd6;border-radius:8px;text-decoration:none;color:#8e3555}
.bh-pager .active{background:#8e3555;color:#fff;border-color:#8e3555}
</style>
@endsection

@section('content')
<div class="bh-wrap">
    <div class="bh-head">
        <h1>💐 Bó hoa hệ thống đề xuất</h1>
        <p>Những mẫu bó hoa được RoseShop thiết kế sẵn — chọn mẫu ưng ý và đặt ngay.</p>
    </div>
    @auth
    @php $soBoTrongGio = count(session('gio_bo_hoa', [])); @endphp
    <div style="margin-bottom:20px;padding:12px 18px;background:#fbf5f8;border:1px solid #eadbe1;border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="color:#725e67;font-size:14px;">
            💐 Bạn có <strong>{{ $soBoTrongGio }}</strong> bó hoa mẫu trong giỏ
        </span>
        <a href="{{ route('customer.bo-hoa.gio.index') }}"
           style="background:#8e3555;color:#fff;padding:10px 20px;border-radius:9px;text-decoration:none;font-weight:600;font-size:14px;">
            Xem giỏ bó hoa →
        </a>
    </div>
    @endauth

    @if($mauBoHoas->isEmpty())
        <div class="bh-empty">Hiện chưa có mẫu bó hoa nào đang mở bán. Vui lòng quay lại sau.</div>
    @else
        <div class="bh-grid">
            @foreach($mauBoHoas as $mau)
                <article class="bh-card">
                    <div class="bh-img">
                        <img src="{{ \App\Http\Controllers\Admin\BoHoaController::anhMau($mau) }}" alt="{{ $mau->ten_mau }}" loading="lazy">
                    </div>
                    <div class="bh-body">
                        <h3 class="bh-name">{{ $mau->ten_mau }}</h3>
                        <div class="bh-meta">
                            {{ $mau->size }} · {{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$mau->kieu_bo_hoa] ?? $mau->kieu_bo_hoa }}
                        </div>
                        <div class="bh-price">{{ number_format($mau->gia_hien_thi ?? 0, 0, ',', '.') }}₫</div>
                        <a class="bh-btn" href="{{ route('customer.mau-bo-hoa.chi-tiet', $mau->ma_mau_bo_hoa) }}">Xem và mua →</a>
                    </div>
                </article>
            @endforeach
        </div>
        @if($mauBoHoas instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="bh-pager">{{ $mauBoHoas->links() }}</div>
        @endif
    @endif
</div>
@endsection