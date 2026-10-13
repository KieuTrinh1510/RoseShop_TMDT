@extends('customer.layouts.customer')
@section('title', 'Tự Thiết Kế Bó Hoa – Custom Mix | RoseShop')
@section('page-id', 'bo-hoa-tu-tao')

@section('head-styles')
<style>
:root {
    --primary-rose: #EC3D8F;
    --primary-rose-hover: #db2777;
    --primary-rose-light: #fdf2f8;
    --primary-rose-border: #fbcfe8;
    --text-dark: #1f2937;
    --text-muted: #6b7280;
    --card-bg: #ffffff;
    --border-light: #e5e7eb;
    --success-green: #059669;
    --danger-red: #dc2626;
    --warning-amber: #d97706;
}

.custom-mix-page {
    max-width: 1320px;
    margin: 24px auto 60px;
    padding: 0 16px;
    font-family: inherit;
    color: var(--text-dark);
}

.page-header {
    margin-bottom: 24px;
}
.page-title {
    font-size: 28px;
    font-weight: 800;
    color: #831843;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.page-desc {
    color: var(--text-muted);
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
}

.custom-mix-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 28px;
    align-items: start;
}

@media (max-width: 1024px) {
    .custom-mix-layout {
        grid-template-columns: 1fr;
    }
}

.builder-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.section-card {
    background: var(--card-bg);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    transition: border-color 0.2s;
}
.section-card:hover {
    border-color: var(--primary-rose-border);
}
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}
.section-title {
    font-size: 17px;
    font-weight: 700;
    color: #831843;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    background: var(--primary-rose-light);
    color: var(--primary-rose);
    border: 1px solid var(--primary-rose-border);
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 640px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 14px;
}
.form-group:last-child {
    margin-bottom: 0;
}
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    font-size: 14px;
    background: #fff;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-control:focus {
    border-color: var(--primary-rose);
    box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.12);
}

/* SEARCH & PICKER */
.picker-container {
    background: #fdfafb;
    border: 1px solid #fce7f3;
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 16px;
}
.picker-search-bar {
    position: relative;
    margin-bottom: 12px;
}
.picker-search-input {
    width: 100%;
    padding: 9px 14px 9px 36px;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    font-size: 13px;
    box-sizing: border-box;
    background: #fff;
}
.picker-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
}
.picker-scroll-list {
    max-height: 250px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-right: 4px;
}
.picker-scroll-list::-webkit-scrollbar {
    width: 5px;
}
.picker-scroll-list::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}

.picker-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #f3f4f6;
    border-radius: 10px;
    transition: background 0.15s, border-color 0.15s;
}
.picker-item:hover:not(.disabled) {
    border-color: var(--primary-rose-border);
    background: #fff5f8;
}
.picker-item.disabled {
    opacity: 0.6;
    background: #f9fafb;
    border-color: #e5e7eb;
}
.picker-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    background: #fdf2f8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    border: 1px solid #fce7f3;
}
.picker-info {
    flex: 1;
    min-width: 0;
}
.picker-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.picker-meta {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.stock-badge {
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 6px;
    font-weight: 600;
}
.stock-available {
    background: #ecfdf5;
    color: #059669;
}
.stock-out {
    background: #fef2f2;
    color: #dc2626;
}
.stock-used {
    background: #fffbeb;
    color: #b45309;
}

.btn-pick {
    background: var(--primary-rose-light);
    color: var(--primary-rose);
    border: 1px solid var(--primary-rose-border);
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.btn-pick:hover:not(:disabled) {
    background: var(--primary-rose);
    color: #fff;
}
.btn-pick:disabled {
    cursor: not-allowed;
    background: #f3f4f6;
    color: #9ca3af;
    border-color: #e5e7eb;
}

/* SELECTED ITEMS TABLE */
.selected-items-box {
    margin-top: 10px;
}
.selected-item-row {
    display: grid;
    grid-template-columns: 44px minmax(0, 1.4fr) 110px minmax(0, 1.2fr) 36px;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    margin-bottom: 8px;
}
@media (max-width: 640px) {
    .selected-item-row {
        grid-template-columns: 44px minmax(0, 1fr) auto;
        gap: 8px;
    }
    .selected-stock-hint {
        grid-column: span 3;
    }
}
.qty-stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    background: #fff;
    overflow: hidden;
}
.qty-btn {
    width: 28px;
    height: 32px;
    border: none;
    background: #f9fafb;
    color: #374151;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qty-btn:hover:not(:disabled) {
    background: #f3f4f6;
}
.qty-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.qty-input {
    width: 44px;
    height: 32px;
    border: none;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    outline: none;
}
.selected-stock-hint {
    font-size: 12px;
    color: var(--text-muted);
}
.btn-delete-row {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #fee2e2;
    background: #fff5f5;
    color: #ef4444;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.btn-delete-row:hover {
    background: #fee2e2;
}

/* MATERIALS SELECTOR */
.material-selector-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}
.material-card {
    border: 1.5px solid var(--border-light);
    border-radius: 12px;
    padding: 10px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}
.material-card:hover:not(.disabled) {
    border-color: var(--primary-rose);
    box-shadow: 0 2px 6px rgba(236, 61, 143, 0.12);
}
.material-card.selected {
    border-color: var(--primary-rose);
    background: #fff7fa;
    box-shadow: 0 0 0 1px var(--primary-rose);
}
.material-card.disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: #f9fafb;
}
.material-card-thumb {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    background: #f8fafc;
    flex-shrink: 0;
}
.material-card-body {
    flex: 1;
    min-width: 0;
}
.material-card-title {
    font-size: 12px;
    font-weight: 600;
    margin: 0 0 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.material-card-meta {
    font-size: 11px;
    color: var(--text-muted);
}

/* IMAGE UPLOAD */
.upload-dropzone {
    border: 2px dashed #f472b6;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    background: #fff7fa;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}
.upload-dropzone:hover {
    background: #fdf2f8;
    border-color: var(--primary-rose);
}
.upload-icon {
    font-size: 28px;
    color: var(--primary-rose);
    margin-bottom: 6px;
}
.upload-preview-grid {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 14px;
}
.upload-thumb-card {
    position: relative;
    width: 90px;
    height: 90px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #fbcfe8;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.upload-thumb-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.btn-remove-thumb {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    border: none;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-remove-thumb:hover {
    background: #dc2626;
}
.upload-disclaimer {
    font-size: 12px;
    color: #9f1239;
    background: #fff1f2;
    border-radius: 8px;
    padding: 8px 12px;
    margin-top: 10px;
    border-left: 3px solid var(--primary-rose);
    line-height: 1.4;
}

/* STICKY SUMMARY */
.summary-column {
    position: sticky;
    top: 85px;
}
.summary-card {
    background: #ffffff;
    border: 1.5px solid #fce7f3;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 6px 20px rgba(236, 61, 143, 0.08);
}
.summary-header {
    border-bottom: 1px solid #fce7f3;
    padding-bottom: 12px;
    margin-bottom: 16px;
}
.summary-title {
    font-size: 18px;
    font-weight: 800;
    color: #831843;
    margin: 0 0 4px;
}
.summary-subtitle {
    font-size: 12px;
    color: var(--text-muted);
}

.progress-container {
    background: #fdf2f8;
    border: 1px solid #fbcfe8;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 16px;
}
.progress-bar-wrap {
    height: 8px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin: 8px 0;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #ec4899, #db2777);
    border-radius: 6px;
    transition: width 0.3s ease;
}

.validation-checklist {
    list-style: none;
    padding: 0;
    margin: 0 0 16px;
    font-size: 12px;
}
.validation-checklist li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    color: var(--text-muted);
}
.validation-checklist li.valid {
    color: var(--success-green);
}
.validation-checklist li.invalid {
    color: var(--danger-red);
}

.price-breakdown-box {
    background: #fafafa;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
    font-size: 13px;
}
.price-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    color: #4b5563;
}
.price-row.total {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #d1d5db;
    font-size: 16px;
    font-weight: 800;
    color: #be185d;
}

.btn-submit-custom {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(219, 39, 119, 0.3);
    transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    text-align: center;
}
.btn-submit-custom:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(219, 39, 119, 0.4);
}
.btn-submit-custom:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.7;
}

