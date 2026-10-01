<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table      = 'thong_bao';
    protected $primaryKey = 'ma_thong_bao';
    public $timestamps    = false;

    protected $fillable = ['ma_nguoi_dung', 'loai', 'noi_dung', 'ma_hoa_don', 'da_doc', 'thoi_gian'];
    protected $casts    = ['da_doc' => 'boolean', 'thoi_gian' => 'datetime'];
}