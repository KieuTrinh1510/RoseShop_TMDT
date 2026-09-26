const sequelize = require('../config/database');
const NhanVien = require('../models/NhanVien');


const GIOI_HAN_DON = 10;
// Tạo lỗi có kèm status HTTP và mã lỗi để controller bắt và trả về client

function taoLoi(status, code, message) {
    const err = new Error(message);
    err.status = status;
    err.code = code;
    return err;
}

// Biến row DB thành object gọn để trả cho app

function buildStatusResponse(nhanVien) {
    const isFull = nhanVien.so_don_hien_tai >= GIOI_HAN_DON;
    const coTheNhanDon =
        nhanVien.trang_thai_hoat_dong === 'ONLINE' &&
        !isFull;


    return {
        ma_nhan_vien: nhanVien.ma_nhan_vien,
        ten_nhan_vien: nhanVien.ten_nhan_vien,
        trang_thai_hoat_dong: nhanVien.trang_thai_hoat_dong,
        nhan_don: nhanVien.nhan_don,
        so_don_hien_tai: nhanVien.so_don_hien_tai,
        gioi_han_don: GIOI_HAN_DON,
        is_full: isFull,
        co_the_nhan_don: coTheNhanDon,
    };

}
// Tìm shipper, không có thì ném lỗi 404

async function timShipperHoacNemLoi(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);

    if (!shipper) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'khong tim thay shipper')
    }
    return shipper;
}
// Màn hình chính của shipper: xem trạng thái hiện tại

async function getStatus(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);

    return buildStatusResponse(shipper);
}
// Nút gạt ONLINE / OFFLINE

async function setOnlineStatus(maNhanVien, trangThaiMoi) {
    if (trangThaiMoi !== 'ONLINE' && trangThaiMoi !== 'OFFLINE') {
        throw taoLoi(400, 'INVALID_STATUS', 'trang thai chi duoc la ONLINE hoac OFFLINE');
    }

    const shipper = await timShipperHoacNemLoi(maNhanVien);
    const trangThaiCu = shipper.trang_thai_hoat_dong;

    // Chỉ đổi đúng 1 cột. Đang có đơn mà chuyển OFFLINE vẫn được,
    // đơn hiện tại không bị huỷ, chỉ bị chặn nhận đơn mới.
    await shipper.update({ trang_thai_hoat_dong: trangThaiMoi });

    console.log(`[Status] Shipper ${maNhanVien}:${trangThaiCu}-> ${trangThaiMoi}`);

    return buildStatusResponse(shipper);

}
//
//
//
//đoạn logic này sẽ để bên phía quản lý đơn hàng của admin
//
//
//

// Dùng cho module nhân viên phân công: shipper này có được chỉ định thêm đơn không?
async function canReceiveNewOrder(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    return buildStatusResponse(shipper).co_the_nhan_don;
}

// Gọi khi nhân viên gán đơn thành công cho shipper: số đơn +1
async function incrementOrderCount(maNhanVien) {
    // sequelize.transaction(callback): chạy xong callback thì tự commit,
    // nếu callback ném lỗi thì tự rollback.
    const shipper = await sequelize.transaction(async (t) => {
        // Khoá dòng shipper (SELECT ... FOR UPDATE) để 2 nhân viên
        // không thể cùng gán đơn cho 1 shipper cùng lúc.
        const nv = await NhanVien.findByPk(maNhanVien, {
            transaction: t,
            lock: t.LOCK.UPDATE,
        });
        if (!nv) {
            throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
        }

        // Kiểm tra lại sau khi khoá (chống race condition):
        // shipper có thể vừa bấm OFFLINE hoặc vừa bị gán đủ 10 đơn
        if (nv.trang_thai_hoat_dong !== 'ONLINE') {
            throw taoLoi(409, 'SHIPPER_OFFLINE', 'Shipper đang OFFLINE, không thể chỉ định đơn.');
        }
        if (nv.so_don_hien_tai >= GIOI_HAN_DON) {
            throw taoLoi(409, 'SHIPPER_FULL', 'Shipper đã đủ 10/10 đơn.');
        }

        await nv.update({ so_don_hien_tai: nv.so_don_hien_tai + 1 }, { transaction: t });
        return nv;
    });

    console.log(`[Status] Shipper ${maNhanVien}: số đơn tăng lên ${shipper.so_don_hien_tai}`);
    return buildStatusResponse(shipper);
}




//
//
// logic cuuar phía shipper
//
//// Gọi khi đơn rời khỏi tay shipper (giao thành công / giao thất bại): số đơn -1

async function decrementOrderCount(maNhanVien) {

    //Transaction = đảm bảo nhiều thao tác DB được thực hiện như một "gói" nguyên tử.

    //lock: UPDATE = trong transaction đó, khóa dòng được lấy ra để transaction khác không cùng sửa nó một cách cạnh tranh.
    const shipper = await sequelize.transaction(async (t) => {
        const nv = await NhanVien.findByPk(maNhanVien, {
            transaction: t,
            lock: t.LOCK.UPDATE,
        });
        if (!nv) {
            throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'khong tim thay shipper');

        }
        if (nv.so_don_hien_tai <= 0) {
            console.warn(`[Status] shipper ${maNhanVien}: so don la 0,khong giam them`);
        } else {
            await nv.update({ so_don_hien_tai: nv.so_don_hien_tai - 1 }, { transaction: t });
        }
        return nv;
    })
    // Không đụng tới ONLINE/OFFLINE: shipper vẫn ONLINE, từ 10 -> 9 là tự nhận đơn được lại
    console.log(`[Status] shipper ${maNhanVien}: so don con ${shipper.so_don_hien_tai}`);

    return buildStatusResponse(shipper);

}

module.exports = {
    getStatus,
    setOnlineStatus,
    canReceiveNewOrder,
    incrementOrderCount,
    decrementOrderCount,
}
