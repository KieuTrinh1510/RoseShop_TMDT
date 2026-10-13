<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetInventoryTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:reset-inventory-test-data
                            {--all-ncc : Xóa toàn bộ nhà cung cấp để test UC4.1 từ danh sách trống}
                            {--delete-test-orders : Xóa các đơn hàng test phát sinh (ma_hoa_don > 20)}
                            {--force : Bỏ qua bước xác nhận tương tác}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dọn sạch dữ liệu test/rác cho UC4.1 (NCC), UC4.2 (Phiếu nhập), UC4.3 (Lô hàng), UC4.4 (Tồn kho & Reservation)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Kiểm tra môi trường tuyệt đối: chỉ chạy trên APP_ENV=local
        if (!App::isLocal()) {
            $this->error('LỖI AN TOÀN: Command này CHỈ ĐƯỢC PHÉP CHẠY TRÊN MÔI TRƯỜNG LOCAL (APP_ENV=local)!');
            $this->error('Môi trường hiện tại: ' . config('app.env'));
            return Command::FAILURE;
        }

        $dbName = config('database.connections.mysql.database');
        $this->info("=== KHỞI ĐỘNG TIẾN TRÌNH RESET DỮ LIỆU TEST KHO / NHẬP HÀNG ===");
        $this->line("Database: <comment>{$dbName}</comment> (Host: " . config('database.connections.mysql.host') . ")");
        $this->line("Môi trường: <info>" . config('app.env') . "</info>");

        $deleteAllNcc = $this->option('all-ncc');
        $deleteTestOrders = $this->option('delete-test-orders');

        if ($deleteAllNcc) {
            $this->warn("Tùy chọn NCC: Sẽ XÓA TOÀN BỘ NHÀ CUNG CẤP (100% trống để tạo mới từ đầu).");
        } else {
            $this->info("Tùy chọn NCC: Sẽ GIỮ 7 NCC MẪU BAN ĐẦU (ID 1-7) và XÓA 1.127 NCC TEST (ID >= 183).");
        }

        if ($deleteTestOrders) {
            $this->warn("Tùy chọn Đơn hàng: Sẽ XÓA 211 ĐƠN HÀNG TEST (ma_hoa_don > 20) và detach lô cho 20 đơn ban đầu.");
        } else {
            $this->info("Tùy chọn Đơn hàng: GIỮ NGUYÊN ĐƠN HÀNG, chỉ detach tham chiếu lô (SET ma_chi_tiet_nhap = NULL).");
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Bạn có chắc chắn muốn thực hiện dọn sạch dữ liệu test nghiệp vụ kho/nhập hàng không?', false)) {
                $this->comment('Đã hủy thao tác.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('--- Bắt đầu transaction xóa theo thứ tự FK an toàn ---');

        try {
            DB::transaction(function () use ($deleteAllNcc, $deleteTestOrders) {
                // 1. Xóa toàn bộ inventory_reservations (RESERVED, CONSUMED, RELEASED)
                if (Schema::hasTable('inventory_reservations')) {
                    $c = DB::table('inventory_reservations')->count();
                    DB::table('inventory_reservations')->delete();
                    $this->line("1. Đã xóa <comment>{$c}</comment> bản ghi trong table `inventory_reservations`.");
                }

                // 2. Xóa toàn bộ đánh giá QC lô hàng
                if (Schema::hasTable('danh_gia_lo_hang')) {
                    $c = DB::table('danh_gia_lo_hang')->count();
                    DB::table('danh_gia_lo_hang')->delete();
                    $this->line("2. Đã xóa <comment>{$c}</comment> bản ghi trong table `danh_gia_lo_hang`.");
                }

                // 3. Xóa toàn bộ báo cáo hàng hỏng / hao hụt
                if (Schema::hasTable('bao_cao_hang_hong')) {
                    $c = DB::table('bao_cao_hang_hong')->count();
                    DB::table('bao_cao_hang_hong')->delete();
                    $this->line("3. Đã xóa <comment>{$c}</comment> bản ghi trong table `bao_cao_hang_hong`.");
                }

                // 4. Xử lý giỏ hàng nếu có
                if (Schema::hasTable('cart_items')) {
                    $c = DB::table('cart_items')->count();
                    DB::table('cart_items')->delete();
                    $this->line("4. Đã dọn <comment>{$c}</comment> bản ghi trong table `cart_items`.");
                }

                // 5. Xử lý đơn hàng test và chi tiết đơn hàng
                if ($deleteTestOrders) {
                    if (Schema::hasTable('bo_hoa')) {
                        $testBoHoaIds = DB::table('bo_hoa')->where('ma_hoa_don', '>', 20)->pluck('ma_bo_hoa');
                        if ($testBoHoaIds->isNotEmpty() && Schema::hasTable('chi_tiet_bo_hoa')) {
                            $cCtbh = DB::table('chi_tiet_bo_hoa')->whereIn('ma_bo_hoa', $testBoHoaIds)->delete();
                            $this->line("5a. Đã xóa <comment>{$cCtbh}</comment> chi tiết bó hoa của các đơn hàng test.");
                        }
                        $cBh = DB::table('bo_hoa')->where('ma_hoa_don', '>', 20)->delete();
                        $this->line("5b. Đã xóa <comment>{$cBh}</comment> bó hoa gắn với các đơn hàng test (ma_hoa_don > 20).");
                    }

                    if (Schema::hasTable('chi_tiet_hoa_don')) {
                        $cTestCthd = DB::table('chi_tiet_hoa_don')->where('ma_hoa_don', '>', 20)->delete();
                        $this->line("5c. Đã xóa <comment>{$cTestCthd}</comment> chi tiết hóa đơn test (ma_hoa_don > 20).");
                    }

                    if (Schema::hasTable('hoa_don')) {
                        $cOrders = DB::table('hoa_don')->where('ma_hoa_don', '>', 20)->delete();
                        $this->line("5d. Đã xóa <comment>{$cOrders}</comment> hóa đơn test (ma_hoa_don > 20).");
                    }
                }

                // 6. Detach mã lô cho các chi tiết đơn và bó hoa còn lại (nếu có)
                if (Schema::hasTable('chi_tiet_hoa_don')) {
                    $cDetachHd = DB::table('chi_tiet_hoa_don')->whereNotNull('ma_chi_tiet_nhap')->count();
                    DB::table('chi_tiet_hoa_don')->whereNotNull('ma_chi_tiet_nhap')->update(['ma_chi_tiet_nhap' => null]);
                    $this->line("6a. Đã detach <comment>{$cDetachHd}</comment> tham chiếu `ma_chi_tiet_nhap` trong `chi_tiet_hoa_don` (set NULL).");
                }

                if (Schema::hasTable('chi_tiet_bo_hoa')) {
                    $cDetachBh = DB::table('chi_tiet_bo_hoa')->whereNotNull('ma_chi_tiet_nhap')->count();
                    DB::table('chi_tiet_bo_hoa')->whereNotNull('ma_chi_tiet_nhap')->update(['ma_chi_tiet_nhap' => null]);
                    $this->line("6b. Đã detach <comment>{$cDetachBh}</comment> tham chiếu `ma_chi_tiet_nhap` trong `chi_tiet_bo_hoa` (set NULL).");
                }

                // 7. Xóa toàn bộ chi tiết nhập / lô hàng
                if (Schema::hasTable('chi_tiet_nhap')) {
                    $c = DB::table('chi_tiet_nhap')->count();
                    DB::table('chi_tiet_nhap')->delete();
                    $this->line("7. Đã xóa <comment>{$c}</comment> bản ghi trong table `chi_tiet_nhap` (Lô hàng).");
                }

                // 8. Xóa toàn bộ phiếu nhập (DRAFT, CONFIRMED, CANCELLED)
                if (Schema::hasTable('phieu_nhap')) {
                    $c = DB::table('phieu_nhap')->count();
                    DB::table('phieu_nhap')->delete();
                    $this->line("8. Đã xóa <comment>{$c}</comment> bản ghi trong table `phieu_nhap`.");
                }

                // 9. Xử lý nhà cung cấp
                if (Schema::hasTable('nha_cung_cap')) {
                    if ($deleteAllNcc) {
                        $c = DB::table('nha_cung_cap')->count();
                        DB::table('nha_cung_cap')->delete();
                        $this->line("9. Đã xóa toàn bộ <comment>{$c}</comment> bản ghi trong table `nha_cung_cap`.");
                    } else {
                        $c = DB::table('nha_cung_cap')->where('ma_nha_cung_cap', '>', 7)->count();
                        DB::table('nha_cung_cap')->where('ma_nha_cung_cap', '>', 7)->delete();
                        $this->line("9. Đã xóa <comment>{$c}</comment> NCC test (ma_nha_cung_cap > 7). Giữ lại 7 NCC ban đầu (ID 1-7).");
                    }
                }

                // 10. Reset tồn kho sản phẩm về 0
                if (Schema::hasTable('san_pham')) {
                    $c = DB::table('san_pham')->where('so_luong', '!=', 0)->count();
                    DB::table('san_pham')->update(['so_luong' => 0]);
                    $this->line("10. Đã reset số lượng tồn `san_pham.so_luong = 0` cho <comment>{$c}</comment> sản phẩm có tồn khác 0.");
                }
            });

            // Reset AUTO_INCREMENT trong môi trường local
            $tablesToReset = [
                'inventory_reservations',
                'danh_gia_lo_hang',
                'bao_cao_hang_hong',
                'chi_tiet_nhap',
                'phieu_nhap',
            ];
            if ($deleteAllNcc) {
                $tablesToReset[] = 'nha_cung_cap';
            }

            foreach ($tablesToReset as $t) {
                if (Schema::hasTable($t) && DB::table($t)->count() === 0) {
                    DB::statement("ALTER TABLE `{$t}` AUTO_INCREMENT = 1");
                }
            }

            $this->newLine();
            $this->info("=== HOÀN TẤT DỌN SẠCH DỮ LIỆU THÀNH CÔNG (DB TRANSACTION COMMITTED) ===");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("LỖI TRONG QUÁ TRÌNH CLEANUP (ĐÃ TỰ ĐỘNG ROLLBACK): " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
