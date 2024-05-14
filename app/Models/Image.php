<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = [
        'url',
        'type',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo('imageable');
    }
}
