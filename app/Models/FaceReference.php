<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceReference extends Model
{
    protected $fillable = ['user_id', 'embedding', 'source', 'image_path'];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
