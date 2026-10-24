<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BotKnowledge;
use App\Models\MauBoHoa;
use App\Models\SanPham;
use App\Models\TinTuc;
use App\Models\PhuPhiBoHoa;

class SyncBotKnowledgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:sync-knowledge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ và biên soạn dữ liệu từ Database vào bảng Tri thức RAG cho Chatbot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Bắt đầu quá trình đồng bộ dữ liệu RAG cho Chatbot RoseShop...');

        $this->syncMauBoHoa();
        $this->syncSanPham();
        $this->syncTinTuc();
        $this->syncPhuPhi();
        $this->syncChinhSachVaFaq();

        $total = BotKnowledge::count();
        $this->info("✅ Đã hoàn tất đồng bộ! Tổng số tài liệu tri thức trong Knowledge Base: {$total}");

        return Command::SUCCESS;
    }

    /**
     * Đồng bộ mẫu bó hoa (Entity Chunking từ mau_bo_hoa + chi_tiet_mau_bo_hoa + san_pham)
     */
    protected function syncMauBoHoa()
    {
        $mauBoHoas = MauBoHoa::with(['chiTietMauBoHoa.sanPham'])->get();
        $count = 0;

        foreach ($mauBoHoas as $mau) {
            $thanhPhanHoa = [];
            $cacLoaiHoaKeywords = [];

            if ($mau->chiTietMauBoHoa) {
                foreach ($mau->chiTietMauBoHoa as $ct) {
                    $tenSp = $ct->sanPham ? $ct->sanPham->ten_san_pham : 'Hoa phụ';
                    $thanhPhanHoa[] = "- {$tenSp} (Số lượng: {$ct->so_luong})";
                    $cacLoaiHoaKeywords[] = $tenSp;
                }
            }

            $thanhPhanStr = !empty($thanhPhanHoa) ? implode("\n", $thanhPhanHoa) : 'Đang cập nhật chi tiết cành hoa.';

            $kieuBoText = match($mau->kieu_bo_hoa) {
                'ROUND' => 'Bó tròn (Round)',
                'NATURAL' => 'Phong cách tự nhiên (Natural)',
                'CONE' => 'Bó dài hình nón (Cone)',
                'CASCADING' => 'Dáng thác đổ (Cascading)',
                'ASYMMETRIC' => 'Bất đối xứng hiện đại (Asymmetric)',
                'COMPACT' => 'Bó nhỏ gọn (Compact)',
                'LONG_STEM' => 'Dáng cành dài quý phái (Long Stem)',
                default => $mau->kieu_bo_hoa ?? 'Tiêu chuẩn',
            };

            $noiDung = "Mẫu bó hoa: {$mau->ten_mau}\n"
                     . "Kiểu dáng: {$kieuBoText}\n"
                     . "Kích thước (Size): {$mau->size}\n"
                     . "Concept & Chủ đề: " . ($mau->concept ?? 'Tất cả các dịp, sinh nhật, tình yêu, khai trương, tốt nghiệp') . "\n"
                     . "Mô tả thiết kế: " . ($mau->mo_ta ?? 'Thiết kế hoa tươi chọn lọc, phối màu nghệ thuật, sang trọng và tinh tế.') . "\n"
                     . "Thành phần hoa và phụ kiện:\n{$thanhPhanStr}";

            $keywords = implode(', ', array_unique(array_filter([
                $mau->ten_mau,
                $mau->concept,
                $kieuBoText,
                'Size ' . $mau->size,
                'bó hoa',
                'hoa tươi',
                ...$cacLoaiHoaKeywords
            ])));

            BotKnowledge::updateOrCreate(
                ['ma_dinh_danh' => 'mau_bo_hoa_' . $mau->ma_mau_bo_hoa],
                [
                    'loai' => 'MAU_BO_HOA',
                    'tieu_de' => 'Mẫu bó hoa: ' . $mau->ten_mau,
                    'noi_dung' => $noiDung,
                    'tu_khoa' => $keywords,
                    'metadata' => [
                        'id' => $mau->ma_mau_bo_hoa,
                        'ten_mau' => $mau->ten_mau,
                        'kieu_bo' => $mau->kieu_bo_hoa,
                        'size' => $mau->size,
                        'concept' => $mau->concept,
                        'url' => route('customer.mau-bo-hoa.chi-tiet', $mau->ma_mau_bo_hoa, false),
                    ]
                ]
            );
            $count++;
        }

        $this->line("  -> Đã đồng bộ {$count} mẫu bó hoa.");
    }

    /**
     * Đồng bộ danh mục hoa lẻ & sản phẩm
     */
    protected function syncSanPham()
    {
        $sanPhams = SanPham::all();
        $count = 0;

        foreach ($sanPhams as $sp) {
            $loaiStr = match($sp->loai_san_pham) {
                'HOA_TUOI' => 'Hoa tươi cắt cành',
                'HOA_GIA' => 'Hoa lụa nghệ thuật',
                'HOA_SAP' => 'Hoa sáp thơm cao cấp',
                'CAY_CANH' => 'Cây cảnh mini để bàn',
                'CHAU_HOA_TUOI' => 'Chậu hoa tươi trang trí',
                'PHU_KIEN' => 'Phụ kiện cắm hoa & giấy gói',
                'QUA_TANG' => 'Quà tặng kèm (gấu bông, thiệp, nến thơm)',
                default => $sp->loai_san_pham,
            };

            $giaFormatted = number_format($sp->gia_ban_hien_tai ?? 0, 0, ',', '.') . ' VNĐ';
            $tinhTrangKho = ($sp->so_luong > 0) ? "Còn hàng sẵn tại shop (tồn kho: {$sp->so_luong} {$sp->don_vi_tinh})" : "Tạm hết hàng lẻ, có thể đặt trước";

            $noiDung = "Sản phẩm: {$sp->ten_san_pham}\n"
                     . "Phân loại: {$loaiStr}\n"
                     . "Giá bán: {$giaFormatted} / {$sp->don_vi_tinh}\n"
                     . "Tình trạng: {$tinhTrangKho}\n"
                     . "Mô tả đặc điểm: " . ($sp->mo_ta ?? 'Sản phẩm hoa tươi chất lượng cao tuyển chọn từ Đà Lạt và nhập khẩu.');

            $keywords = "{$sp->ten_san_pham}, {$loaiStr}, {$sp->don_vi_tinh}, hoa tươi, quà tặng";

            BotKnowledge::updateOrCreate(
                ['ma_dinh_danh' => 'san_pham_' . $sp->ma_san_pham],
                [
                    'loai' => 'SAN_PHAM',
                    'tieu_de' => 'Sản phẩm: ' . $sp->ten_san_pham,
                    'noi_dung' => $noiDung,
                    'tu_khoa' => $keywords,
                    'metadata' => [
                        'id' => $sp->ma_san_pham,
                        'ten_san_pham' => $sp->ten_san_pham,
                        'loai' => $sp->loai_san_pham,
                        'gia_ban' => $sp->gia_ban_hien_tai,
                        'don_vi_tinh' => $sp->don_vi_tinh,
                        'hinh_anh' => $sp->hinh_anh,
                        'url' => route('customer.san-pham.chi-tiet', $sp->ma_san_pham, false),
                    ]
                ]
            );
            $count++;
        }

        $this->line("  -> Đã đồng bộ {$count} sản phẩm hoa & quà tặng.");
    }

    /**
     * Đồng bộ cẩm nang & tin tức
     */
    protected function syncTinTuc()
    {
        $tinTucs = TinTuc::where('trang_thai', 'HIEN_THI')->get();
        $count = 0;

        foreach ($tinTucs as $tt) {
            $noiDungClean = strip_tags($tt->noi_dung ?? $tt->tom_tat ?? '');
            $noiDung = "Bài viết: {$tt->tieu_de}\n"
                     . "Tóm tắt: {$tt->tom_tat}\n"
                     . "Nội dung chia sẻ: {$noiDungClean}";

            BotKnowledge::updateOrCreate(
                ['ma_dinh_danh' => 'tin_tuc_' . $tt->ma_tin_tuc],
                [
                    'loai' => 'TIN_TUC',
                    'tieu_de' => 'Bài viết cẩm nang: ' . $tt->tieu_de,
                    'noi_dung' => $noiDung,
                    'tu_khoa' => "tin tức, cẩm nang hoa tươi, {$tt->tieu_de}, cách chăm sóc hoa",
                    'metadata' => [
                        'id' => $tt->ma_tin_tuc,
                        'tieu_de' => $tt->tieu_de,
                    ]
                ]
            );
            $count++;
        }

        $this->line("  -> Đã đồng bộ {$count} bài viết cẩm nang.");
    }

    /**
     * Đồng bộ bảng phụ phí bó hoa
     */
    protected function syncPhuPhi()
    {
        $phuPhis = PhuPhiBoHoa::all();
        if ($phuPhis->isEmpty()) return;

        $lines = [];
        foreach ($phuPhis as $pp) {
            $giaStr = number_format((float) ($pp->phu_phi ?? 0), 0, ',', '.') . ' VNĐ';
            $lines[] = "- {$pp->loai} ({$pp->gia_tri}): Phụ phí {$giaStr}";
        }

        $noiDung = "BẢNG QUY ĐỊNH PHỤ PHÍ THIẾT KẾ BÓ HOA:\n"
                 . "Khi khách hàng tự tạo bó hoa theo ý thích (Custom Mix) hoặc yêu cầu kích thước/kiểu dáng đặc biệt, chi phí gia công và vật liệu gói hoa được tính như sau:\n"
                 . implode("\n", $lines) . "\n"
                 . "Đã bao gồm công cắm hoa của nghệ nhân hoa tươi, nơ ruy băng cao cấp và thiệp chúc mừng thiết kế riêng.";

        BotKnowledge::updateOrCreate(
            ['ma_dinh_danh' => 'bang_phu_phi_bo_hoa'],
            [
                'loai' => 'CHINH_SACH',
                'tieu_de' => 'Quy định phụ phí gia công và thiết kế bó hoa',
                'noi_dung' => $noiDung,
                'tu_khoa' => 'phụ phí, tự mix bó hoa, giá cắm hoa, công thợ, ruy băng, giấy gói, kích thước, size bó hoa',
                'metadata' => ['type' => 'phu_phi']
            ]
        );

        $this->line("  -> Đã đồng bộ bảng phụ phí bó hoa.");
    }

    /**
     * Đồng bộ các chính sách và FAQs tiêu chuẩn của RoseShop
     */
    protected function syncChinhSachVaFaq()
    {
        $docs = [
            [
                'id' => 'chinh_sach_giao_hang',
                'tieu_de' => 'Chính sách vận chuyển và giao hoa tươi RoseShop',
                'noi_dung' => "CHÍNH SÁCH VẬN CHUYỂN & GIAO HOA:\n"
                            . "- Giao hoa hỏa tốc: Cửa hàng hỗ trợ giao nhanh trong vòng 60 - 90 phút đối với khu vực nội thành.\n"
                            . "- Giao hẹn giờ chính xác: Khách hàng có thể hẹn giờ giao cụ thể cho các dịp bất ngờ, tỏ tình, tiệc sinh nhật.\n"
                            . "- Bảo quản trong lúc vận chuyển: Hoa luôn được bọc lớp màng bảo vệ, gắn túi trữ nước giữ tươi và giao bằng xe máy hoặc ô tô chuyên dụng để tránh dập nát.\n"
                            . "- Phí vận chuyển: Miễn phí giao hàng cho đơn từ 500.000 VNĐ trong bán kính 5km. Các khu vực xa hơn áp dụng phí tính theo biểu phí tiêu chuẩn.",
                'tu_khoa' => 'giao hàng, ship hoa, hỏa tốc, thời gian giao hoa, phí ship, vận chuyển'
            ],
            [
                'id' => 'chinh_sach_doi_tra_bao_hanh',
                'tieu_de' => 'Chính sách đổi trả, bảo hành và hoàn tiền',
                'noi_dung' => "CHÍNH SÁCH ĐỔI TRẢ & BẢO HÀNH HOA TƯƠI:\n"
                            . "- Cam kết chất lượng: RoseShop cam kết 100% hoa tươi mới nhập trong ngày từ Đà Lạt và trang trại chất lượng cao.\n"
                            . "- Đổi mới hoặc hoàn tiền ngay: Nếu hoa nhận được bị dập nát, gãy cành, héo úa hoặc không đúng mẫu đã đặt, khách hàng chỉ cần chụp ảnh hoặc quay video gửi lại cho shop trong vòng 2 giờ kể từ khi nhận hàng.\n"
                            . "- Shop sẽ đổi ngay bó hoa mới miễn phí hoặc hoàn tiền 100% trong 24 giờ làm việc.",
                'tu_khoa' => 'đổi trả, hoàn tiền, hoa héo, hoa dập, bảo hành, bồi thường, khiếu nại'
            ],
            [
                'id' => 'huong_dan_tu_mix_bo_hoa',
                'tieu_de' => 'Hướng dẫn tính năng tự mix bó hoa theo ý thích (Custom Bouquet)',
                'noi_dung' => "TÍNH NĂNG TỰ TẠO BÓ HOA THEO SỞ THÍCH:\n"
                            . "- Khách hàng có thể vào mục 'Bó hoa tự chọn' trên thanh điều hướng của website.\n"
                            . "- Bước 1: Chọn kiểu dáng bó (Tròn, Tự nhiên, Dài, Thác đổ...).\n"
                            . "- Bước 2: Chọn kích cỡ (Size S, M, L, XL).\n"
                            . "- Bước 3: Tự do chọn các loại hoa chính, hoa phụ và số lượng từng cành hoa.\n"
                            . "- Bước 4: Chọn màu giấy gói, màu nơ và viết lời nhắn chúc mừng gửi kèm.\n"
                            . "- Hệ thống tự động tính giá tức thời và hiển thị ảnh mô phỏng.",
                'tu_khoa' => 'tự mix hoa, tự tạo bó hoa, custom bó hoa, cắm hoa theo yêu cầu, chọn hoa'
            ],
            [
                'id' => 'chinh_sach_thanh_toan',
                'tieu_de' => 'Chính sách thanh toán VNPAY và COD',
                'noi_dung' => "PHƯƠNG THỨC THANH TOÁN TẠI ROSESHOP:\n"
                            . "- Thanh toán online VNPAY: Hỗ trợ quét mã VNPAY-QR qua các ứng dụng ngân hàng và ví điện tử, an toàn tiện lợi.\n"
                            . "- Thanh toán khi nhận hàng (COD): Áp dụng cho các đơn hàng tiêu chuẩn.\n"
                            . "- Lưu ý về COD: Đối với các đơn hoa thiết kế đặc biệt có giá trị lớn (> 1.000.000 VNĐ) hoặc khách hàng có lịch sử hủy đơn, shop có thể yêu cầu đặt cọc trước một phần để bảo đảm.",
                'tu_khoa' => 'thanh toán, vnpay, cod, chuyển khoản, tiền mặt, đặt cọc'
            ],
            [
                'id' => 'huong_dan_bao_quan_hoa',
                'tieu_de' => 'Bí quyết chăm sóc và giữ hoa tươi lâu tại nhà',
                'noi_dung' => "MẸO GIỮ HOA TƯƠI LÂU:\n"
                            . "1. Cắt vát cành: Dùng kéo sắc cắt vát chéo gốc 45 độ dưới vòi nước để tăng diện tích hút nước.\n"
                            . "2. Nước cắm hoa: Dùng nước sạch, có thể cho thêm một gói dưỡng hoa hoặc vài giọt nước cốt chanh / nửa viên aspirin.\n"
                            . "3. Vị trí đặt: Đặt bình hoa nơi thoáng mát, tránh ánh nắng trực tiếp, tránh xa quạt gió mạnh hoặc luồng gió điều hòa phả thẳng vào hoa.\n"
                            . "4. Thay nước: Nên thay nước cho bình hoa mỗi ngày một lần và tỉa bỏ những cánh hoa bên ngoài đã héo.",
                'tu_khoa' => 'bảo quản hoa, giữ hoa tươi lâu, cách cắm hoa, thay nước, dưỡng hoa'
            ]
        ];

        foreach ($docs as $doc) {
            BotKnowledge::updateOrCreate(
                ['ma_dinh_danh' => $doc['id']],
                [
                    'loai' => 'CHINH_SACH',
                    'tieu_de' => $doc['tieu_de'],
                    'noi_dung' => $doc['noi_dung'],
                    'tu_khoa' => $doc['tu_khoa'],
                    'metadata' => ['policy_code' => $doc['id']]
                ]
            );
        }

        $this->line("  -> Đã đồng bộ " . count($docs) . " tài liệu chính sách & FAQs.");
    }
}
