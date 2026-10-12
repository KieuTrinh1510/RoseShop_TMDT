<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomMixImage extends Model
{
    use HasFactory;

    protected $table = 'custom_mix_images';

    protected $fillable = [
        'ma_bo_hoa',
        'path',
        'original_name',
        'file_size',
        'sort_order',
    ];

    protected $casts = [
        'ma_bo_hoa'  => 'integer',
        'file_size'  => 'integer',
        'sort_order' => 'integer',
    ];

    public function boHoa()
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
