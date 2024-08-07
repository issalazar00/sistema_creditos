<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'headquarter_id',
        'user_id',
        'description',
        'date',
        'type_output',
        'price',
        'status',
        'type_movement'
    ];

    protected $dates = [
        'date' => 'date:Y-m-d'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function headquarter()
    {
        return $this->belongsTo(Headquarter::class, 'headquarter_id');
    }
}
