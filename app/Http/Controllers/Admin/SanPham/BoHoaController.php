<?php

namespace App\Http\Controllers\Admin\SanPham;

use App\Http\Controllers\Admin\BoHoaController as BaseBoHoaController;
use Illuminate\Http\Request;

/**
 * Class con của Admin\BoHoaController.
 *
 * Kế thừa TOÀN BỘ:
 *   - Hằng số: SO_BONG, KIEU_DANG, CONCEPT, GIAY_GOI_QTY, RUY_BANG_QTY,
 *              XOP_QTY, PHU_KIEN_MAX, VAI_TRO, VAI_TRO_CHO_PHEP, MA_NHAN_VIEN_MAC_DINH
 *   - Static:  getDuLieuBoHoa(), validateBo(), kiemTraCongThuc(),
 *              kiemTraNguyenLieuDangBan(), layPhuPhi(), phanBo(), hoanKho(),
 *              giaPhanBo(), tinhGiaDuLieu(), luuChiTiet(), anh(), anhMau(),
 *              saoChepAnhTuMau(), huyBoTrongDon(), loi(), kiemTraTrungHoaChinh()
 *   - Instance: mau(), phuPhi(), luuPhuPhi(), storeMau(), updateMau(),
 *               destroyMau(), trangThaiMau(), editDataMau(), store(),
 *               baoHangHong(), chiTietGiaBoHoa(), json()
 *
 * KHÔNG override bất kỳ method nào — chỉ thêm index() để render view mới.
 * Route /admin/bo-hoa/* cũ vẫn trỏ class cha Admin\BoHoaController như trước,
 * nên 9 file phụ thuộc (DonHangController, Customer\*, các view customer…)
 * không bị ảnh hưởng.
 */
class BoHoaController extends BaseBoHoaController
{
    public function index(Request $request)
    {
        return view('admin.SanPham.QuanLyBoHoa', static::getDuLieuBoHoa($request));
    }
}