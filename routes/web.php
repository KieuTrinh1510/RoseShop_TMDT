<?php

use App\Http\Controllers\Admin\NguoiDungController;
use App\Http\Controllers\Admin\KhachHangController;
use App\Http\Controllers\Admin\NhanVienController;
use App\Http\Controllers\Admin\SanPham\SanPhamController;
use App\Http\Controllers\Admin\SanPham\BoHoaController as SanPhamBoHoaController;
use App\Http\Controllers\Admin\SanPham\NguyenVatLieuController;
use App\Http\Controllers\Admin\HoaDonController;
use App\Http\Controllers\Admin\DonHangController;
use App\Http\Controllers\Admin\PhieuNhapController;
use App\Http\Controllers\Admin\NhaCungCapController;
use App\Http\Controllers\Admin\LoHangController;
use App\Http\Controllers\Admin\BaoCaoController;
use App\Http\Controllers\Shipper\ShipperController;
use App\Http\Controllers\Shipper\NhanDonController;
use App\Http\Controllers\Shipper\ShipperProfileController;

use App\Http\Controllers\Shipper\ShipperThongBaoController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Customer\ThongBaoController as CustomerThongBaoController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerProfileController;
use App\Http\Controllers\Customer\ChiTietSanPhamController;
use App\Http\Controllers\Customer\GioHangController;
use App\Http\Controllers\Customer\ThanhToanController;
use App\Http\Controllers\Auth\RegisteredUserController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BoHoaController as AdminBoHoaController;
use App\Http\Controllers\Customer\BoHoaController as CustomerBoHoaController;

use App\Http\Controllers\VnPayController;

Route::middleware(['auth', 'role:ADMIN,NHAN_VIEN'])->prefix('admin/bo-hoa')->name('admin.bo-hoa.')->group(function () {
    // Mẫu
    Route::get('/mau',                    [AdminBoHoaController::class, 'mau'])->name('mau');
    Route::post('/mau',                   [AdminBoHoaController::class, 'storeMau'])->name('mau.store');
    Route::put('/mau/{id}',               [AdminBoHoaController::class, 'updateMau'])->name('mau.update');
    Route::delete('/mau/{id}',            [AdminBoHoaController::class, 'destroyMau'])->name('mau.destroy');
    Route::patch('/mau/{id}/trang-thai',  [AdminBoHoaController::class, 'trangThaiMau'])->name('mau.trang-thai');
    Route::get('/mau/{id}/edit-data',     [AdminBoHoaController::class, 'editDataMau'])->name('mau.edit-data');

    // Phụ phí + tính giá
    Route::get('/phu-phi',  [AdminBoHoaController::class, 'phuPhi'])->name('phu-phi');
    Route::post('/phu-phi', [AdminBoHoaController::class, 'luuPhuPhi'])->name('phu-phi.store');
    Route::post('/tinh-gia',[AdminBoHoaController::class, 'tinhGia'])->name('tinh-gia');

    // Bó hoa
    Route::post('/',                        [AdminBoHoaController::class, 'store'])->name('store');
    Route::get('/{id}/edit-data',           [AdminBoHoaController::class, 'editData'])->name('edit-data');
   
    Route::post('/{id}/bao-hong',           [AdminBoHoaController::class, 'baoHangHong'])->name('bao-hong');
    Route::delete('/{id}',                  [AdminBoHoaController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/chi-tiet-gia',        [AdminBoHoaController::class, 'chiTietGiaBoHoa'])->name('chi-tiet-gia');
});

// ─── Giỏ bó hoa mẫu (tách riêng khỏi giỏ sản phẩm đơn lẻ) ────────────
Route::middleware(['auth', 'role:KHACH_HANG'])
    ->prefix('bo-hoa/gio')
    ->name('customer.bo-hoa.gio.')
    ->group(function () {
        Route::get('/',       [CustomerBoHoaController::class, 'gioBoHoa'])->name('index');
        Route::post('/add',   [CustomerBoHoaController::class, 'themVaoGio'])->name('add');
        Route::post('/remove',[CustomerBoHoaController::class, 'xoaKhoiGio'])->name('remove');
    });
// ─── Bó hoa — khách hàng ─────────────────────────────────────────────
Route::prefix('bo-hoa')->name('customer.bo-hoa.')->group(function () {
    Route::get('/he-thong',  [CustomerBoHoaController::class, 'heThong'])->name('he-thong');
    Route::get('/tu-tao',    [CustomerBoHoaController::class, 'create'])->name('tu-tao');
    Route::post('/tinh-gia', [CustomerBoHoaController::class, 'quote'])->name('quote');

        Route::middleware(['auth', 'role:KHACH_HANG'])->group(function () {
        Route::get('/tu-tao/tiep-tuc/{id}', [CustomerBoHoaController::class, 'tiepTucTuTao'])
            ->whereNumber('id')
            ->name('tu-tao.tiep-tuc');
        Route::post('/tu-tao/xac-nhan', [CustomerBoHoaController::class, 'xacNhanTuTao'])->name('tu-tao.xac-nhan');
        Route::post('/tu-tao/mua',      [CustomerBoHoaController::class, 'muaTuTao'])->name('tu-tao.mua');
    });
});

Route::post('/mau-bo-hoa/{id}/mua', [CustomerBoHoaController::class, 'muaMau'])
    ->middleware(['auth', 'role:KHACH_HANG'])->name('customer.mau-bo-hoa.mua');



// ─── Chi tiết mẫu bó hoa / bó hoa tự tạo ──────────────────────────────
Route::get('/mau-bo-hoa/chi-tiet/{id}', [ChiTietSanPhamController::class, 'showMauBoHoa'])
    ->name('customer.mau-bo-hoa.chi-tiet');
Route::get('/bo-hoa-tu-tao/chi-tiet',   [ChiTietSanPhamController::class, 'showTuTao'])
    ->middleware(['auth', 'role:KHACH_HANG'])
    ->name('customer.bo-hoa-tu-tao.chi-tiet');




Route::middleware(['auth'])->group(function () {
    Route::get('/vnpay/payment/{hoaDon}', [VnPayController::class, 'payment'])
        ->name('vnpay.payment');
});
 
// Return URL: khách hàng có thể vừa mất session (ví dụ mở app VNPay/trình duyệt khác
// để quét QR rồi được redirect về), nên KHÔNG bắt buộc auth — chỉ hiển thị kết quả,
// không xử lý gì (đã đảm bảo ở VnPayController::return()).
Route::get('/vnpay/return', [VnPayController::class, 'return'])
    ->name('vnpay.return');
 
Route::get('/vnpay/ipn', [VnPayController::class, 'ipn'])
    ->name('vnpay.ipn');
// ─── Trang chủ ────────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $vai_tro = auth()->user()->vai_tro;
        return match($vai_tro) {
            'ADMIN', 'NHAN_VIEN' => redirect()->route('admin.dashboard'),
            'SHIPPER'            => redirect()->route('shipper.dashboard'),
            default              => redirect()->route('customer.dashboard'),
        };
    }
    // Guest vào thẳng dashboard customer (không cần đăng nhập)
    return redirect()->route('customer.dashboard');
});

