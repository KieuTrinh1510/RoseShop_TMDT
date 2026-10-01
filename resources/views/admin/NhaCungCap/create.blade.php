@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Thêm nhà cung cấp</h1>
            <p class="rs-page-subtitle">Nhập thông tin đối tác cung ứng mới để bắt đầu nhập hàng vào RoseShop.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('admin.nha-cung-cap') }}" class="ncc-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                <span>Quay lại danh sách</span>
            </a>
        </div>
    </div>

    {{-- Error Summary --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ncc-card">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-building-add"></i>
                        Thông tin nhà cung cấp mới
                    </h2>
                </div>

                <div class="p-4">
                    <form method="POST" action="{{ route('admin.nha-cung-cap.store') }}">
                        @csrf

                        {{-- Tên NCC --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="ten_nha_cung_cap">
                                Tên nhà cung cấp <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="ten_nha_cung_cap"
                                   id="ten_nha_cung_cap"
                                   class="form-control @error('ten_nha_cung_cap') is-invalid @enderror"
                                   placeholder="Ví dụ: Công ty TNHH Hoa Tươi Đà Lạt"
                                   value="{{ old('ten_nha_cung_cap') }}"
                                   maxlength="100"
                                   required>
                            @error('ten_nha_cung_cap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tên đại diện hoặc pháp nhân của đối tác cung ứng.</div>
                        </div>

                        {{-- SĐT & Email --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="so_dien_thoai">
                                    Số điện thoại liên hệ
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-telephone"></i></span>
                                    <input type="text"
                                           name="so_dien_thoai"
                                           id="so_dien_thoai"
                                           class="form-control @error('so_dien_thoai') is-invalid @enderror"
                                           placeholder="10 chữ số (VD: 0987527192)"
                                           value="{{ old('so_dien_thoai') }}"
                                           maxlength="10">
                                    @error('so_dien_thoai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="email">
                                    Địa chỉ Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email"
                                           name="email"
                                           id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="ncc@example.com"
                                           value="{{ old('email') }}"
                                           maxlength="100">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Địa chỉ --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="dia_chi">
                                Địa chỉ văn phòng / nhà vườn
                            </label>
                            <textarea name="dia_chi"
                                      id="dia_chi"
                                      rows="3"
                                      class="form-control @error('dia_chi') is-invalid @enderror"
                                      placeholder="Ví dụ: Lô B2, Làng hoa Vạn Thành, TP. Đà Lạt, Lâm Đồng"
                                      maxlength="500">{{ old('dia_chi') }}</textarea>
                            @error('dia_chi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Trạng thái ghi chú --}}
                        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" style="font-size:13px; color:#475467;">
                            <i class="bi bi-info-circle text-primary fs-5"></i>
                            <div>
                                Nhà cung cấp mới tạo sẽ có trạng thái <strong>Đang hợp tác</strong> và sẵn sàng xuất hiện trong danh mục chọn khi tạo Phiếu nhập hàng mới.
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('admin.nha-cung-cap') }}" class="ncc-btn-secondary">
                                Hủy bỏ
                            </a>
                            <button type="submit" class="ncc-btn-primary">
                                <i class="bi bi-check2-circle"></i>
                                <span>Lưu nhà cung cấp</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
