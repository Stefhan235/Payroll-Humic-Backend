<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'deadline',
        'target_amount',
        'content'
    ];

    public function files()
    {
        return $this->hasMany(File::class);
    }
}