// ─── Breeze Auth routes ────────────────────────────────────────────────────────
require __DIR__.'/auth.php';

// ─── ADMIN + NHÂN VIÊN Dashboard ──────────────────────────────────────────────
Route::middleware(['auth', 'role:ADMIN,NHAN_VIEN'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->name('admin.dashboard');
});

// ─── ADMIN ONLY ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:ADMIN'])->group(function () {

    // Quản lý Người dùng
    Route::prefix('nguoi-dung')->name('nguoi-dung.')->group(function () {
        Route::get('/',              [NguoiDungController::class, 'index'])->name('index');
        Route::post('/',             [NguoiDungController::class, 'store'])->name('store');
        Route::put('/{id}',          [NguoiDungController::class, 'update'])->name('update');
        Route::delete('/{id}',       [NguoiDungController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/edit-data',[NguoiDungController::class, 'editData'])->name('edit-data');
        Route::post('/{id}/reset-password', [NguoiDungController::class, 'resetPassword'])->name('reset-password');
    });

    // Quản lý Nhân viên (bao gồm trả tiền - admin only)
    Route::prefix('nhan-vien')->name('nhan-vien.')->controller(NhanVienController::class)->group(function () {
        Route::get('/',                               'index')->name('index');
        Route::post('/',                              'store')->name('store');
        Route::put('/{ma_nhan_vien}',                 'update')->name('update');
        Route::delete('/{ma_nhan_vien}',              'destroy')->name('destroy');
        Route::post('/{ma}/tra-tien',                 'payback')->name('payback');
        Route::patch('/{ma_nhan_vien}/toggle-status', 'toggleStatus')->name('toggle-status');
    });

    // ─── Placeholder routes cho các menu đang phát triển (ADMIN only) ──
    Route::get('/admin/thanh-toan',             fn() => view('admin.placeholder', ['title' => 'Thanh toán']))->name('admin.thanh-toan');
    Route::get('/admin/mau-bo-hoa',             fn() => view('admin.placeholder', ['title' => 'Mẫu bó hoa']))->name('admin.mau-bo-hoa');
    Route::get('/admin/nguyen-vat-lieu',        fn() => view('admin.placeholder', ['title' => 'Nguyên vật liệu']))->name('admin.nguyen-vat-lieu');
    Route::get('/admin/ton-kho',                fn() => view('admin.placeholder', ['title' => 'Tồn kho']))->name('admin.ton-kho');
    Route::get('/admin/quan-ly-shipper',        fn() => view('admin.placeholder', ['title' => 'Shipper']))->name('admin.shipper');
    Route::get('/admin/giao-hang/phan-cong',    fn() => view('admin.placeholder', ['title' => 'Phân công giao hàng']))->name('admin.giao-hang.phan-cong');
    Route::get('/admin/giao-hang/theo-doi',     fn() => view('admin.placeholder', ['title' => 'Theo dõi giao hàng']))->name('admin.giao-hang.theo-doi');
    Route::get('/admin/giao-hang/hang-hoan',    fn() => view('admin.placeholder', ['title' => 'Hàng hoàn']))->name('admin.giao-hang.hang-hoan');
    Route::get('/admin/giao-hang/doi-soat-cod', fn() => view('admin.placeholder', ['title' => 'Đối soát COD']))->name('admin.giao-hang.doi-soat-cod');
    Route::get('/admin/bao-cao/kho',            fn() => view('admin.placeholder', ['title' => 'Báo cáo kho']))->name('admin.bao-cao.kho');
    Route::get('/admin/bao-cao/giao-hang',      fn() => view('admin.placeholder', ['title' => 'Báo cáo hoạt động giao hàng']))->name('admin.bao-cao.giao-hang');
    Route::get('/admin/he-thong/thong-bao',     fn() => view('admin.placeholder', ['title' => 'Thông báo']))->name('admin.he-thong.thong-bao');
    Route::get('/admin/he-thong/cau-hinh',      fn() => view('admin.placeholder', ['title' => 'Cấu hình hệ thống']))->name('admin.he-thong.cau-hinh');
});