.btn-calc-quote {
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid var(--border-light);
    background: #fff;
    color: #4b5563;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 10px;
}
.btn-calc-quote:hover {
    background: #f9fafb;
    border-color: #cbd5e1;
}

.quota-indicator {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
}
</style>
@endsection

@section('content')
@php
    $CONCEPT_LIST = \App\Http\Controllers\Admin\BoHoaController::CONCEPT;
    $KIEU_DANG_LIST = \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG;
    $phuPhiKieuDang = $phuPhi->where('loai', 'KIEU_DANG')->keyBy('gia_tri');
    $phuPhiSize = $phuPhi->where('loai', 'SIZE')->keyBy('gia_tri');
@endphp

<div class="custom-mix-page">
    <div class="page-header">
        <h1 class="page-title">🌸 Tự Thiết Kế Bó Hoa Tươi (Custom Mix)</h1>
        <p class="page-desc">Tự do phối hoa chính, hoa trang trí, giấy gói, ruy băng và phụ kiện theo phong cách riêng của bạn. Hệ thống cập nhật tồn kho và báo giá trực tiếp.</p>
    </div>

    @if(session('error'))
        <div style="padding:14px 18px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; color:#be123c; margin-bottom:20px; font-weight:600;">
            ❌ {{ session('error') }}
        </div>
    @endif

    <form id="customMixForm" method="POST" action="{{ route('customer.bo-hoa.tu-tao.xac-nhan') }}" enctype="multipart/form-data">
        @csrf

        <div class="custom-mix-layout">
            {{-- ═══════════════════════════════════════════
                 LEFT COLUMN: BUILDER
            ═══════════════════════════════════════════ --}}
            <div class="builder-column">

                {{-- SECTION 1: BASIC INFO --}}
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">✨ 1. Thông Tin Cơ Bản</h2>
                        <span class="section-badge">Bắt buộc</span>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Tên bó hoa thiết kế *</label>
                            <input type="text" name="ten_bo_hoa" id="inputTenBo" class="form-control"
                                   required maxlength="100" value="{{ old('ten_bo_hoa', $duLieuCu['ten_bo_hoa'] ?? 'Bó hoa của tôi') }}"
                                   placeholder="Ví dụ: Bó Hồng Tình Yêu, Nắng Ban Mai...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Concept / Chủ đề thiết kế *</label>
                            <select name="concept" id="selectConcept" class="form-control" required>
                                <option value="">-- Chọn concept --</option>
                                @foreach($CONCEPT_LIST as $k => $label)
                                    <option value="{{ $k }}" @selected(old('concept', $duLieuCu['concept'] ?? '') === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Kiểu dáng bó hoa *</label>
                            <select name="kieu_bo_hoa" id="selectKieuDang" class="form-control" required>
                                <option value="">-- Chọn kiểu dáng --</option>
                                @foreach($KIEU_DANG_LIST as $kdKey => $kdLabel)
                                    @php $p = $phuPhiKieuDang->get($kdKey); @endphp
                                    @if($p)
                                        <option value="{{ $p->gia_tri }}"
                                                data-phu-phi="{{ $p->phu_phi }}"
                                                @selected(old('kieu_bo_hoa', $duLieuCu['kieu_bo_hoa'] ?? '') === $p->gia_tri)>
                                            {{ $kdLabel }} (+{{ number_format($p->phu_phi, 0, ',', '.') }}₫)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kích thước (Size) *</label>
                            <select name="size" id="selectSize" class="form-control" required>
                                <option value="">-- Chọn size --</option>
                                @foreach($SO_BONG as $sizeKey => $soBongDefault)
                                    @php $p = $phuPhiSize->get($sizeKey); @endphp
                                    @if($p)
                                        <option value="{{ $p->gia_tri }}"
                                                data-so-bong="{{ $soBongDefault }}"
                                                data-phu-phi="{{ $p->phu_phi }}"
                                                @selected(old('size', $duLieuCu['size'] ?? '') === $p->gia_tri)>
                                            Size {{ $p->gia_tri }} — Định mức: {{ $soBongDefault }} bông (+{{ number_format($p->phu_phi, 0, ',', '.') }}₫)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="max-width:240px;">
                        <label class="form-label">Số lượng bó hoa cần làm *</label>
                        <input type="number" name="so_luong" id="inputSoLuongBo" class="form-control"
                               min="1" max="10" value="{{ old('so_luong', $duLieuCu['so_luong'] ?? 1) }}" required>
                        <small style="color:var(--text-muted); font-size:12px; display:block; margin-top:4px;">Từ 1 đến 10 bó cùng cấu hình.</small>
                    </div>
                </div>

                {{-- SECTION 2: CHỌN HOA (HOA CHÍNH & HOA TRANG TRÍ) --}}
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">🌹 2. Chọn Các Loại Hoa</h2>
                        <span class="section-badge" id="badgeFlowerProgress">0 / 0 bông</span>
                    </div>
                    <p style="font-size:13px; color:var(--text-muted); margin-top:-6px; margin-bottom:16px;">
                        Quy tắc: <strong>Tổng số bông (Hoa chính + Hoa trang trí) = Đúng định mức Size</strong>. Cần ít nhất 1 cành hoa chính. Một loại hoa <strong>không được dùng đồng thời</strong> ở cả hai nhóm.
                    </p>

                    {{-- SUBSECTION: HOA CHÍNH --}}
                    <div style="margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <label style="font-size:14px; font-weight:700; color:#831843;">🌺 Hoa chính (Tối thiểu 1 bông)</label>
                            <span style="font-size:12px; font-weight:600; color:var(--text-muted);" id="mainFlowerCountLabel">Đã chọn: 0 bông</span>
                        </div>

                        {{-- Searchable picker for Main Flowers --}}
                        <div class="picker-container">
                            <div class="picker-search-bar">
                                <span class="picker-search-icon">🔍</span>
                                <input type="text" class="picker-search-input" id="searchMainFlower" placeholder="Tìm kiếm hoa chính...">
                            </div>
                            <div class="picker-scroll-list" id="listMainFlower"></div>
                        </div>

                        {{-- Selected Main Flowers --}}
                        <div class="selected-items-box" id="selectedMainFlowersBox"></div>
                    </div>

                    <hr style="border:none; border-top:1px dashed #fce7f3; margin:20px 0;">

                    {{-- SUBSECTION: HOA TRANG TRÍ --}}
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <label style="font-size:14px; font-weight:700; color:#831843;">🌿 Hoa trang trí / Hoa đệm (Tùy chọn)</label>
                            <span style="font-size:12px; font-weight:600; color:var(--text-muted);" id="decorFlowerCountLabel">Đã chọn: 0 bông</span>
                        </div>

                        {{-- Searchable picker for Decor Flowers --}}
                        <div class="picker-container">
                            <div class="picker-search-bar">
                                <span class="picker-search-icon">🔍</span>
                                <input type="text" class="picker-search-input" id="searchDecorFlower" placeholder="Tìm kiếm hoa trang trí...">
                            </div>
                            <div class="picker-scroll-list" id="listDecorFlower"></div>
                        </div>

                        {{-- Selected Decor Flowers --}}
                        <div class="selected-items-box" id="selectedDecorFlowersBox"></div>
                    </div>
                </div>

                {{-- SECTION 3: VẬT LIỆU CỐ ĐỊNH (GIẤY GÓI, RUY BĂNG, XỐP) --}}
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">🎁 3. Vật Liệu Bó Hoa</h2>
                        <span class="section-badge">Bắt buộc theo Size</span>
                    </div>
                    <p style="font-size:13px; color:var(--text-muted); margin-top:-6px; margin-bottom:16px;">
                        Số lượng giấy gói, ruy băng và xốp được hệ thống cố định chuẩn kỹ thuật theo Size. Vui lòng chọn mỗi loại 1 mẫu phù hợp.
                    </p>

                    {{-- GIẤY GÓI --}}
                    <div style="margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:700; color:#374151;">📄 Giấy gói hoa (1 loại)</span>
                            <span style="font-size:12px; color:var(--primary-rose); font-weight:600;" id="labelQtyGiay">Định mức: 0 tờ/bó</span>
                        </div>
                        <div class="material-selector-grid" id="gridGiayGoi"></div>
                        <input type="hidden" name="giay_goi_selected" id="inputGiayGoi" value="">
                    </div>

                    {{-- RUY BĂNG --}}
                    <div style="margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:700; color:#374151;">🎀 Ruy băng nơ (1 loại)</span>
                            <span style="font-size:12px; color:var(--primary-rose); font-weight:600;" id="labelQtyRuyBang">Định mức: 0 mét/bó</span>
                        </div>
                        <div class="material-selector-grid" id="gridRuyBang"></div>
                        <input type="hidden" name="ruy_bang_selected" id="inputRuyBang" value="">
                    </div>

                    {{-- XỐP --}}
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:700; color:#374151;">🧱 Xốp cắm hoa (1 loại)</span>
                            <span style="font-size:12px; color:var(--primary-rose); font-weight:600;" id="labelQtyXop">Định mức: 0 miếng/bó</span>
                        </div>
                        <div class="material-selector-grid" id="gridXop"></div>
                        <input type="hidden" name="xop_selected" id="inputXop" value="">
                    </div>
                </div>

                {{-- SECTION 4: PHỤ KIỆN TRANG TRÍ --}}
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">🧸 4. Phụ Kiện Cắm Hoa (Tùy chọn)</h2>
                        <span class="section-badge" id="badgeAccessoryQuota">0 / 0 phụ kiện</span>
                    </div>
                    <p style="font-size:13px; color:var(--text-muted); margin-top:-6px; margin-bottom:14px;">
                        Chỉ gồm phụ kiện trang trí dùng trong cắm hoa (đèn LED, bướm đính, tag chữ...). Tối đa theo quy định của Size.
                    </p>

                    <div class="picker-container">
                        <div class="picker-search-bar">
                            <span class="picker-search-icon">🔍</span>
                            <input type="text" class="picker-search-input" id="searchAccessory" placeholder="Tìm kiếm phụ kiện trang trí...">
                        </div>
                        <div class="picker-scroll-list" id="listAccessory"></div>
                    </div>

                    <div class="selected-items-box" id="selectedAccessoriesBox"></div>
                </div>

                {{-- SECTION 5: ẢNH MẪU THAM KHẢO --}}
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">📸 5. Ảnh Mẫu Mong Muốn (Tùy chọn)</h2>
                        <span class="section-badge">Tối đa 3 ảnh</span>
                    </div>

                    <p style="font-size:13px; color:var(--text-muted); margin-top:-6px; margin-bottom:12px;">
                        Bạn có thể tải ảnh bó hoa mong muốn để cửa hàng tham khảo khi thực hiện bó hoa.
                    </p>

                    <div class="upload-dropzone" onclick="document.getElementById('fileUploadInput').click();">
                        <div class="upload-icon">📷</div>
                        <div style="font-size:14px; font-weight:700; color:#831843; margin-bottom:4px;">+ Tải ảnh mẫu lên</div>
                        <div style="font-size:12px; color:var(--text-muted);">
                            Hỗ trợ JPG, JPEG, PNG, WEBP. Tối đa 3 ảnh, mỗi ảnh không quá 5MB.
                        </div>
                        <input type="file" id="fileUploadInput" name="anh_mau[]" multiple accept="image/jpeg,image/png,image/webp,image/jpg" style="display:none;">
                    </div>

                    {{-- Container for retained existing draft images --}}
                    <div id="retainedImagesContainer"></div>

                    {{-- Preview Container --}}
                    <div class="upload-preview-grid" id="uploadPreviewGrid"></div>

                    <div class="upload-disclaimer">
                        💡 <strong>Lưu ý:</strong> Ảnh mẫu chỉ dùng để tham khảo về phong cách và cách phối. Bó hoa thực tế được thực hiện dựa trên thành phần và số lượng đã được xác nhận trong Custom Mix.
                    </div>
                </div>

            </div>

            {{-- ═══════════════════════════════════════════
                 RIGHT COLUMN: STICKY SUMMARY & CTA
            ═══════════════════════════════════════════ --}}
            <div class="summary-column">
                <div class="summary-card">
                    <div class="summary-header">
                        <h3 class="summary-title">Tóm Tắt Bó Hoa</h3>
                        <div class="summary-subtitle" id="summaryBasicInfo">Chưa chọn kiểu dáng & size</div>
                    </div>

                    {{-- PROGRESS BAR QUOTA --}}
                    <div class="progress-container">
                        <div class="quota-indicator">
                            <span>Định mức hoa:</span>
                            <span id="summaryFlowerProgressText">Chưa chọn size</span>
                        </div>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" id="summaryProgressBar" style="width: 0%;"></div>
                        </div>
                        <div style="font-size:11px; color:var(--text-muted);" id="summaryFlowerHint">
                            Vui lòng chọn size để hiển thị định mức.
                        </div>
                    </div>

                    {{-- VALIDATION CHECKLIST --}}
                    <div style="margin-bottom:12px; font-size:13px; font-weight:700; color:#831843;">
                        Tình trạng cấu hình:
                    </div>
                    <ul class="validation-checklist" id="validationChecklist">
                        <li id="checkKieuDang">⚪ Kiểu dáng bó hoa</li>
                        <li id="checkSize">⚪ Size & Định mức</li>
                        <li id="checkFlowerQuota">⚪ Đủ số lượng hoa theo size</li>
                        <li id="checkFlowerNoOverlap">⚪ Không trùng hoa chính & trang trí</li>
                        <li id="checkGiayGoi">⚪ Giấy gói (đủ tồn kho)</li>
                        <li id="checkRuyBang">⚪ Ruy băng (đủ tồn kho)</li>
                        <li id="checkXop">⚪ Xốp cắm hoa (đủ tồn kho)</li>
                        <li id="checkAccessoryQuota">⚪ Phụ kiện trong định mức</li>
                        <li id="checkStockAvailable">⚪ Tất cả thành phần đủ tồn kho</li>
                    </ul>

                    {{-- PRICE BREAKDOWN --}}
                    <div class="price-breakdown-box">
                        <div style="font-size:12px; font-weight:700; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                            Tạm tính thành phần (1 bó):
                        </div>
                        <div class="price-row"><span>Hoa:</span><strong id="priceRowHoa">0₫</strong></div>
                        <div class="price-row"><span>Giấy gói:</span><strong id="priceRowGiay">0₫</strong></div>
                        <div class="price-row"><span>Ruy băng:</span><strong id="priceRowRuyBang">0₫</strong></div>
                        <div class="price-row"><span>Xốp:</span><strong id="priceRowXop">0₫</strong></div>
                        <div class="price-row"><span>Phụ kiện:</span><strong id="priceRowPhuKien">0₫</strong></div>
                        <div class="price-row"><span>Phụ phí Size:</span><strong id="priceRowSize">0₫</strong></div>
                        <div class="price-row"><span>Phụ phí Kiểu dáng:</span><strong id="priceRowKieuDang">0₫</strong></div>

                        <hr style="border:none; border-top:1px dashed #e5e7eb; margin:8px 0;">

                        <div class="price-row" style="font-weight:600;">
                            <span>Giá 1 bó:</span><strong id="priceRowMoiBo" style="color:#db2777;">0₫</strong>
                        </div>
                        <div class="price-row" style="font-size:12px; color:var(--text-muted);">
                            <span>Số lượng bó:</span><span id="priceRowSoLuong">× 1 bó</span>
                        </div>
                        <div class="price-row total">
                            <span>TỔNG CỘNG:</span>
                            <span id="priceRowTongTien">0₫</span>
                        </div>
                    </div>

                    {{-- HIDDEN SUBMISSION CONTAINERS --}}
                    <div id="hiddenThanhPhanContainer"></div>

                    {{-- CTA BUTTONS --}}
                    <button type="submit" class="btn-submit-custom" id="btnSubmitForm" disabled>
                        Xem Trước & Tiếp Tục →
                    </button>

                    <div style="font-size:11px; color:var(--text-muted); text-align:center; margin-top:10px; line-height:1.4;">
                        🔒 Chỉ giữ chỗ nguyên liệu (Reserve) khi bạn hoàn tất đặt hàng tại bước thanh toán.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function() {
    'use strict';

    // SERVER DATA
    const SO_BONG       = @json($SO_BONG);
    const GIAY_GOI_QTY  = @json($GIAY_GOI_QTY);
    const RUY_BANG_QTY  = @json($RUY_BANG_QTY);
    const XOP_QTY       = @json($XOP_QTY);
    const PHU_KIEN_MAX  = @json($PHU_KIEN_MAX);
    const KIEU_DANG_LBL = @json($KIEU_DANG_LIST);
    const PRODUCTS      = @json($nguyenLieu);
    const DU_LIEU_CU    = @json($duLieuCu ?? []);

    const ROLE_FLOWER = ['HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN'];

    // STATE
    const state = {
        kieuDang: '',
        size: '',
        tenBo: 'Bó hoa của tôi',
        concept: '',
        soLuongBo: 1,

        mainFlowers: {},       // ma_san_pham -> qty_per_bouquet
        decorFlowers: {},      // ma_san_pham -> qty_per_bouquet
        selectedGiay: null,    // ma_san_pham
        selectedRuyBang: null, // ma_san_pham
        selectedXop: null,     // ma_san_pham
        accessories: {},       // ma_san_pham -> qty_per_bouquet

        uploadedFiles: [],     // File[]
        retainedImages: [],    // string[] paths
    };

    // DOM ELEMENTS
    const form = document.getElementById('customMixForm');
    const inputTenBo = document.getElementById('inputTenBo');
    const selectConcept = document.getElementById('selectConcept');
    const selectKieuDang = document.getElementById('selectKieuDang');
    const selectSize = document.getElementById('selectSize');
    const inputSoLuongBo = document.getElementById('inputSoLuongBo');

    const searchMainFlower = document.getElementById('searchMainFlower');
    const listMainFlower = document.getElementById('listMainFlower');
    const selectedMainFlowersBox = document.getElementById('selectedMainFlowersBox');
    const mainFlowerCountLabel = document.getElementById('mainFlowerCountLabel');

    const searchDecorFlower = document.getElementById('searchDecorFlower');
    const listDecorFlower = document.getElementById('listDecorFlower');
    const selectedDecorFlowersBox = document.getElementById('selectedDecorFlowersBox');
    const decorFlowerCountLabel = document.getElementById('decorFlowerCountLabel');

    const gridGiayGoi = document.getElementById('gridGiayGoi');
    const gridRuyBang = document.getElementById('gridRuyBang');
    const gridXop = document.getElementById('gridXop');

    const searchAccessory = document.getElementById('searchAccessory');
    const listAccessory = document.getElementById('listAccessory');
    const selectedAccessoriesBox = document.getElementById('selectedAccessoriesBox');
    const badgeAccessoryQuota = document.getElementById('badgeAccessoryQuota');

    const fileUploadInput = document.getElementById('fileUploadInput');
    const uploadPreviewGrid = document.getElementById('uploadPreviewGrid');
    const retainedImagesContainer = document.getElementById('retainedImagesContainer');

    // SUMMARY ELEMENTS
    const summaryBasicInfo = document.getElementById('summaryBasicInfo');
    const badgeFlowerProgress = document.getElementById('badgeFlowerProgress');
    const summaryFlowerProgressText = document.getElementById('summaryFlowerProgressText');
    const summaryProgressBar = document.getElementById('summaryProgressBar');
    const summaryFlowerHint = document.getElementById('summaryFlowerHint');

    const checkKieuDang = document.getElementById('checkKieuDang');
    const checkSize = document.getElementById('checkSize');
    const checkFlowerQuota = document.getElementById('checkFlowerQuota');
    const checkFlowerNoOverlap = document.getElementById('checkFlowerNoOverlap');
    const checkGiayGoi = document.getElementById('checkGiayGoi');
    const checkRuyBang = document.getElementById('checkRuyBang');
    const checkXop = document.getElementById('checkXop');
    const checkAccessoryQuota = document.getElementById('checkAccessoryQuota');
    const checkStockAvailable = document.getElementById('checkStockAvailable');

    const priceRowHoa = document.getElementById('priceRowHoa');
    const priceRowGiay = document.getElementById('priceRowGiay');
    const priceRowRuyBang = document.getElementById('priceRowRuyBang');
    const priceRowXop = document.getElementById('priceRowXop');
    const priceRowPhuKien = document.getElementById('priceRowPhuKien');
    const priceRowSize = document.getElementById('priceRowSize');
    const priceRowKieuDang = document.getElementById('priceRowKieuDang');
    const priceRowMoiBo = document.getElementById('priceRowMoiBo');
    const priceRowSoLuong = document.getElementById('priceRowSoLuong');
    const priceRowTongTien = document.getElementById('priceRowTongTien');

    const btnSubmitForm = document.getElementById('btnSubmitForm');
    const hiddenThanhPhanContainer = document.getElementById('hiddenThanhPhanContainer');

    // HELPER FUNCTIONS
    const fmtMoney = n => Number(n || 0).toLocaleString('vi-VN') + '₫';
    const getProduct = id => PRODUCTS.find(p => Number(p.ma_san_pham) === Number(id));

    // GET PRODUCTS BY CATEGORY
    const getFlowerProducts = () => PRODUCTS.filter(p => ROLE_FLOWER.includes(p.loai_san_pham));
    const getPaperProducts = () => PRODUCTS.filter(p => p.loai_san_pham === 'GIAY_GOI');
    const getRibbonProducts = () => PRODUCTS.filter(p => p.loai_san_pham === 'RUY_BANG');
    const getFoamProducts = () => PRODUCTS.filter(p => p.loai_san_pham === 'MIENG_XOP');
    const getAccessoryProducts = () => PRODUCTS.filter(p => p.loai_san_pham === 'PHU_KIEN_TRANG_TRI');

    // RENDER SEARCHABLE PICKER FOR FLOWERS
    function renderFlowerPicker(container, filterText, isMain) {
        container.innerHTML = '';
        const query = (filterText || '').trim().toLowerCase();
        const flowers = getFlowerProducts().filter(p => !query || p.ten_san_pham.toLowerCase().includes(query));

        if (!flowers.length) {
            container.innerHTML = '<div style="padding:14px; text-align:center; color:#9ca3af; font-size:13px;">Không tìm thấy loại hoa phù hợp.</div>';
            return;
        }

        flowers.forEach(p => {
            const id = p.ma_san_pham;
            const isSelectedHere = isMain ? (state.mainFlowers[id] !== undefined) : (state.decorFlowers[id] !== undefined);
            const isUsedOpposite = isMain ? (state.decorFlowers[id] !== undefined) : (state.mainFlowers[id] !== undefined);
            const available = Number(p.available || 0);
            const isOutOfStock = available <= 0;

            const disabled = isSelectedHere || isUsedOpposite || isOutOfStock;

            let badgeHtml = '';
            let btnText = '+ Chọn';

            if (isSelectedHere) {
                badgeHtml = '<span class="stock-badge stock-available">✓ Đã chọn</span>';
                btnText = 'Đã chọn';
            } else if (isUsedOpposite) {
                const reason = isMain ? 'Đã dùng làm Hoa trang trí' : 'Đã dùng làm Hoa chính';
                badgeHtml = `<span class="stock-badge stock-used">⚠️ ${reason}</span>`;
                btnText = 'Không thể chọn';
            } else if (isOutOfStock) {
                badgeHtml = '<span class="stock-badge stock-out">Hết hàng</span>';
                btnText = 'Hết hàng';
            } else {
                badgeHtml = `<span class="stock-badge stock-available">Còn ${available} cành</span>`;
            }

            const imgHtml = p.hinh_anh
                ? `<img src="/${p.hinh_anh}" class="picker-thumb" alt="${p.ten_san_pham}" onerror="this.outerHTML='<div class=\\'picker-thumb\\'>🌸</div>'">`
                : `<div class="picker-thumb">🌸</div>`;

            const item = document.createElement('div');
            item.className = 'picker-item' + (disabled ? ' disabled' : '');
            item.innerHTML = `
                ${imgHtml}
                <div class="picker-info">
                    <div class="picker-name" title="${p.ten_san_pham}">${p.ten_san_pham}</div>
                    <div class="picker-meta">
                        <span style="font-weight:700; color:#db2777;">${fmtMoney(p.gia_ban_hien_tai)}</span>
                        ${badgeHtml}
                    </div>
                </div>
                <button type="button" class="btn-pick" ${disabled ? 'disabled' : ''}>${btnText}</button>
            `;

            if (!disabled) {
                item.querySelector('.btn-pick').addEventListener('click', () => {
                    if (isMain) {
                        state.mainFlowers[id] = 1;
                    } else {
                        state.decorFlowers[id] = 1;
                    }
                    onStateChange();
                });
            }

            container.appendChild(item);
        });
    }

    // RENDER SELECTED FLOWERS
    function renderSelectedFlowers(container, flowerMap, isMain) {
        container.innerHTML = '';
        const ids = Object.keys(flowerMap);

        if (!ids.length) {
            container.innerHTML = `<div style="padding:10px 14px; background:#fff; border:1px dashed #e5e7eb; border-radius:10px; font-size:13px; color:#9ca3af; text-align:center;">
                Chưa chọn ${isMain ? 'hoa chính' : 'hoa trang trí'}.
            </div>`;
            return;
        }

        ids.forEach(id => {
            const p = getProduct(id);
            if (!p) return;

            const qty = Number(flowerMap[id] || 1);
            const totalNeeded = qty * state.soLuongBo;
            const available = Number(p.available || 0);
            const isEnough = available >= totalNeeded;

            const imgHtml = p.hinh_anh
                ? `<img src="/${p.hinh_anh}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.outerHTML='<div style=\\'width:40px;height:40px;background:#fdf2f8;border-radius:8px;display:flex;align-items:center;justify-content:center;\\'>🌸</div>'">`
                : `<div style="width:40px;height:40px;background:#fdf2f8;border-radius:8px;display:flex;align-items:center;justify-content:center;">🌸</div>`;

            const row = document.createElement('div');
            row.className = 'selected-item-row';
            row.innerHTML = `
                <div>${imgHtml}</div>
                <div style="min-width:0;">
                    <div style="font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${p.ten_san_pham}</div>
                    <div style="font-size:12px; color:var(--text-muted);">${fmtMoney(p.gia_ban_hien_tai)} / cành</div>
                </div>
                <div class="qty-stepper">
                    <button type="button" class="qty-btn btn-dec" ${qty <= 1 ? 'disabled' : ''}>-</button>
                    <input type="number" class="qty-input" value="${qty}" min="1" max="1000">
                    <button type="button" class="qty-btn btn-inc">+</button>
                </div>
                <div class="selected-stock-hint">
                    <div>Cần: <strong>${totalNeeded}</strong> cành (${qty} × ${state.soLuongBo} bó)</div>
                    <div>Khả dụng: <strong>${available}</strong> · ${isEnough ? '<span style="color:#059669;font-weight:600;">✓ Đủ</span>' : '<span style="color:#dc2626;font-weight:700;">✕ Thiếu ' + (totalNeeded - available) + '</span>'}</div>
                </div>
                <div>
                    <button type="button" class="btn-delete-row" title="Xóa loại hoa này">✕</button>
                </div>
            `;

            // EVENT LISTENERS
            const inputEl = row.querySelector('.qty-input');
            row.querySelector('.btn-dec').addEventListener('click', () => {
                if (flowerMap[id] > 1) {
                    flowerMap[id]--;
                    onStateChange();
                }
            });
            row.querySelector('.btn-inc').addEventListener('click', () => {
                flowerMap[id]++;
                onStateChange();
            });
            inputEl.addEventListener('change', () => {
                let v = parseInt(inputEl.value) || 1;
                if (v < 1) v = 1;
                flowerMap[id] = v;
                onStateChange();
            });
            row.querySelector('.btn-delete-row').addEventListener('click', () => {
                delete flowerMap[id];
                onStateChange();
            });

            container.appendChild(row);
        });
    }

    // RENDER FIXED MATERIALS (PAPER, RIBBON, FOAM)
    function renderMaterialSelector(container, products, selectedId, requiredPerBouquet, onSelectCallback) {
        container.innerHTML = '';
        if (!products.length) {
            container.innerHTML = '<div style="font-size:13px; color:#9ca3af;">Không có nguyên liệu phù hợp.</div>';
            return;
        }

        const totalNeeded = (requiredPerBouquet || 0) * state.soLuongBo;

        products.forEach(p => {
            const id = p.ma_san_pham;
            const isSelected = Number(selectedId) === Number(id);
            const available = Number(p.available || 0);
            const isOutOfStock = available <= 0;
            const isEnough = !isSelected || (available >= totalNeeded);

            const card = document.createElement('div');
            card.className = 'material-card' + (isSelected ? ' selected' : '') + (isOutOfStock ? ' disabled' : '');

            const imgHtml = p.hinh_anh
                ? `<img src="/${p.hinh_anh}" class="material-card-thumb" onerror="this.outerHTML='<div class=\\'material-card-thumb\\' style=\\'display:flex;align-items:center;justify-content:center;\\'>📦</div>'">`
                : `<div class="material-card-thumb" style="display:flex;align-items:center;justify-content:center;">📦</div>`;

            let stockText = isOutOfStock
                ? '<span style="color:#dc2626; font-weight:600;">Hết hàng</span>'
                : `Còn ${available}`;

            if (isSelected && requiredPerBouquet > 0) {
                stockText += isEnough
                    ? ` · <span style="color:#059669; font-weight:600;">✓ Đủ (${totalNeeded})</span>`
                    : ` · <span style="color:#dc2626; font-weight:700;">✕ Thiếu ${totalNeeded - available}</span>`;
            }

            card.innerHTML = `
                ${imgHtml}
                <div class="material-card-body">
                    <div class="material-card-title" title="${p.ten_san_pham}">${p.ten_san_pham}</div>
                    <div class="material-card-meta">
                        <span style="font-weight:700; color:#db2777;">${fmtMoney(p.gia_ban_hien_tai)}</span> · ${stockText}
                    </div>
                </div>
            `;

            if (!isOutOfStock) {
                card.addEventListener('click', () => {
                    onSelectCallback(id);
                    onStateChange();
                });
            }

            container.appendChild(card);
        });
    }

    // RENDER ACCESSORIES PICKER
    function renderAccessoryPicker(filterText) {
        listAccessory.innerHTML = '';
        const query = (filterText || '').trim().toLowerCase();
        const accessories = getAccessoryProducts().filter(p => !query || p.ten_san_pham.toLowerCase().includes(query));

        if (!accessories.length) {
            listAccessory.innerHTML = '<div style="padding:14px; text-align:center; color:#9ca3af; font-size:13px;">Không tìm thấy phụ kiện phù hợp.</div>';
            return;
        }

        const maxAccessories = PHU_KIEN_MAX[state.size] || 0;
        let currentTotal = 0;
        Object.values(state.accessories).forEach(v => currentTotal += Number(v || 0));
        const isMaxReached = currentTotal >= maxAccessories;

        accessories.forEach(p => {
            const id = p.ma_san_pham;
            const isSelected = state.accessories[id] !== undefined;
            const available = Number(p.available || 0);
            const isOutOfStock = available <= 0;
            const disabled = isSelected || isOutOfStock || (isMaxReached && !isSelected);

            let badgeHtml = '';
            let btnText = '+ Thêm';

            if (isSelected) {
                badgeHtml = '<span class="stock-badge stock-available">✓ Đã chọn</span>';
                btnText = 'Đã chọn';
            } else if (isOutOfStock) {
                badgeHtml = '<span class="stock-badge stock-out">Hết hàng</span>';
                btnText = 'Hết hàng';
            } else if (isMaxReached) {
                badgeHtml = '<span class="stock-badge stock-used">Đã đạt tối đa</span>';
                btnText = 'Đầy định mức';
            } else {
                badgeHtml = `<span class="stock-badge stock-available">Còn ${available}</span>`;
            }

            const imgHtml = p.hinh_anh
                ? `<img src="/${p.hinh_anh}" class="picker-thumb" alt="${p.ten_san_pham}" onerror="this.outerHTML='<div class=\\'picker-thumb\\'>🧸</div>'">`
                : `<div class="picker-thumb">🧸</div>`;

            const item = document.createElement('div');
            item.className = 'picker-item' + (disabled ? ' disabled' : '');
            item.innerHTML = `
                ${imgHtml}
                <div class="picker-info">
                    <div class="picker-name" title="${p.ten_san_pham}">${p.ten_san_pham}</div>
                    <div class="picker-meta">
                        <span style="font-weight:700; color:#db2777;">${fmtMoney(p.gia_ban_hien_tai)}</span>
                        ${badgeHtml}
                    </div>
                </div>
                <button type="button" class="btn-pick" ${disabled ? 'disabled' : ''}>${btnText}</button>
            `;

            if (!disabled) {
                item.querySelector('.btn-pick').addEventListener('click', () => {
                    state.accessories[id] = 1;
                    onStateChange();
                });
            }

            listAccessory.appendChild(item);
        });
    }

    // RENDER SELECTED ACCESSORIES
    function renderSelectedAccessories() {
        selectedAccessoriesBox.innerHTML = '';
        const ids = Object.keys(state.accessories);

        if (!ids.length) {
            selectedAccessoriesBox.innerHTML = `<div style="padding:10px 14px; background:#fff; border:1px dashed #e5e7eb; border-radius:10px; font-size:13px; color:#9ca3af; text-align:center;">
                Chưa chọn phụ kiện trang trí (tùy chọn).
            </div>`;
            return;
        }

        const maxAccessories = PHU_KIEN_MAX[state.size] || 0;

        ids.forEach(id => {
            const p = getProduct(id);
            if (!p) return;

            const qty = Number(state.accessories[id] || 1);
            const totalNeeded = qty * state.soLuongBo;
            const available = Number(p.available || 0);
            const isEnough = available >= totalNeeded;

            const imgHtml = p.hinh_anh
                ? `<img src="/${p.hinh_anh}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.outerHTML='<div style=\\'width:40px;height:40px;background:#fdf2f8;border-radius:8px;display:flex;align-items:center;justify-content:center;\\'>🧸</div>'">`
                : `<div style="width:40px;height:40px;background:#fdf2f8;border-radius:8px;display:flex;align-items:center;justify-content:center;">🧸</div>`;

            const row = document.createElement('div');
            row.className = 'selected-item-row';
            row.innerHTML = `
                <div>${imgHtml}</div>
                <div style="min-width:0;">
                    <div style="font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${p.ten_san_pham}</div>
                    <div style="font-size:12px; color:var(--text-muted);">${fmtMoney(p.gia_ban_hien_tai)} / cái</div>
                </div>
                <div class="qty-stepper">
                    <button type="button" class="qty-btn btn-dec" ${qty <= 1 ? 'disabled' : ''}>-</button>
                    <input type="number" class="qty-input" value="${qty}" min="1" max="100">
                    <button type="button" class="qty-btn btn-inc">+</button>
                </div>
                <div class="selected-stock-hint">
                    <div>Cần: <strong>${totalNeeded}</strong> (${qty} × ${state.soLuongBo} bó)</div>
                    <div>Khả dụng: <strong>${available}</strong> · ${isEnough ? '<span style="color:#059669;font-weight:600;">✓ Đủ</span>' : '<span style="color:#dc2626;font-weight:700;">✕ Thiếu ' + (totalNeeded - available) + '</span>'}</div>
                </div>
                <div>
                    <button type="button" class="btn-delete-row" title="Xóa phụ kiện này">✕</button>
                </div>
            `;

            // EVENTS
            row.querySelector('.btn-dec').addEventListener('click', () => {
                if (state.accessories[id] > 1) {
                    state.accessories[id]--;
                    onStateChange();
                }
            });
            row.querySelector('.btn-inc').addEventListener('click', () => {
                let currentTotal = 0;
                Object.values(state.accessories).forEach(v => currentTotal += Number(v || 0));
                if (currentTotal < maxAccessories) {
                    state.accessories[id]++;
                    onStateChange();
                } else {
                    alert(`Size ${state.size} chỉ cho phép tối đa ${maxAccessories} phụ kiện / bó.`);
                }
            });
            row.querySelector('.btn-delete-row').addEventListener('click', () => {
                delete state.accessories[id];
                onStateChange();
            });

            selectedAccessoriesBox.appendChild(row);
        });
    }

    // RENDER IMAGE PREVIEWS
    function renderImagePreviews() {
        uploadPreviewGrid.innerHTML = '';
        retainedImagesContainer.innerHTML = '';

        // Retained images from old draft
        state.retainedImages.forEach((img, idx) => {
            const card = document.createElement('div');
            card.className = 'upload-thumb-card';
            card.innerHTML = `
                <img src="${img.url}" alt="${img.original_name || 'Ảnh mẫu'}">
                <button type="button" class="btn-remove-thumb" title="Xóa ảnh này">✕</button>
            `;
            card.querySelector('.btn-remove-thumb').addEventListener('click', () => {
                state.retainedImages.splice(idx, 1);
                renderImagePreviews();
            });
            uploadPreviewGrid.appendChild(card);

            // Hidden input to retain path
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'anh_mau_giu_lai[]';
            hidden.value = img.path;
            retainedImagesContainer.appendChild(hidden);
        });

        // Newly uploaded files
        state.uploadedFiles.forEach((file, idx) => {
            const card = document.createElement('div');
            card.className = 'upload-thumb-card';
            const url = URL.createObjectURL(file);
            card.innerHTML = `
                <img src="${url}" alt="${file.name}">
                <button type="button" class="btn-remove-thumb" title="Xóa ảnh này">✕</button>
            `;
            card.querySelector('.btn-remove-thumb').addEventListener('click', () => {
                state.uploadedFiles.splice(idx, 1);
                URL.revokeObjectURL(url);
                syncFileInput();
                renderImagePreviews();
            });
            uploadPreviewGrid.appendChild(card);
        });
    }

    function syncFileInput() {
        const dt = new DataTransfer();
        state.uploadedFiles.forEach(f => dt.items.add(f));
        fileUploadInput.files = dt.files;
    }

    fileUploadInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        const total = state.retainedImages.length + state.uploadedFiles.length + files.length;
        if (total > 3) {
            alert('Bạn chỉ có thể tải lên tối đa 3 ảnh mẫu tham khảo.');
            return;
        }

        for (const file of files) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
                alert(`File "${file.name}" không hợp lệ. Chỉ chấp nhận JPG, JPEG, PNG, WEBP.`);
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert(`File "${file.name}" vượt quá dung lượng 5MB.`);
                return;
            }
            state.uploadedFiles.push(file);
        }
        syncFileInput();
        renderImagePreviews();
    });

    // BUILD HIDDEN FORM INPUTS FOR SUBMISSION
    function buildHiddenInputs() {
        hiddenThanhPhanContainer.innerHTML = '';
        let idx = 0;

        // HOA CHINH
        Object.entries(state.mainFlowers).forEach(([id, qty]) => {
            appendHiddenThanhPhan(idx++, id, qty, 'HOA_CHINH');
        });

        // HOA TRANG TRI
        Object.entries(state.decorFlowers).forEach(([id, qty]) => {
            appendHiddenThanhPhan(idx++, id, qty, 'HOA_TRANG_TRI');
        });

        // GIAY GOI
        if (state.selectedGiay && state.size) {
            const qty = GIAY_GOI_QTY[state.size] || 0;
            if (qty > 0) appendHiddenThanhPhan(idx++, state.selectedGiay, qty, 'GIAY_GOI');
        }

        // RUY BANG
        if (state.selectedRuyBang && state.size) {
            const qty = RUY_BANG_QTY[state.size] || 0;
            if (qty > 0) appendHiddenThanhPhan(idx++, state.selectedRuyBang, qty, 'RUY_BANG');
        }

        // XOP
        if (state.selectedXop && state.size) {
            const qty = XOP_QTY[state.size] || 0;
            if (qty > 0) appendHiddenThanhPhan(idx++, state.selectedXop, qty, 'XOP');
        }

        // PHU KIEN
        Object.entries(state.accessories).forEach(([id, qty]) => {
            appendHiddenThanhPhan(idx++, id, qty, 'PHU_KIEN_TRANG_TRI');
        });
    }

    function appendHiddenThanhPhan(index, spId, qty, role) {
        hiddenThanhPhanContainer.innerHTML += `
            <input type="hidden" name="thanh_phan[${index}][ma_san_pham]" value="${spId}">
            <input type="hidden" name="thanh_phan[${index}][so_luong]" value="${qty}">
            <input type="hidden" name="thanh_phan[${index}][vai_tro]" value="${role}">
        `;
    }

    // MAIN RECALCULATION & VALIDATION LOGIC
    function onStateChange() {
        // Read form basics
        state.kieuDang = selectKieuDang.value;
        state.size = selectSize.value;
        state.concept = selectConcept.value;
        state.soLuongBo = parseInt(inputSoLuongBo.value) || 1;
        if (state.soLuongBo < 1) state.soLuongBo = 1;
        if (state.soLuongBo > 10) state.soLuongBo = 10;

        const sizeQuota = SO_BONG[state.size] || 0;
        const maxAccessories = PHU_KIEN_MAX[state.size] || 0;

        // Flower counts
        let countMain = 0;
        Object.values(state.mainFlowers).forEach(q => countMain += Number(q || 0));
        let countDecor = 0;
        Object.values(state.decorFlowers).forEach(q => countDecor += Number(q || 0));
        const totalFlower = countMain + countDecor;

        mainFlowerCountLabel.textContent = `Đã chọn: ${countMain} bông`;
        decorFlowerCountLabel.textContent = `Đã chọn: ${countDecor} bông`;

        // Flower progress bar
        if (!state.size || sizeQuota === 0) {
            badgeFlowerProgress.textContent = 'Chưa chọn size';
            summaryProgressBar.style.width = '0%';
            summaryFlowerProgressText.textContent = 'Chưa chọn size';
            summaryFlowerHint.textContent = 'Vui lòng chọn size ở Bước 1 để xác định định mức hoa.';
            summaryFlowerHint.style.color = 'var(--text-muted)';
        } else {
            badgeFlowerProgress.textContent = `${totalFlower} / ${sizeQuota} bông`;
            let pct = Math.min(100, Math.round((totalFlower / sizeQuota) * 100));
            summaryProgressBar.style.width = pct + '%';
            summaryFlowerProgressText.textContent = `${totalFlower} / ${sizeQuota} bông`;

            if (totalFlower < sizeQuota) {
                summaryFlowerHint.textContent = `Còn thiếu ${sizeQuota - totalFlower} bông hoa nữa (định mức: ${sizeQuota} bông).`;
                summaryFlowerHint.style.color = 'var(--warning-amber)';
            } else if (totalFlower > sizeQuota) {
                summaryFlowerHint.textContent = `Đang vượt quá ${totalFlower - sizeQuota} bông hoa (định mức: ${sizeQuota} bông)!`;
                summaryFlowerHint.style.color = 'var(--danger-red)';
            } else {
                summaryFlowerHint.textContent = `✓ Đã đủ định mức ${sizeQuota} bông hoa!`;
                summaryFlowerHint.style.color = 'var(--success-green)';
            }
        }

        // Material quotas display
        const giayQtyPerBouquet = GIAY_GOI_QTY[state.size] || 0;
        const ruyBangQtyPerBouquet = RUY_BANG_QTY[state.size] || 0;
        const xopQtyPerBouquet = XOP_QTY[state.size] || 0;

        document.getElementById('labelQtyGiay').textContent = `Định mức: ${giayQtyPerBouquet} tờ/bó · Cần ${giayQtyPerBouquet * state.soLuongBo} tờ`;
        document.getElementById('labelQtyRuyBang').textContent = `Định mức: ${ruyBangQtyPerBouquet}m/bó · Cần ${ruyBangQtyPerBouquet * state.soLuongBo}m`;
        document.getElementById('labelQtyXop').textContent = `Định mức: ${xopQtyPerBouquet} miếng/bó · Cần ${xopQtyPerBouquet * state.soLuongBo} miếng`;

        // Accessory count
        let countAcc = 0;
        Object.values(state.accessories).forEach(q => countAcc += Number(q || 0));
        badgeAccessoryQuota.textContent = `${countAcc} / max ${maxAccessories}`;

        // CHECKLIST VALIDATION
        let isKieuDangValid = !!state.kieuDang;
        let isSizeValid = !!state.size;
        let isFlowerQuotaValid = sizeQuota > 0 && totalFlower === sizeQuota && countMain >= 1;

        // Mutual exclusion check (intersection must be empty)
        const mainIds = Object.keys(state.mainFlowers);
        const decorIds = Object.keys(state.decorFlowers);
        const hasOverlap = mainIds.some(id => decorIds.includes(id));
        let isFlowerNoOverlapValid = !hasOverlap && countMain >= 1;

        // Material stock checks
        const pGiay = state.selectedGiay ? getProduct(state.selectedGiay) : null;
        let isGiayValid = !!pGiay && Number(pGiay.available || 0) >= (giayQtyPerBouquet * state.soLuongBo);

        const pRuyBang = state.selectedRuyBang ? getProduct(state.selectedRuyBang) : null;
        let isRuyBangValid = !!pRuyBang && Number(pRuyBang.available || 0) >= (ruyBangQtyPerBouquet * state.soLuongBo);

        const pXop = state.selectedXop ? getProduct(state.selectedXop) : null;
        let isXopValid = !!pXop && Number(pXop.available || 0) >= (xopQtyPerBouquet * state.soLuongBo);

        let isAccessoryQuotaValid = countAcc <= maxAccessories;

        // All components stock available check
        let isStockAllOk = true;

        // Check flowers stock
        mainIds.forEach(id => {
            const p = getProduct(id);
            if (!p || Number(p.available || 0) < (Number(state.mainFlowers[id]) * state.soLuongBo)) {
                isStockAllOk = false;
            }
        });
        decorIds.forEach(id => {
            const p = getProduct(id);
            if (!p || Number(p.available || 0) < (Number(state.decorFlowers[id]) * state.soLuongBo)) {
                isStockAllOk = false;
            }
        });
        // Check accessories stock
        Object.entries(state.accessories).forEach(([id, q]) => {
            const p = getProduct(id);
            if (!p || Number(p.available || 0) < (Number(q) * state.soLuongBo)) {
                isStockAllOk = false;
            }
        });
        if (!isGiayValid || !isRuyBangValid || !isXopValid) {
            isStockAllOk = false;
        }

        // UPDATE CHECKLIST UI
        updateCheckItem(checkKieuDang, isKieuDangValid, 'Kiểu dáng bó hoa: ' + (KIEU_DANG_LBL[state.kieuDang] || 'Chưa chọn'));
        updateCheckItem(checkSize, isSizeValid, 'Size & Định mức: ' + (state.size ? `Size ${state.size} (${sizeQuota} bông)` : 'Chưa chọn'));
        if (!state.size || sizeQuota === 0) {
            updateCheckItem(checkFlowerQuota, false, 'Định mức hoa: Vui lòng chọn size trước');
        } else {
            updateCheckItem(checkFlowerQuota, isFlowerQuotaValid, `Định mức hoa: ${totalFlower}/${sizeQuota} bông (Hoa chính: ${countMain})`);
        }
        updateCheckItem(checkFlowerNoOverlap, isFlowerNoOverlapValid, hasOverlap ? '✕ Trùng loại hoa chính & trang trí!' : 'Không trùng hoa chính & trang trí');
        updateCheckItem(checkGiayGoi, isGiayValid, isGiayValid ? 'Giấy gói: Đủ tồn kho' : (state.selectedGiay ? 'Giấy gói: Không đủ tồn kho' : 'Chưa chọn giấy gói'));
        updateCheckItem(checkRuyBang, isRuyBangValid, isRuyBangValid ? 'Ruy băng: Đủ tồn kho' : (state.selectedRuyBang ? 'Ruy băng: Không đủ tồn kho' : 'Chưa chọn ruy băng'));
        updateCheckItem(checkXop, isXopValid, isXopValid ? 'Xốp cắm hoa: Đủ tồn kho' : (state.selectedXop ? 'Xốp: Không đủ tồn kho' : 'Chưa chọn xốp'));
        updateCheckItem(checkAccessoryQuota, isAccessoryQuotaValid, `Phụ kiện: ${countAcc}/${maxAccessories}`);
        updateCheckItem(checkStockAvailable, isStockAllOk, isStockAllOk ? 'Tất cả thành phần đủ tồn kho' : 'Thiếu tồn kho tại một số thành phần');

        // CALCULATE BREAKDOWN PRICE
        let tienHoa = 0;
        mainIds.forEach(id => {
            const p = getProduct(id);
            if (p) tienHoa += Number(p.gia_ban_hien_tai || 0) * Number(state.mainFlowers[id] || 0);
        });
        decorIds.forEach(id => {
            const p = getProduct(id);
            if (p) tienHoa += Number(p.gia_ban_hien_tai || 0) * Number(state.decorFlowers[id] || 0);
        });

        let tienGiay = pGiay ? Number(pGiay.gia_ban_hien_tai || 0) * giayQtyPerBouquet : 0;
        let tienRuyBang = pRuyBang ? Number(pRuyBang.gia_ban_hien_tai || 0) * ruyBangQtyPerBouquet : 0;
        let tienXop = pXop ? Number(pXop.gia_ban_hien_tai || 0) * xopQtyPerBouquet : 0;

        let tienPhuKien = 0;
        Object.entries(state.accessories).forEach(([id, q]) => {
            const p = getProduct(id);
            if (p) tienPhuKien += Number(p.gia_ban_hien_tai || 0) * Number(q || 0);
        });

        const optSize = selectSize.selectedOptions[0];
        const phuPhiSizeVal = optSize ? Number(optSize.dataset.phuPhi || 0) : 0;

        const optKieu = selectKieuDang.selectedOptions[0];
        const phuPhiKieuDangVal = optKieu ? Number(optKieu.dataset.phuPhi || 0) : 0;

        const giaMoiBo = tienHoa + tienGiay + tienRuyBang + tienXop + tienPhuKien + phuPhiSizeVal + phuPhiKieuDangVal;
        const tongTien = giaMoiBo * state.soLuongBo;

        priceRowHoa.textContent = fmtMoney(tienHoa);
        priceRowGiay.textContent = fmtMoney(tienGiay);
        priceRowRuyBang.textContent = fmtMoney(tienRuyBang);
        priceRowXop.textContent = fmtMoney(tienXop);
        priceRowPhuKien.textContent = fmtMoney(tienPhuKien);
        priceRowSize.textContent = fmtMoney(phuPhiSizeVal);
        priceRowKieuDang.textContent = fmtMoney(phuPhiKieuDangVal);
        priceRowMoiBo.textContent = fmtMoney(giaMoiBo);
        priceRowSoLuong.textContent = `× ${state.soLuongBo} bó`;
        priceRowTongTien.textContent = fmtMoney(tongTien);

        summaryBasicInfo.textContent = (KIEU_DANG_LBL[state.kieuDang] || '—') + ' · Size ' + (state.size || '—') + ' · ' + state.soLuongBo + ' bó';

        // OVERALL FORM VALIDITY
        const isOverallValid = isKieuDangValid
            && isSizeValid
            && isFlowerQuotaValid
            && isFlowerNoOverlapValid
            && isGiayValid
            && isRuyBangValid
            && isXopValid
            && isAccessoryQuotaValid
            && isStockAllOk
            && giaMoiBo > 0
            && state.soLuongBo >= 1 && state.soLuongBo <= 10;

        btnSubmitForm.disabled = !isOverallValid;
        if (!isOverallValid) {
            btnSubmitForm.title = 'Vui lòng hoàn tất cấu hình và khắc phục các mục chưa đạt yêu cầu.';
        } else {
            btnSubmitForm.title = 'Bấm để xem trước thiết kế bó hoa của bạn.';
        }

        // RE-RENDER PICKERS & LISTS
        renderFlowerPicker(listMainFlower, searchMainFlower.value, true);
        renderFlowerPicker(listDecorFlower, searchDecorFlower.value, false);
        renderSelectedFlowers(selectedMainFlowersBox, state.mainFlowers, true);
        renderSelectedFlowers(selectedDecorFlowersBox, state.decorFlowers, false);

        renderMaterialSelector(gridGiayGoi, getPaperProducts(), state.selectedGiay, giayQtyPerBouquet, id => { state.selectedGiay = id; document.getElementById('inputGiayGoi').value = id; });
        renderMaterialSelector(gridRuyBang, getRibbonProducts(), state.selectedRuyBang, ruyBangQtyPerBouquet, id => { state.selectedRuyBang = id; document.getElementById('inputRuyBang').value = id; });
        renderMaterialSelector(gridXop, getFoamProducts(), state.selectedXop, xopQtyPerBouquet, id => { state.selectedXop = id; document.getElementById('inputXop').value = id; });

        renderAccessoryPicker(searchAccessory.value);
        renderSelectedAccessories();

        buildHiddenInputs();
    }

    function updateCheckItem(el, isValid, text) {
        el.className = isValid ? 'valid' : 'invalid';
        el.innerHTML = (isValid ? '✓ ' : '✕ ') + text;
    }

    // FORM LEVEL LISTENERS
    selectKieuDang.addEventListener('change', onStateChange);
    selectSize.addEventListener('change', onStateChange);
    selectConcept.addEventListener('change', onStateChange);
    inputSoLuongBo.addEventListener('input', onStateChange);
    inputTenBo.addEventListener('input', () => { state.tenBo = inputTenBo.value; });

    searchMainFlower.addEventListener('input', () => renderFlowerPicker(listMainFlower, searchMainFlower.value, true));
    searchDecorFlower.addEventListener('input', () => renderFlowerPicker(listDecorFlower, searchDecorFlower.value, false));
    searchAccessory.addEventListener('input', () => renderAccessoryPicker(searchAccessory.value));

    form.addEventListener('submit', e => {
        buildHiddenInputs();
        if (btnSubmitForm.disabled) {
            e.preventDefault();
            alert('Cấu hình bó hoa chưa hợp lệ hoặc thiếu tồn kho. Vui lòng kiểm tra lại checklist bên phải!');
            return;
        }
        btnSubmitForm.disabled = true;
        btnSubmitForm.textContent = '⏳ Đang xử lý...';
    });

    // INITIALIZE WITH OLD DRAFT DATA (IF RETURNING)
    if (DU_LIEU_CU) {
        if (DU_LIEU_CU.kieu_bo_hoa) state.kieuDang = DU_LIEU_CU.kieu_bo_hoa;
        if (DU_LIEU_CU.size) state.size = DU_LIEU_CU.size;
        if (DU_LIEU_CU.so_luong) state.soLuongBo = parseInt(DU_LIEU_CU.so_luong) || 1;
        if (DU_LIEU_CU.ten_bo_hoa) state.tenBo = DU_LIEU_CU.ten_bo_hoa;
        if (DU_LIEU_CU.concept) state.concept = DU_LIEU_CU.concept;

        if (Array.isArray(DU_LIEU_CU.thanh_phan)) {
            DU_LIEU_CU.thanh_phan.forEach(tp => {
                const id = tp.ma_san_pham;
                const qty = tp.so_luong || 1;
                if (tp.vai_tro === 'HOA_CHINH') state.mainFlowers[id] = qty;
                else if (tp.vai_tro === 'HOA_TRANG_TRI') state.decorFlowers[id] = qty;
                else if (tp.vai_tro === 'GIAY_GOI') state.selectedGiay = id;
                else if (tp.vai_tro === 'RUY_BANG') state.selectedRuyBang = id;
                else if (tp.vai_tro === 'XOP') state.selectedXop = id;
                else if (tp.vai_tro === 'PHU_KIEN_TRANG_TRI') state.accessories[id] = qty;
            });
        }

        if (Array.isArray(DU_LIEU_CU.anh_mau)) {
            state.retainedImages = DU_LIEU_CU.anh_mau.map(img => ({
                url: img.url,
                path: img.path,
                original_name: img.original_name,
            }));
            renderImagePreviews();
        }
    }

    // Auto select first paper, ribbon, foam if not yet selected and available
    if (!state.selectedGiay) {
        const firstGiay = getPaperProducts().find(p => Number(p.available || 0) > 0);
        if (firstGiay) state.selectedGiay = firstGiay.ma_san_pham;
    }
    if (!state.selectedRuyBang) {
        const firstRuyBang = getRibbonProducts().find(p => Number(p.available || 0) > 0);
        if (firstRuyBang) state.selectedRuyBang = firstRuyBang.ma_san_pham;
    }
    if (!state.selectedXop) {
        const firstXop = getFoamProducts().find(p => Number(p.available || 0) > 0);
        if (firstXop) state.selectedXop = firstXop.ma_san_pham;
    }

    // INITIAL TRIGGER
    onStateChange();
})();
</script>
@endsection