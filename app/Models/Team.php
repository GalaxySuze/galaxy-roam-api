<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends BaseModel
{
    protected $guarded = [
        'created_at', 'updated_at'
    ];

    /**
     * @return mixed
     */
    public static function getData()
    {
        $data = Team::paginate(1);
        return $data;
    }
}