// ─── ADMIN + NHÂN VIÊN ────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:ADMIN,NHAN_VIEN'])->group(function () {

    // Quản lý Khách hàng
    Route::prefix('khach-hang')->name('khach-hang.')->group(function () {
        Route::get('/',        [KhachHangController::class, 'index'])->name('index');
        Route::put('/{id}',    [KhachHangController::class, 'update'])->name('update');
        Route::delete('/{id}', [KhachHangController::class, 'destroy'])->name('destroy');
    });

    // Sản phẩm
    Route::prefix('san-pham')->name('san-pham.')->group(function () {
    // Trang chính — quản lý sản phẩm đơn lẻ
    Route::get('/', [SanPhamController::class, 'index'])->name('index');

    // 2 trang con — phải đặt TRƯỚC route động {id} để không bị nuốt
    Route::get('/bo-hoa',          [SanPhamBoHoaController::class,  'index'])->name('bo-hoa.index');
    Route::get('/nguyen-vat-lieu', [NguyenVatLieuController::class, 'index'])->name('nguyen-vat-lieu.index');

    // CRUD sản phẩm — giữ nguyên path + name như cũ
    Route::post('/',              [SanPhamController::class, 'store'])->name('store');
    Route::get('/{id}/edit-data', [SanPhamController::class, 'editData'])->name('editData')->whereNumber('id');
    Route::put('/{id}',           [SanPhamController::class, 'update'])->name('update')->whereNumber('id');
    Route::patch('/{id}/toggle',  [SanPhamController::class, 'toggleTrangThai'])->name('toggle')->whereNumber('id');
    Route::get('/{id}/lo-nhap',   [SanPhamController::class, 'loNhap'])->name('lo-nhap')->whereNumber('id');
    Route::post('/bao-hang-hong', [SanPhamController::class, 'baoHangHong'])->name('bao-hang-hong');
});

    // Hóa đơn
    Route::prefix('hoa-don')->name('hoa-don.')->controller(HoaDonController::class)->group(function () {
        Route::get('/',      'index')->name('index');
        Route::get('/{id}',  'show')->name('show');
        Route::delete('/{id}','destroy')->name('destroy');
    });


