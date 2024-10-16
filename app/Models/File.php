<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'type',
        'planning_id'
    ];

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }
}
