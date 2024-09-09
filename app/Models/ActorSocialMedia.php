<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActorSocialMedia extends Model
{
    use HasFactory;

    protected $table = 'actor_social_media';
    protected $fillable = ['actor_id', 'social_media_platform_id', 'link'];
}