Route::prefix('don-hang')->name('don-hang.')->controller(DonHangController::class)->group(function () {
    
    Route::get('/', 'index')->name('index');
  
    Route::post('/{id}/confirm', 'confirm')->name('confirm');
   
    Route::post('/{id}/cancel', 'cancel')->name('cancel');
});
Route::prefix('phieu-nhap')->name('phieu-nhap.')->controller(PhieuNhapController::class)->group(function () {
 
    Route::get('/', 'index')->name('index');
 
    Route::post('/', 'store')->name('store');
 
    Route::get('/{id}/edit-data', 'editData')->name('edit-data');
 
    Route::put('/{id}', 'update')->name('update');
 
    Route::delete('/{id}', 'destroy')->name('destroy');
 
    Route::post('/{id}/confirm', 'confirm')->name('confirm');
});

// Quản lý Nhà cung cấp (UC4.1) - Cả ADMIN và NHÂN VIÊN
Route::prefix('admin/nha-cung-cap')->name('admin.nha-cung-cap.')->controller(NhaCungCapController::class)->group(function () {
    Route::get('/',              'index')->name('index');
    Route::get('/create',        'create')->name('create');
    Route::post('/',             'store')->name('store');
    Route::get('/{id}',          'show')->name('show')->whereNumber('id');
    Route::get('/{id}/edit',     'edit')->name('edit')->whereNumber('id');
    Route::put('/{id}',          'update')->name('update')->whereNumber('id');
    Route::patch('/{id}/trang-thai', 'updateStatus')->name('status')->whereNumber('id');
    Route::post('/lo-hang/{ma_chi_tiet_nhap}/danh-gia', 'danhGiaLo')->name('danh-gia-lo')->whereNumber('ma_chi_tiet_nhap');
});
Route::get('/admin/nha-cung-cap', [NhaCungCapController::class, 'index'])->name('admin.nha-cung-cap');

// Quản lý Lô hàng & Đánh giá chất lượng lô (Cả ADMIN và NHÂN VIÊN)
Route::middleware(['auth', 'role:ADMIN,NHAN_VIEN'])->group(function () {
    Route::get('/admin/lo-hang', [LoHangController::class, 'index'])->name('admin.lo-hang');
    Route::get('/admin/lo-hang/{id}', [LoHangController::class, 'show'])->name('admin.lo-hang.show')->whereNumber('id');
    Route::post('/admin/lo-hang/{id}/danh-gia', [LoHangController::class, 'danhGia'])->name('admin.lo-hang.danh-gia')->whereNumber('id');
});
Route::get('/bao-cao',               [BaoCaoController::class, 'index'])->name('bao-cao.index');
Route::get('/bao-cao/doanh-thu',     [BaoCaoController::class, 'doanhThu'])->name('bao-cao.doanh-thu');
Route::get('/bao-cao/doanh-thu/export', [BaoCaoController::class, 'exportDoanhThu'])->name('bao-cao.doanh-thu.export');
Route::get('/bao-cao/loi-nhuan',     [BaoCaoController::class, 'index'])->name('bao-cao.loi-nhuan');
Route::get('/bao-cao/san-pham',              [BaoCaoController::class, 'baoCaoSanPham'])->name('bao-cao.san-pham');
Route::get('/bao-cao/san-pham/export',         [BaoCaoController::class, 'exportBaoCaoSanPham'])->name('bao-cao.san-pham.export');
Route::get('/bao-cao/san-pham/{id}/hang-hong', [BaoCaoController::class, 'chiTietHangHong'])->name('bao-cao.san-pham.hang-hong');
Route::get('/bao-cao/san-pham/{id}/chi-tiet',  [BaoCaoController::class, 'chiTietSanPham'])->name('bao-cao.san-pham.chi-tiet');
Route::get('/bao-cao/ton-kho',       [BaoCaoController::class, 'index'])->name('bao-cao.ton-kho');
Route::get('/bao-cao/khach-hang',    [BaoCaoController::class, 'index'])->name('bao-cao.khach-hang');



});
Route::middleware(['auth', 'role:SHIPPER'])->group(function () {
    Route::get('/shipper/dashboard', [ShipperController::class, 'dashboard'])->name('shipper.dashboard');
    Route::patch('/shipper/don-hang/{id}/cap-nhat', [ShipperController::class, 'updateStatus'])->name('shipper.update-status');
    Route::get('/shipper/don-hang/{id}/chi-tiet', [NhanDonController::class, 'show'])->name('shipper.don-hang.chi-tiet');
    Route::get('/shipper/profile',[ShipperProfileController::class, 'edit'])->name('shipper.profile.edit');
    Route::patch('/shipper/profile',[ShipperProfileController::class, 'update'])->name('shipper.profile.update');
    Route::patch('/shipper/profile/password', [ShipperProfileController::class, 'updatePassword']) ->name('shipper.profile.password');
    Route::post('/shipper/don-hang/{id}/da-den-noi',[ShipperThongBaoController::class, 'guiThongBao'])->name('shipper.don-hang.da-den-noi');
    Route::get('/shipper/thong-bao', [ShipperThongBaoController::class, 'index'])
    ->name('shipper.thong-bao');
 
    Route::get('/shipper/thong-bao/so-chua-doc', [ShipperThongBaoController::class, 'soChuaDoc'])
    ->name('shipper.thong-bao.so-chua-doc');
 
    Route::patch('/shipper/thong-bao/{id}/xoa', [ShipperThongBaoController::class, 'xoa'])
    ->name('shipper.thong-bao.xoa');
 

    Route::post('/shipper/don-hang/{maHoaDon}/check-timeout', [ShipperThongBaoController::class, 'checkTimeout'])
    ->name('shipper.don-hang.check-timeout');
});



