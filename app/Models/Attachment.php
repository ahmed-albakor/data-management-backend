<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['actor_id', 'file_path', 'type', 'description'];

    public function actor()
    {
        return $this->belongsTo(Actor::class);
    }
}
