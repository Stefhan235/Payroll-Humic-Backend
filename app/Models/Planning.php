<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'deadline',
        'target_amount',
        'content'
    ];

    public function files()
    {
        return $this->hasMany(File::class);
    }

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}
