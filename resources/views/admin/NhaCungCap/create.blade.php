@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
@endpush

@section('content')
<div class="rs-management-page position-relative">

    {{-- Header trang --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Thêm nhà cung cấp</h1>
                <p class="rs-header-subtitle">Nhập thông tin đối tác cung ứng mới để bắt đầu nhập hàng vào RoseShop</p>
            </div>
        </div>

        <div>
            <a href="{{ route('admin.nha-cung-cap') }}" class="rs-btn-back">
                <i class="bi bi-arrow-left me-1"></i>
                <span>Danh sách nhà cung cấp</span>
            </a>
        </div>
    </div>

    {{-- Error Summary --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Vui lòng kiểm tra lại các trường thông tin:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- BOX POPUP OVERLAY ĐÈ LÊN GIAO DIỆN CHÍNH --}}
    <div class="rs-modal show" style="position: relative !important; background: transparent !important; backdrop-filter: none !important; opacity: 1 !important; visibility: visible !important; padding: 0 !important; z-index: 1 !important;">
        <div class="rs-modal-dialog mx-auto" style="max-width: 760px; transform: none !important; box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important; border: 1px solid #EAECF0 !important;">
            
            <div class="rs-modal-header" style="background: #ffffff !important; border-bottom: 1px solid #EAECF0 !important; padding: 18px 24px !important;">
                <h3 class="rs-modal-title" style="color: #101828 !important; font-size: 16px !important; display: flex !important; align-items: center !important; gap: 8px !important;">
                    <i class="bi bi-building-add" style="color:#EC3D8F;"></i>
                    <span>Thông tin nhà cung cấp mới</span>
                </h3>
            </div>

            <div class="rs-modal-body" style="padding: 24px !important;">
                <form method="POST" id="formCreateNcc" action="{{ route('admin.nha-cung-cap.store') }}">
                    @csrf

                    {{-- Tên NCC --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="ten_nha_cung_cap" style="font-size: 13px; color: #344054;">
                            Tên nhà cung cấp <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="ten_nha_cung_cap"
                               id="ten_nha_cung_cap"
                               class="form-control @error('ten_nha_cung_cap') is-invalid @enderror"
                               placeholder="Ví dụ: Công ty TNHH Hoa Tươi Đà Lạt"
                               value="{{ old('ten_nha_cung_cap') }}"
                               maxlength="100"
                               style="border-radius: 8px; border: 1px solid #D0D5DD; padding: 10px 14px;"
                               required>
                        @error('ten_nha_cung_cap')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- SĐT & Email --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="so_dien_thoai" style="font-size: 13px; color: #344054;">
                                Số điện thoại liên hệ
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="bi bi-telephone"></i></span>
                                <input type="text"
                                       name="so_dien_thoai"
                                       id="so_dien_thoai"
                                       class="form-control @error('so_dien_thoai') is-invalid @enderror"
                                       placeholder="10 chữ số (VD: 0987527192)"
                                       value="{{ old('so_dien_thoai') }}"
                                       maxlength="10"
                                       style="border-radius: 0 8px 8px 0; border-left: none; padding: 10px 14px;">
                                @error('so_dien_thoai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email" style="font-size: 13px; color: #344054;">
                                Địa chỉ Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="bi bi-envelope"></i></span>
                                <input type="email"
                                       name="email"
                                       id="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="ncc@example.com"
                                       value="{{ old('email') }}"
                                       maxlength="100"
                                       style="border-radius: 0 8px 8px 0; border-left: none; padding: 10px 14px;">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Địa chỉ --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="dia_chi" style="font-size: 13px; color: #344054;">
                            Địa chỉ văn phòng / nhà vườn
                        </label>
                        <textarea name="dia_chi"
                                  id="dia_chi"
                                  rows="3"
                                  class="form-control @error('dia_chi') is-invalid @enderror"
                                  placeholder="Ví dụ: Lô B2, Làng hoa Vạn Thành, TP. Đà Lạt, Lâm Đồng"
                                  maxlength="500"
                                  style="border-radius: 8px; border: 1px solid #D0D5DD; padding: 10px 14px;">{{ old('dia_chi') }}</textarea>
                        @error('dia_chi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>

            <div class="rs-modal-footer" style="padding: 16px 24px !important; background: #fafafa !important; border-top: 1px solid #EAECF0 !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
                <a href="{{ route('admin.nha-cung-cap') }}" class="rs-btn-cancel" style="text-decoration: none !important;">
                    Hủy bỏ
                </a>
                <button type="submit" form="formCreateNcc" class="rs-btn-submit">
                    <i class="bi bi-check2-circle me-1"></i>
                    <span>Lưu nhà cung cấp</span>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
