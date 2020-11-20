<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoEngine extends Model
{
    use UsesUuid;

    protected $fillable = [
        "enabled"
    ];
}
