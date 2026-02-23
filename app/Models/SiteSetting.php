<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $fillable = [
        'banner_image',
    ];
}
