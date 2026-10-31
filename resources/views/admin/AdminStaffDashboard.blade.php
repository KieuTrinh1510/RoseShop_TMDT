@extends('layouts.admin')

@push('styles')
<style>
    .home-stats {
        display: grid;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 16px !important;
        margin-bottom: 24px;
    }
    @media (max-width: 1400px) {
        .home-stats {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: 992px) {
        .home-stats {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    @media (max-width: 576px) {
        .home-stats {
            grid-template-columns: 1fr !important;
        }
    }
    .home-icon.orange {
        background: #FEF0C7 !important;
        color: #DC6803 !important;
    }
    a.home-card {
        text-decoration: none;
        color: inherit;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    a.home-card:hover {
        text-decoration: none;
        color: inherit;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 24, 40, 0.08);
    }

    /* Toast Cảnh Báo Sản Phẩm Hết Hạn (Tự định kiểu hoàn toàn, không phụ thuộc Bootstrap) */
    .expiry-toast-container {
        position: fixed;
        top: 24px;
        right: 28px;
        z-index: 99999;
        max-width: 450px;
        width: calc(100% - 56px);
        animation: toastSlideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes toastSlideDown {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .expiry-toast-card {
        background: #ffffff;
        border: 1px solid #FDA29B;
        border-left: 5px solid #D92D20;
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 12px 28px -4px rgba(16, 24, 40, 0.18), 0 6px 12px -2px rgba(16, 24, 40, 0.08);
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .expiry-toast-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #FEE4E2;
        color: #D92D20;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
        animation: pulseWarning 2s infinite;
    }
    @keyframes pulseWarning {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(217, 45, 32, 0.4); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(217, 45, 32, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(217, 45, 32, 0); }
    }
    .expiry-toast-content {
        flex: 1;
        min-width: 0;
    }
    .expiry-toast-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .expiry-toast-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .expiry-toast-title {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #101828;
    }
    .expiry-toast-badge {
        background: #FEE4E2;
        color: #B42318;
        border: 1px solid #FECDCA;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
    }
    .expiry-toast-close {
        background: none;
        border: none;
        font-size: 22px;
        line-height: 1;
        color: #98A2B3;
        cursor: pointer;
        padding: 0 4px;
        border-radius: 4px;
        transition: color 0.15s ease, background 0.15s ease;
    }
    .expiry-toast-close:hover {
        color: #101828;
        background: #F2F4F7;
    }
    .expiry-toast-desc {
        margin: 0 0 10px 0;
        font-size: 13px;
        color: #475467;
        line-height: 1.5;
    }
    .expiry-toast-link {
        color: #D92D20;
        font-weight: 700;
        text-decoration: underline;
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .expiry-toast-link:hover {
        color: #B42318;
    }
    .expiry-toast-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .expiry-toast-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #D92D20;
        color: #ffffff !important;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 20px;
        text-decoration: none;
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .expiry-toast-btn-action:hover {
        background: #B42318;
        color: #ffffff !important;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .expiry-toast-btn-dismiss {
        background: none;
        border: none;
        color: #667085;
        font-size: 12px;
        font-weight: 500;
        padding: 4px 8px;
        cursor: pointer;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .expiry-toast-btn-dismiss:hover {
        color: #101828;
        text-decoration: underline;
    }

    /* ─── Store Notifications Bell & Dropdown ─── */
    .rs-page-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
    }
    .rs-notif-dropdown-wrapper {
        position: relative;
    }
    .rs-notif-bell-btn {
        background: #ffffff;
        border: 1px solid var(--rs-border, #E4E7EC);
        height: 40px;
        min-width: 40px;
        padding: 0 11px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--rs-primary-text, #101828);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        position: relative;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        user-select: none;
        outline: none;
    }
    .rs-notif-bell-btn:hover {
        background: #F9FAFB;
        border-color: #D0D5DD;
        color: #101828;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px -1px rgba(16, 24, 40, 0.08);
    }
    .rs-notif-bell-btn.active {
        background: #FFF1F3;
        border-color: #FECDD3;
        color: #E11D48;
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
    }
    .rs-notif-icon-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .rs-notif-badge {
        position: absolute;
        top: -7px;
        right: -9px;
        background: #E11D48;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        min-width: 17px;
        height: 17px;
        line-height: 17px;
        padding: 0 4px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 0 0 2px #ffffff, 0 2px 4px rgba(225, 29, 72, 0.35);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        animation: pulseBadge 2.5s infinite;
    }
    @keyframes pulseBadge {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .rs-notif-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 420px;
        max-width: calc(100vw - 32px);
        background: #ffffff;
        border: 1px solid #E4E7EC;
        border-radius: 16px;
        box-shadow: 0 20px 40px -8px rgba(16, 24, 40, 0.14), 0 8px 16px -4px rgba(16, 24, 40, 0.06);
        z-index: 1050;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: rsDropdownFade 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes rsDropdownFade {
        from { opacity: 0; transform: translateY(-8px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .rs-notif-header {
        padding: 14px 18px 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #F2F4F7;
    }
    .rs-notif-header-title {
        font-size: 14.5px;
        font-weight: 700;
        color: #101828;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rs-notif-count-pill {
        background: #FEE4E2;
        color: #B42318;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
    }
    .rs-notif-mark-read-btn {
        background: none;
        border: none;
        font-size: 12px;
        font-weight: 600;
        color: #E11D48;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 6px;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .rs-notif-mark-read-btn:hover {
        background: #FFF1F3;
    }
    .rs-notif-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px 10px 16px;
        border-bottom: 1px solid #F2F4F7;
        background: #FCFCFD;
        overflow-x: auto;
    }
    .rs-notif-tab {
        background: #ffffff;
        border: 1px solid #E4E7EC;
        color: #667085;
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 14px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .rs-notif-tab:hover {
        background: #F9FAFB;
        color: #344054;
    }
    .rs-notif-tab.active {
        background: #FFF1F3;
        border-color: #FECDD3;
        color: #E11D48;
    }
    .rs-notif-list {
        max-height: 380px;
        overflow-y: auto;
        padding: 4px 0;
    }
    .rs-notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid #F2F4F7;
        text-decoration: none !important;
        color: inherit;
        transition: background 0.15s ease;
        position: relative;
    }
    .rs-notif-item:last-child {
        border-bottom: none;
    }
    .rs-notif-item:hover {
        background: #F9FAFB;
    }
    .rs-notif-item.is-read {
        opacity: 0.72;
    }
    .rs-notif-item.is-read:hover {
        opacity: 1;
    }
    .rs-notif-icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .rs-notif-body {
        flex: 1;
        min-width: 0;
    }
    .rs-notif-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 3px;
    }
    .rs-notif-category-badge {
        font-size: 10.5px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 6px;
        line-height: 1.4;
    }
    .rs-notif-time {
        font-size: 11px;
        color: #98A2B3;
        white-space: nowrap;
    }
    .rs-notif-unread-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #E11D48;
        display: inline-block;
        margin-left: 5px;
        flex-shrink: 0;
    }
    .rs-notif-item.is-read .rs-notif-unread-dot {
        display: none !important;
    }
    .rs-notif-item-title {
        font-size: 13px;
        font-weight: 700;
        color: #101828;
        margin: 0 0 3px 0;
        line-height: 1.35;
    }
    .rs-notif-item-msg {
        font-size: 12px;
        color: #475467;
        margin: 0 0 5px 0;
        line-height: 1.4;
    }
    .rs-notif-item-link-text {
        font-size: 11.5px;
        font-weight: 600;
        color: #E11D48;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .rs-notif-footer {
        padding: 10px 16px;
        background: #FCFCFD;
        border-top: 1px solid #F2F4F7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }
    .rs-notif-footer a {
        color: #475467;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .rs-notif-footer a:hover {
        color: #E11D48;
        text-decoration: underline;
    }
    .rs-notif-empty {
        padding: 36px 20px;
        text-align: center;
        color: #98A2B3;
    }
</style>
@endpush

@section('content')

    <div class="admin-home">

        {{-- Popup Thông báo Sản phẩm hết hạn / sắp hết hạn --}}
        @if(($canhBaoSapHetHan ?? 0) > 0 && !empty($thongBaoHetHan))
            <div id="toastCanhBaoHetHan" class="expiry-toast-container" style="display: none;">
                <div class="expiry-toast-card">
                    <div class="expiry-toast-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="expiry-toast-content">
                        <div class="expiry-toast-header">
                            <div class="expiry-toast-title-row">
                                <span class="expiry-toast-title">Cảnh báo sản phẩm hết hạn</span>
                                <span class="expiry-toast-badge">{{ $canhBaoSapHetHan }} cần xử lý</span>
                            </div>
                            <button type="button" class="expiry-toast-close" onclick="dongThongBaoHetHan()" aria-label="Đóng" title="Đóng thông báo">&times;</button>
                        </div>
                        <div class="expiry-toast-desc">
                            Hôm nay có 
                            @php
                                $parts = [];
                                if (($soBoHetHanHomNay ?? 0) > 0) $parts[] = '<strong>' . $soBoHetHanHomNay . ' bó hoa hết hạn</strong>';
                                if (($soBoSapHet ?? 0) > 0) $parts[] = '<strong>' . $soBoSapHet . ' bó hoa sắp hết hạn</strong>';
                                if (($soLoHetHanHomNay ?? 0) > 0) $parts[] = '<strong>' . $soLoHetHanHomNay . ' loại hoa hết hạn</strong>';
                                if (($soLoSapHet ?? 0) > 0) $parts[] = '<strong>' . $soLoSapHet . ' hoa sắp hết hạn</strong>';
                            @endphp
                            {!! implode(', ', $parts) !!}, 
                            <a href="{{ route('admin.san-pham-het-han') }}" class="expiry-toast-link">
                                bấm vào đây để xử lý
                            </a>
                        </div>
                        <div class="expiry-toast-actions">
                            <a href="{{ route('admin.san-pham-het-han') }}" class="expiry-toast-btn-action">
                                <span>Xử lý ngay</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                            <button type="button" class="expiry-toast-btn-dismiss" onclick="dongThongBaoHetHan()">
                                Đóng (x)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="rs-page-header">
            <div class="rs-page-header-text">
                <h1 class="rs-page-title">Tổng quan cửa hàng</h1>
                <p class="rs-page-subtitle">Theo dõi tổng quan hoạt động của RoseShop.</p>
            </div>

            <div class="rs-page-header-actions">
                {{-- Nút thông báo cửa hàng (Bên trái lịch) --}}
                <div class="rs-notif-dropdown-wrapper" id="storeNotifWrapper">
                    <button type="button" id="btnStoreNotifications" class="rs-notif-bell-btn" onclick="toggleStoreNotifications(event)" aria-label="Thông báo cửa hàng" title="Xem tất cả thông báo cửa hàng">
                        <span class="rs-notif-icon-wrap">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span id="rsNotifBadge" class="rs-notif-badge" style="display: none;">0</span>
                        </span>
                        <!-- <span class="rs-notif-btn-label">Thông báo</span> -->
                    </button>

                    {{-- Dropdown bảng thông báo cửa hàng --}}
                    <div id="rsStoreNotifMenu" class="rs-notif-menu" style="display: none;">
                        <div class="rs-notif-header">
                            <div class="rs-notif-header-title">
                                <span>Thông báo cửa hàng</span>
                                <span id="rsNotifCountPill" class="rs-notif-count-pill">0 mới</span>
                            </div>
                            <button type="button" class="rs-notif-mark-read-btn" onclick="markAllNotificationsAsRead()" title="Đánh dấu tất cả là đã đọc">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Đã đọc tất cả</span>
                            </button>
                        </div>

                        {{-- Bộ lọc tab thông báo --}}
                        <div class="rs-notif-tabs">
                            <button type="button" class="rs-notif-tab active" data-filter="all" onclick="filterNotifTab('all', this)">
                                Tất cả ({{ count($thongBaoCuaHang ?? []) }})
                            </button>
                            <button type="button" class="rs-notif-tab" data-filter="order" onclick="filterNotifTab('order', this)">
                                Đơn hàng ({{ count(array_filter($thongBaoCuaHang ?? [], fn($n) => ($n['category'] ?? '') === 'order')) }})
                            </button>
                            <button type="button" class="rs-notif-tab" data-filter="stock" onclick="filterNotifTab('stock', this)">
                                Kho hàng ({{ count(array_filter($thongBaoCuaHang ?? [], fn($n) => ($n['category'] ?? '') === 'stock')) }})
                            </button>
                            <button type="button" class="rs-notif-tab" data-filter="expiry" onclick="filterNotifTab('expiry', this)">
                                Hạn dùng ({{ count(array_filter($thongBaoCuaHang ?? [], fn($n) => ($n['category'] ?? '') === 'expiry')) }})
                            </button>
                        </div>

                        {{-- Danh sách thông báo --}}
                        <div class="rs-notif-list" id="rsNotifList">
                            @forelse($thongBaoCuaHang ?? [] as $tb)
                                <a href="{{ $tb['link'] ?? '#' }}" class="rs-notif-item" data-id="{{ $tb['id'] }}" data-category="{{ $tb['category'] }}" onclick="handleNotifClick(event, '{{ $tb['id'] }}', '{{ $tb['link'] }}')">
                                    <div class="rs-notif-icon-circle" style="background: {{ $tb['icon_bg'] }}; color: {{ $tb['icon_color'] }};">
                                        @if(($tb['icon_type'] ?? '') === 'shopping_bag')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'check_circle')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'trending_up')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'arrow_down_circle')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="8 12 12 16 16 12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'alert_triangle')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'shield_alert')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        @elseif(($tb['icon_type'] ?? '') === 'hourglass' || ($tb['icon_type'] ?? '') === 'flower')
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>
                                        @else
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                        @endif
                                    </div>
                                    <div class="rs-notif-body">
                                        <div class="rs-notif-meta-row">
                                            <span class="rs-notif-category-badge" style="{{ $tb['badge_style'] ?? '' }}">
                                                {{ $tb['badge'] ?? 'Thông báo' }}
                                            </span>
                                            <div style="display:flex; align-items:center;">
                                                <span class="rs-notif-time">{{ $tb['time'] ?? '' }}</span>
                                                <span class="rs-notif-unread-dot" id="dot_{{ $tb['id'] }}"></span>
                                            </div>
                                        </div>
                                        <h5 class="rs-notif-item-title">{{ $tb['title'] ?? '' }}</h5>
                                        <p class="rs-notif-item-msg">{{ $tb['message'] ?? '' }}</p>
                                        <span class="rs-notif-item-link-text">
                                            {{ $tb['link_text'] ?? 'Chi tiết' }} &rarr;
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="rs-notif-empty">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#D0D5DD" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                    <p style="margin:0; font-size:13px; font-weight:600; color:#475467;">Không có thông báo mới</p>
                                    <p style="margin:4px 0 0 0; font-size:12px; color:#98A2B3;">Mọi hoạt động của cửa hàng sẽ xuất hiện tại đây.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="rs-notif-footer">
                            <a href="{{ route('hoa-don.index') }}">Xem danh sách đơn hàng</a>
                            <a href="{{ route('admin.ton-kho') }}">Kiểm tra kho</a>
                        </div>
                    </div>
                </div>

                {{-- Hộp ngày hôm nay --}}
                <div class="home-date">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" style="color: #667085;">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                        <line x1="16" x2="16" y1="2" y2="6" />
                        <line x1="8" x2="8" y1="2" y2="6" />
                        <line x1="3" x2="21" y1="10" y2="10" />
                    </svg>
                    <span>Hôm nay: {{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        <div class="home-stats">
            <div class="home-card">
                <div class="home-icon pink">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="m7.5 4.27 9 5.15" />
                        <path
                            d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                        <path d="m3.3 7 8.7 5 8.7-5" />
                        <path d="M12 22V12" />
                    </svg>
                </div>
                <div>
                    <p>Tổng sản phẩm</p>
                    <h2>{{ number_format($tongSanPham ?? 0) }}</h2>
                </div>
            </div>

            <div class="home-card">
                <div class="home-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1" />
                        <circle cx="19" cy="21" r="1" />
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                    </svg>
                </div>
                <div>
                    <p>Tổng đơn hàng</p>
                    <h2>{{ number_format($tongDonHang ?? 0) }}</h2>
                </div>
            </div>

            <div class="home-card">
                <div class="home-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" x2="12" y1="2" y2="22" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
                <div>
                    <p>Doanh thu hôm nay</p>
                    <h2>{{ number_format($doanhThuHomNay ?? 0, 0, ',', '.') }}đ</h2>
                </div>
            </div>

            <div class="home-card">
                <div class="home-icon purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <div>
                    <p>Tổng khách hàng</p>
                    <h2>{{ number_format($tongKhachHang ?? 0) }}</h2>
                </div>
            </div>

            <a href="{{ route('admin.san-pham-het-han') }}" class="home-card text-decoration-none" title="Xem tất cả sản phẩm hết hạn (gồm hoa tươi & bó hoa)">
                <div class="home-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 22h14" />
                        <path d="M5 2h14" />
                        <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
                        <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                    </svg>
                </div>
                <div>
                    <p>Cảnh báo sắp hết hạn</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <h2>{{ number_format($canhBaoSapHetHan ?? 0) }}</h2>
                        @if(($canhBaoSapHetHan ?? 0) > 0)
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:10px; padding:2px 6px; font-weight:600; border-radius:10px;">
                                Cần xử lý
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="home-main">

            <div class="home-panel chart-panel">
                <div class="panel-title d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h3 class="m-0" style="font-size: 16px; font-weight: 700; color: #1f2937;">Doanh thu</h3>
                        <p class="m-0 mt-1" style="font-size: 13px; color: #64748b;">
                            Tổng kỳ: <strong id="revenueTotalDisplay" style="color: #EC3D8F; font-size: 14px; font-weight: 700;">{{ number_format($tongDoanhThuKy ?? 0, 0, ',', '.') }}đ</strong>
                        </p>
                    </div>

                    <select id="revenueRange" class="dashboard-select" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; border: 1px solid #D0D5DD; background-color: #fff; cursor: pointer; color: #344054; font-weight: 500; outline: none;">
                        <option value="today" {{ ($range ?? '7days') == 'today' ? 'selected' : '' }}>Hôm nay</option>
                        <option value="7days" {{ ($range ?? '7days') == '7days' ? 'selected' : '' }}>7 ngày qua</option>
                        <option value="30days" {{ ($range ?? '7days') == '30days' ? 'selected' : '' }}>30 ngày qua</option>
                        <option value="month" {{ ($range ?? '7days') == 'month' ? 'selected' : '' }}>Tháng này</option>
                        <option value="year" {{ ($range ?? '7days') == 'year' ? 'selected' : '' }}>Năm nay</option>
                    </select>
                </div>

                <div class="revenue-chart-container" style="position: relative; height: 230px; width: 100%;">
                    <canvas id="revenueChartCanvas"></canvas>
                </div>
            </div>

            <div class="home-panel">
                <div class="panel-title">
                    <h3>Đơn hàng chờ xử lý</h3>
                    <a href="{{ route('don-hang.index', ['tab' => 'pending', 'page' => 1]) }}" class="mini-link">Xem tất cả</a>
                </div>

                <div class="order-list">
                    @forelse($donChoXuLy as $order)
                        <div class="order-item">
                            <div class="order-icon">🧾</div>

                            <div>
                                <strong>HD{{ $order->ma_hoa_don }}</strong>
                                <p>{{ $order->ten_khach_hang ?? 'Khách hàng' }}</p>
                            </div>

                            <span>
                                {{ $order->ngay_dat ? \Carbon\Carbon::parse($order->ngay_dat)->format('d/m/Y') : 'Chưa có ngày' }}
                            </span>

                            <em>{{ $order->trang_thai }}</em>
                        </div>
                    @empty
                        <p>Không có đơn hàng chờ xử lý.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <div class="home-bottom">

            <div class="home-panel">
                <div class="panel-title">
                    <h3>Top 5 sản phẩm bán chạy</h3>
                    <a href="{{ route('bao-cao.san-pham') }}" class="mini-link">Xem tất cả</a>
                </div>

                <table class="home-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Sản phẩm</th>
                            <th>Đã bán</th>
                            <th>Doanh thu</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($topSanPham as $index => $sp)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $sp->ten_san_pham }}</td>
                                <td>{{ number_format($sp->tong_ban ?? 0) }}</td>
                                <td>{{ number_format($sp->doanh_thu ?? 0, 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Chưa có dữ liệu bán hàng</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="home-panel">
                <div class="panel-title">
                    <h3>Sản phẩm sắp hết hàng</h3>
                    <a href="{{ route('san-pham.index') }}" class="mini-link">Xem tất cả</a>
                </div>

                <table class="home-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-center">Tồn kho</th>
                            <th class="text-center">Hạn sử dụng</th>
                            <th class="text-center">Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($sanPhamSapHet as $sp)
                            <tr>
                                <td>{{ $sp->ten_san_pham }}</td>
                                <td class="text-center fw-bold">{{ number_format($sp->so_luong ?? 0) }}</td>
                                <td class="text-center" style="white-space: nowrap;">
                                    @if($sp->ngay_het_han)
                                        @php
                                            $hsd = \Carbon\Carbon::parse($sp->ngay_het_han)->startOfDay();
                                            $today = \Carbon\Carbon::today();
                                            $diffDays = $today->diffInDays($hsd, false);
                                        @endphp
                                        @if($diffDays < 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size:11px; padding:3px 7px;" title="Lô hoa đã quá hạn {{ abs($diffDays) }} ngày">
                                                {{ $hsd->format('d/m/Y') }} (Quá hạn)
                                            </span>
                                        @elseif($diffDays == 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size:11px; padding:3px 7px;" title="Hết hạn hôm nay">
                                                {{ $hsd->format('d/m/Y') }} (Hôm nay)
                                            </span>
                                        @elseif($diffDays <= 2)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size:11px; padding:3px 7px;" title="Sắp hết hạn trong {{ $diffDays }} ngày">
                                                {{ $hsd->format('d/m/Y') }} (Còn {{ $diffDays }}n)
                                            </span>
                                        @else
                                            <span class="text-dark fw-medium" style="font-size:12px;">
                                                {{ $hsd->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic" style="font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(($sp->so_luong ?? 0) == 0)
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">Hết hàng</span>
                                    @else
                                        <span class="low-stock">Sắp hết</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Không có sản phẩm sắp hết hàng</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <script>
        document.getElementById('revenueRange')?.addEventListener('change', function () {
            window.location.href = "{{ route('admin.dashboard') }}?range=" + this.value;
        });

        // Hiển thị thông báo sản phẩm hết hạn khi vừa đăng nhập / vào dashboard
        document.addEventListener('DOMContentLoaded', function () {
            const isFreshLogin = @json(session('vua_dang_nhap') ? true : false);
            if (isFreshLogin) {
                sessionStorage.removeItem('dismissed_expiry_alert');
            }

            const isDismissed = sessionStorage.getItem('dismissed_expiry_alert');
            const toastEl = document.getElementById('toastCanhBaoHetHan');
            if (toastEl && !isDismissed) {
                setTimeout(() => {
                    toastEl.style.display = 'block';
                }, 350);
            }
        });

        function dongThongBaoHetHan() {
            const toastEl = document.getElementById('toastCanhBaoHetHan');
            if (toastEl) {
                toastEl.style.transition = 'all 0.3s ease';
                toastEl.style.opacity = '0';
                toastEl.style.transform = 'translateY(-15px) scale(0.95)';
                setTimeout(() => {
                    toastEl.style.display = 'none';
                }, 300);
                sessionStorage.setItem('dismissed_expiry_alert', 'true');
            }
        }

        // ─────────────────────────────────────────────────────────────
        // QUẢN LÝ THÔNG BÁO CỬA HÀNG (BELL DROPDOWN & READ STATE)
        // ─────────────────────────────────────────────────────────────
        const STORAGE_KEY_NOTIFS = 'rs_read_store_notifs_v1';

        function getReadNotifIds() {
            try {
                return JSON.parse(localStorage.getItem(STORAGE_KEY_NOTIFS)) || [];
            } catch (e) {
                return [];
            }
        }

        function saveReadNotifIds(ids) {
            try {
                localStorage.setItem(STORAGE_KEY_NOTIFS, JSON.stringify(ids));
            } catch (e) {}
        }

        function updateNotifBadges() {
            const readIds = getReadNotifIds();
            const items = document.querySelectorAll('.rs-notif-item');
            let unreadCount = 0;

            items.forEach(item => {
                const id = item.dataset.id;
                const dot = document.getElementById('dot_' + id);
                if (readIds.includes(id)) {
                    item.classList.add('is-read');
                    if (dot) dot.style.display = 'none';
                } else {
                    item.classList.remove('is-read');
                    if (dot) dot.style.display = 'inline-block';
                    unreadCount++;
                }
            });

            const badgeEl = document.getElementById('rsNotifBadge');
            const pillEl = document.getElementById('rsNotifCountPill');

            if (badgeEl) {
                if (unreadCount > 0) {
                    badgeEl.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badgeEl.style.display = 'inline-flex';
                } else {
                    badgeEl.style.display = 'none';
                }
            }

            if (pillEl) {
                pillEl.textContent = unreadCount > 0 ? (unreadCount + ' mới') : '0 mới';
            }
        }

        function toggleStoreNotifications(e) {
            e.stopPropagation();
            const menu = document.getElementById('rsStoreNotifMenu');
            const btn = document.getElementById('btnStoreNotifications');
            if (!menu) return;

            const isOpen = (menu.style.display === 'flex' || menu.style.display === 'block');
            if (isOpen) {
                menu.style.display = 'none';
                btn?.classList.remove('active');
            } else {
                menu.style.display = 'flex';
                btn?.classList.add('active');
            }
        }

        function filterNotifTab(category, btn) {
            // Update active pill
            document.querySelectorAll('.rs-notif-tab').forEach(t => t.classList.remove('active'));
            if (btn) btn.classList.add('active');

            // Filter items
            const items = document.querySelectorAll('.rs-notif-item');
            let visibleCount = 0;

            items.forEach(item => {
                const itemCat = item.dataset.category;
                if (category === 'all' || itemCat === category) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function markAllNotificationsAsRead() {
            const items = document.querySelectorAll('.rs-notif-item');
            const allIds = Array.from(items).map(item => item.dataset.id).filter(Boolean);
            saveReadNotifIds(allIds);
            updateNotifBadges();
        }

        function handleNotifClick(e, id, url) {
            const readIds = getReadNotifIds();
            if (!readIds.includes(id)) {
                readIds.push(id);
                saveReadNotifIds(readIds);
                updateNotifBadges();
            }
        }

        // Tự động khởi tạo badge và lắng nghe sự kiện click ngoài menu
        document.addEventListener('DOMContentLoaded', function () {
            updateNotifBadges();

            document.addEventListener('click', function (event) {
                const wrapper = document.getElementById('storeNotifWrapper');
                const menu = document.getElementById('rsStoreNotifMenu');
                const btn = document.getElementById('btnStoreNotifications');

                if (wrapper && !wrapper.contains(event.target)) {
                    if (menu) menu.style.display = 'none';
                    if (btn) btn.classList.remove('active');
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const menu = document.getElementById('rsStoreNotifMenu');
                    const btn = document.getElementById('btnStoreNotifications');
                    if (menu) menu.style.display = 'none';
                    if (btn) btn.classList.remove('active');
                }
            });
        });
    </script>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('revenueChartCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const initialLabels = @json($chartLabels ?? []);
        const initialValues = @json($chartValues ?? []);

        // Gradient nền vùng bên dưới đường biểu đồ
        let gradient = null;
        try {
            gradient = ctx.createLinearGradient(0, 0, 0, 230);
            gradient.addColorStop(0, 'rgba(236, 61, 143, 0.22)');
            gradient.addColorStop(1, 'rgba(236, 61, 143, 0.01)');
        } catch (e) {
            gradient = 'rgba(236, 61, 143, 0.08)';
        }

        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initialLabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: initialValues,
                    borderColor: '#EC3D8F',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#EC3D8F',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: initialLabels.length > 15 ? 2.5 : 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#be185d',
                    pointHoverBorderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#f9fafb',
                        bodyColor: '#f9fafb',
                        padding: 10,
                        borderRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: function(items) {
                                return items.length ? 'Thời gian: ' + items[0].label : '';
                            },
                            label: function (context) {
                                const val = context.parsed.y !== null ? context.parsed.y : 0;
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(val) + ' đ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11, weight: '500' },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) {
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                return val + 'đ';
                            },
                            color: '#64748b',
                            font: { size: 11 }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });

        // Xử lý sự kiện khi thay đổi bộ lọc thời gian
        const selectRevenueRange = document.getElementById('revenueRange');
        const totalDisplay = document.getElementById('revenueTotalDisplay');

        if (selectRevenueRange) {
            selectRevenueRange.addEventListener('change', function () {
                const selectedRange = this.value;
                const targetUrl = new URL(window.location.href);
                targetUrl.searchParams.set('range', selectedRange);

                // Fetch AJAX mượt mà
                fetch(targetUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Network error');
                    return res.json();
                })
                .then(data => {
                    if (data && data.labels && data.values) {
                        revenueChart.data.labels = data.labels;
                        revenueChart.data.datasets[0].data = data.values;
                        revenueChart.data.datasets[0].pointRadius = data.labels.length > 15 ? 2.5 : 4;
                        revenueChart.update();

                        if (totalDisplay && data.total_formatted) {
                            totalDisplay.textContent = data.total_formatted;
                        }
                        window.history.pushState({ range: selectedRange }, '', targetUrl.toString());
                    } else {
                        window.location.href = targetUrl.toString();
                    }
                })
                .catch(err => {
                    console.warn('AJAX fetch failed, reloading:', err);
                    window.location.href = targetUrl.toString();
                });
            });
        }
    });
</script>
@endpush