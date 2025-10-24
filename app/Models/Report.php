<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public $timestamps = false; // karena migration ga pakai updated_at

    protected $fillable = [
        'report_type',
        'user_id',
        'period_start',
        'period_end',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}