<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'profile_picture', 'birthdate', 'gender', 'expected_price', 'notes','social_media','categories_ids'];


    public function getAgeAttribute()
    {
        return Carbon::parse($this->birthdate)->age;
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
