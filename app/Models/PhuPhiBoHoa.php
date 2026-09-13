<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhuPhiBoHoa extends Model
{
    use HasFactory;

    protected $table = 'phu_phi_bo_hoa';

    protected $primaryKey = 'ma_phu_phi';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'loai',
        'gia_tri',
        'phu_phi',
    ];
}