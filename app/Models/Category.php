<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends BaseModel
{
    const TYPE_TEAM = 1;
    const TYPE_SITE = 2;

    protected $guarded = [
        'created_at', 'updated_at'
    ];
}
