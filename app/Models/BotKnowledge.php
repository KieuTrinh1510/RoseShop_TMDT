<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotKnowledge extends Model
{
    use HasFactory;

    protected $table = 'bot_knowledge';

    protected $fillable = [
        'ma_dinh_danh',
        'loai',
        'tieu_de',
        'noi_dung',
        'tu_khoa',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
