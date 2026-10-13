<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dia_chi_khach_hang', function (Blueprint $table) {
            $table->id('ma_dia_chi');
            $table->integer('ma_khach_hang');
            $table->string('ten_nguoi_nhan', 100);
            $table->string('so_dien_thoai', 20);
            $table->string('quan_huyen', 100);
            $table->string('xa_phuong', 100);
            $table->string('dia_chi_chi_tiet', 255);
            $table->string('dia_chi_day_du', 500);
            $table->boolean('la_mac_dinh')->default(false);
            $table->timestamps();

            $table->foreign('ma_khach_hang')
                  ->references('ma_khach_hang')
                  ->on('khach_hang')
                  ->onDelete('cascade');
        });

        // Di chuyển dữ liệu địa chỉ hiện có từ bảng khach_hang sang dia_chi_khach_hang
        $customers = DB::table('khach_hang')
            ->whereNotNull('dia_chi')
            ->where('dia_chi', '!=', '')
            ->get();

        foreach ($customers as $c) {
            $diaChiRaw = trim($c->dia_chi);
            if (empty($diaChiRaw)) continue;

            $parts = array_map('trim', explode(',', $diaChiRaw));
            if (count($parts) >= 1 && strtolower(end($parts)) === 'hà nội') {
                array_pop($parts);
            }
            $quan = count($parts) >= 2 ? $parts[count($parts) - 1] : 'Cầu Giấy';
            $phuong = count($parts) >= 3 ? $parts[count($parts) - 2] : 'Dịch Vọng Hậu';
            $chiTiet = count($parts) >= 1 ? implode(', ', array_slice($parts, 0, max(1, count($parts) - 2))) : $diaChiRaw;

            $diaChiDayDu = $chiTiet . ', ' . $phuong . ', ' . $quan . ', Hà Nội';

            DB::table('dia_chi_khach_hang')->insert([
                'ma_khach_hang'    => $c->ma_khach_hang,
                'ten_nguoi_nhan'   => $c->ten_khach_hang ?: 'Khách Hàng',
                'so_dien_thoai'    => $c->so_dien_thoai ?: '0988888888',
                'quan_huyen'       => $quan ?: 'Cầu Giấy',
                'xa_phuong'        => $phuong ?: 'Dịch Vọng Hậu',
                'dia_chi_chi_tiet' => $chiTiet ?: 'Địa chỉ chi tiết',
                'dia_chi_day_du'   => $diaChiDayDu,
                'la_mac_dinh'      => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dia_chi_khach_hang');
    }
};
