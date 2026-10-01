<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_nhap', function (Blueprint $table) {
            $table->unsignedBigInteger('ma_nha_cung_cap')
                  ->nullable()
                  ->after('ma_nhan_vien');

            $table->foreign('ma_nha_cung_cap')
                  ->references('ma_nha_cung_cap')
                  ->on('nha_cung_cap')
                  ->onDelete('set null');
        });

        // Backfill dữ liệu an toàn từ các phiếu nhập hiện có
        $phieuNhaps = DB::table('phieu_nhap')
            ->select('ma_phieu_nhap', 'ten_nha_cung_cap', 'so_dien_thoai_ncc', 'email_ncc', 'dia_chi_ncc')
            ->get();

        foreach ($phieuNhaps as $pn) {
            $ten = trim($pn->ten_nha_cung_cap ?? '');
            $sdt = trim($pn->so_dien_thoai_ncc ?? '');
            $email = trim($pn->email_ncc ?? '');
            $diaChi = trim($pn->dia_chi_ncc ?? '');

            if ($ten === '' && $sdt === '' && $email === '') {
                continue;
            }

            // Tìm nhà cung cấp đã có trong bảng nha_cung_cap
            $nccId = null;

            // 1. Khớp theo email (nếu có email)
            if ($email !== '') {
                $found = DB::table('nha_cung_cap')
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->first();
                if ($found) {
                    $nccId = $found->ma_nha_cung_cap;
                }
            }

            // 2. Khớp theo SĐT và tên tương đồng (nếu chưa tìm thấy và có SĐT)
            if (!$nccId && $sdt !== '') {
                $found = DB::table('nha_cung_cap')
                    ->where('so_dien_thoai', $sdt)
                    ->whereRaw('LOWER(ten_nha_cung_cap) = ?', [mb_strtolower($ten)])
                    ->first();
                if ($found) {
                    $nccId = $found->ma_nha_cung_cap;
                }
            }

            // 3. Khớp chính xác tên nếu không có email và SĐT
            if (!$nccId && $email === '' && $sdt === '' && $ten !== '') {
                $found = DB::table('nha_cung_cap')
                    ->whereRaw('LOWER(ten_nha_cung_cap) = ?', [mb_strtolower($ten)])
                    ->first();
                if ($found) {
                    $nccId = $found->ma_nha_cung_cap;
                }
            }

            // Nếu chưa có NCC này, tạo mới
            if (!$nccId) {
                $nccId = DB::table('nha_cung_cap')->insertGetId([
                    'ten_nha_cung_cap' => $ten !== '' ? $ten : 'Nhà cung cấp #' . $pn->ma_phieu_nhap,
                    'so_dien_thoai'    => $sdt !== '' ? $sdt : null,
                    'email'            => $email !== '' ? $email : null,
                    'dia_chi'          => $diaChi !== '' ? $diaChi : null,
                    'trang_thai'       => 'DANG_HOP_TAC',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Cập nhật ma_nha_cung_cap cho phiếu nhập này
            DB::table('phieu_nhap')
                ->where('ma_phieu_nhap', $pn->ma_phieu_nhap)
                ->update(['ma_nha_cung_cap' => $nccId]);
        }
    }

    public function down(): void
    {
        Schema::table('phieu_nhap', function (Blueprint $table) {
            $table->dropForeign(['ma_nha_cung_cap']);
            $table->dropColumn('ma_nha_cung_cap');
        });
    }
};
