<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_id',
        'date',
        'information',
        'bruto_amount',
        'tax_amount',
        'netto_amount',
        'category',
        'document_evidence',
        'image_evidence',
        'isAddition'
    ];

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }
}
