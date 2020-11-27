<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtensionFiles extends Model
{
    use UsesUuid;

    protected $fillable = [
        "extension_id",
        "name",
        "sha256sum",
        "extension_data"
    ];
}
