<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemObjects extends Model
{
    use UsesUuid;

    protected $fillable = [
        "type", "data"
    ];
}
