<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_name',
        'transaction_type',
        'amount',
        'tax_amount',
        'document_evidence',
        'image_evidence',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
