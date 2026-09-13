<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
    PHASE 4 — Migration KHÔNG destructive:
    - Chỉ ADD COLUMN (nullable), không sửa/xoá cột nào đang có.
    - KHÔNG đụng vào enum trang_thai / trang_thai_thanh_toan / phuong_thuc_thanh_toan
      (xem giải thích ở Phase 3: tận dụng nguyên trạng enum hiện có, không ALTER).
    - vnpay_transaction_no/bank_code/pay_date/response_code: ghi lại thông tin giao
      dịch VNPay để tra cứu/đối soát sau này (mục 8 trong yêu cầu gốc).
    - vnpay_cart_snapshot (JSON, THÊM Ở PHASE 5): VNPay gọi IPN từ server của họ,
      KHÔNG có session của khách hàng, nên tại thời điểm tạo đơn VNPay (PENDING),
      phải lưu lại snapshot giỏ hàng + số điểm đã dùng vào chính DB, để
      ThanhToanController::finalizeVnpayThanhCong() có dữ liệu chạy lại đúng
      thuật toán FIFO khi IPN xác nhận thanh toán thành công. Không dùng cho COD
      (COD xử lý FIFO ngay lúc tạo đơn, không cần snapshot).
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->string('vnpay_transaction_no', 50)->nullable()->after('ma_nhan_vien_giao');
            $table->string('vnpay_bank_code', 30)->nullable()->after('vnpay_transaction_no');
            $table->string('vnpay_pay_date', 20)->nullable()->after('vnpay_bank_code');
            $table->string('vnpay_response_code', 10)->nullable()->after('vnpay_pay_date');
            $table->json('vnpay_cart_snapshot')->nullable()->after('vnpay_response_code');
        });
    }

    public function down(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->dropColumn([
                'vnpay_transaction_no',
                'vnpay_bank_code',
                'vnpay_pay_date',
                'vnpay_response_code',
                'vnpay_cart_snapshot',
            ]);
        });
    }
};