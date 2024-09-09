<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaPlatform extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function actors()
    {
        return $this->belongsToMany(Actor::class, 'actor_social_media')
                    ->withPivot('link')
                    ->withTimestamps();
    }
}
