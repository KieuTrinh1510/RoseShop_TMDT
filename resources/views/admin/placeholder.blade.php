@extends('layouts.admin')

@section('content')
<div class="admin-home">
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">{{ $title ?? 'Chức năng' }}</h1>
            <p class="rs-page-subtitle">Hệ thống quản lý RoseShop — tính năng đang trong lộ trình phát triển.</p>
        </div>
        <div class="home-date">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #667085;">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6"/>
                <line x1="8" x2="8" y1="2" y2="6"/>
                <line x1="3" x2="21" y1="10" y2="10"/>
            </svg>
            <span>{{ now()->translatedFormat('l, d/m/Y') }}</span>
        </div>
    </div>

    <div style="background: #ffffff; border-radius: 14px; padding: 48px 32px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05); border: 1px solid #EAECF0; max-width: 580px; margin: 40px auto; text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: 16px; background: #FDEAF3; color: #EC3D8F; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <h2 style="font-size: 20px; font-weight: 700; color: #25324B; margin: 0 0 8px;">{{ $title ?? 'Chức năng' }}</h2>
        <p style="font-size: 14px; color: #475467; margin: 0 0 20px;">Chức năng đang được phát triển.</p>
        <div style="display: inline-block; padding: 6px 16px; background: #FDEAF3; color: #EC3D8F; border-radius: 999px; font-size: 12px; font-weight: 600;">
            Dự kiến ra mắt trong bản cập nhật tới
        </div>
    </div>
</div>
@endsection