//Khach hàng
Route::get('/customer/dashboard', [CustomerController::class, 'dashboard'])
    ->name('customer.dashboard');
Route::get('/customer/hoa-tuoi', [CustomerController::class, 'hoaTuoi'])
    ->name('customer.hoa-tuoi');
Route::get('/customer/san-pham/{id}', [ChiTietSanPhamController::class, 'show'])
    ->name('customer.san-pham.chi-tiet');

// Các chức năng cần đăng nhập mới dùng được
Route::middleware(['auth', 'role:KHACH_HANG'])->group(function () {
    Route::get('/customer/profile',            [CustomerProfileController::class, 'edit'])
        ->name('customer.profile.edit');
    Route::patch('/customer/profile',          [CustomerProfileController::class, 'update'])
        ->name('customer.profile.update');
    Route::patch('/customer/profile/password', [CustomerProfileController::class, 'updatePassword'])
        ->name('customer.profile.password');
        Route::get('/customer/gio-hang', [GioHangController::class, 'index'])
        ->name('customer.gio-hang');

    Route::post('/customer/gio-hang/add', [GioHangController::class, 'add'])
        ->name('customer.gio-hang.add');

    Route::post('/customer/gio-hang/update', [GioHangController::class, 'update'])
        ->name('customer.gio-hang.update');

    Route::post('/customer/gio-hang/remove', [GioHangController::class, 'remove'])
        ->name('customer.gio-hang.remove');

    Route::post('/customer/gio-hang/apply-points', [GioHangController::class, 'applyPoints'])
        ->name('customer.gio-hang.apply-points');
    Route::post('/customer/gio-hang/checkout', [GioHangController::class, 'checkout'])
        ->name('customer.gio-hang.checkout');
    Route::get('/customer/thanh-toan', [ThanhToanController::class, 'index'])->name('customer.thanh-toan');

    Route::post('/customer/thanh-toan', [ThanhToanController::class, 'store'])->name('customer.thanh-toan.store');
    Route::get('/customer/thong-bao',[CustomerThongBaoController::class, 'index'])->name('customer.thong-bao');

    Route::get('/customer/thong-bao/so-chua-doc',[CustomerThongBaoController::class, 'soChuaDoc'])->name('customer.thong-bao.so-chua-doc');

    Route::patch('/customer/thong-bao/{id}/xoa',[CustomerThongBaoController::class, 'xoa'])->name('customer.thong-bao.xoa');
});
//
Route::post('/customer/mua-ngay', [GioHangController::class, 'buyNow'])
    ->name('customer.mua-ngay');
// tin tức chi tiết
Route::get('/customer/tin-tuc', function () {
    return view('customer.TinTuc');
})->name('customer.tin-tuc');
Route::get('/customer/tin-tuc/{slug}', function ($slug) {
    return view('customer.ChiTietTinTuc', compact('slug'));
})->name('customer.tin-tuc.chi-tiet');
// Phụ kiện
Route::get('/customer/phu-kien', [CustomerController::class, 'phuKien'])
    ->name('customer.phu-kien');
    // Quà tặng
Route::get('/customer/qua-tang', [CustomerController::class, 'quaTang'])
    ->name('customer.qua-tang');
// gioi thieu
Route::get('/customer/gioi-thieu', [CustomerController::class, 'gioiThieu'])
    ->name('customer.gioi-thieu');
// Liên hệ
Route::get('/customer/lien-he', [CustomerController::class, 'lienHe'])
    ->name('customer.lien-he');