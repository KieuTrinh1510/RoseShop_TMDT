<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CauHinhHeThong extends Model
{
    protected $table = 'cau_hinh_he_thong';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
