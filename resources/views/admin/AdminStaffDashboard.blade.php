@extends('layouts.admin')

@section('content')

    <div class="admin-home">

        <div class="rs-page-header">
            <div class="rs-page-header-text">
                <h1 class="rs-page-title">Tổng quan cửa hàng</h1>
                <p class="rs-page-subtitle">Theo dõi doanh thu, đơn hàng và các chỉ số hoạt động của RoseShop.</p>
            </div>

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
        </div>

        <div class="home-main">

            <div class="home-panel chart-panel">
                <div class="panel-title">
                    <h3>Doanh thu</h3>

                    <select id="revenueRange" class="dashboard-select">
                        <option value="today" {{ ($range ?? '7days') == 'today' ? 'selected' : '' }}>Hôm nay</option>
                        <option value="7days" {{ ($range ?? '7days') == '7days' ? 'selected' : '' }}>7 ngày qua</option>
                        <option value="30days" {{ ($range ?? '7days') == '30days' ? 'selected' : '' }}>30 ngày qua</option>
                        <option value="month" {{ ($range ?? '7days') == 'month' ? 'selected' : '' }}>Tháng này</option>
                        <option value="year" {{ ($range ?? '7days') == 'year' ? 'selected' : '' }}>Năm nay</option>
                    </select>
                </div>

                @php
                    $chartData = $doanhThuTheoNgay ?? collect();
                    $maxRevenue = max($chartData->max('doanh_thu') ?? 1, 1);
                @endphp

                <div class="dashboard-chart">
                    @forelse($chartData as $item)
                        <div class="chart-bar-item">
                            <div class="bar" style="height: {{ max(25, ($item->doanh_thu / $maxRevenue) * 180) }}px;">
                            </div>

                            <strong>{{ number_format($item->doanh_thu, 0, ',', '.') }}</strong>

                            <span>{{ \Carbon\Carbon::parse($item->ngay)->format('d/m') }}</span>
                        </div>
                    @empty
                        <div class="empty-chart">
                            Chưa có dữ liệu doanh thu.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="home-panel">
                <div class="panel-title">
                    <h3>Đơn hàng chờ xử lý</h3>
                    <a href="{{ route('don-hang.index') }}" class="mini-link">Xem tất cả</a>
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
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($sanPhamSapHet as $sp)
                            <tr>
                                <td>{{ $sp->ten_san_pham }}</td>
                                <td>{{ number_format($sp->so_luong ?? 0) }}</td>
                                <td><span class="low-stock">Sắp hết</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Không có sản phẩm sắp hết hàng</td>
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
    </script>

@endsection