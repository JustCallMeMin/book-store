<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomCategory extends Model
{
    use HasFactory;

    protected $table = 'custom_category'; // nếu bạn dùng tên bảng là custom_category (không theo chuẩn Laravel)

    protected $fillable = [
        'name',
        'url',
        'isActive',
    ];
}
