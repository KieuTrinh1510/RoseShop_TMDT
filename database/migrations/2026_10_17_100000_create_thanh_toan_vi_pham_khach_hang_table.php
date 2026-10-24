<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng ghi nhận các giao dịch thanh toán khoản vi phạm COD của khách hàng.
 *
 * - Mỗi vi phạm có thể có NHIỀU giao dịch (thử lại VNPay, admin xác nhận thủ công).
 * - CHỈ ĐƯỢC có tối đa MỘT giao dịch DA_THANH_TOAN cho một vi phạm.
 * - Không xóa giao dịch thất bại — giữ lịch sử đối soát.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thanh_toan_vi_pham_khach_hang', function (Blueprint $table) {
            $table->id('ma_thanh_toan');

            // FK đến vi_pham_khach_hang (BIGINT UNSIGNED theo migration gốc)
            $table->unsignedBigInteger('ma_vi_pham');

            // FK khách hàng (INT theo schema khach_hang)
            $table->integer('ma_khach_hang');

            // Đơn bị hủy liên quan (INT theo schema hoa_don)
            $table->integer('ma_hoa_don')->nullable();

            // Số tiền = hoa_don.tong_tien tại thời điểm tạo giao dịch
            $table->decimal('so_tien', 12, 2);

            // VNPAY | THU_CONG
            $table->string('phuong_thuc', 20);

            // Mã giao dịch VNPay (nếu thanh toán qua VNPay)
            $table->string('ma_giao_dich', 100)->nullable();

            // CHO_THANH_TOAN | DA_THANH_TOAN | DA_HUY | THAT_BAI
            $table->enum('trang_thai', [
                'CHO_THANH_TOAN',
                'DA_THANH_TOAN',
                'DA_HUY',
                'THAT_BAI',
            ])->default('CHO_THANH_TOAN');

            // Ghi chú của admin khi xác nhận thủ công, hoặc lý do thất bại
            $table->text('ghi_chu')->nullable();

            $table->dateTime('thoi_diem_thanh_toan')->nullable();
            $table->timestamps();

            // FK
            $table->foreign('ma_vi_pham')
                ->references('ma_vi_pham')->on('vi_pham_khach_hang')
                ->cascadeOnDelete();

            $table->foreign('ma_khach_hang')
                ->references('ma_khach_hang')->on('khach_hang')
                ->cascadeOnDelete();

            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->nullOnDelete();

            // Index tra cứu
            $table->index(['ma_vi_pham', 'trang_thai'], 'idx_ttvp_vi_pham_tt');
            $table->index(['ma_khach_hang', 'trang_thai'], 'idx_ttvp_kh_tt');
            $table->index('ma_giao_dich', 'idx_ttvp_ma_gd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thanh_toan_vi_pham_khach_hang');
    }
};