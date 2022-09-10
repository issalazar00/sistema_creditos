<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $table = 'credits';

    protected $fillable = [
        'client_id',
        'debtor_id',
        'headquarter_id',
        'user_id',
        'provider_id',
        'number_installments',
        'number_paid_installments',
        'day_limit',
        'debtor',
        'status',
        'start_date',
        'interest',
        'annual_interest_percentage',
        'porcentaje_interes_mensual',
        'installment_value',
        'credit_value',
        'paid_value',
        'capital_value',
        'interest_value',
        'disbursement_date',
        'finish_date',
        'description',
        'provider'
    ];

    protected $with = [];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function headquarter()
    {
        return $this->belongsTo(Headquarter::class, 'headquarter_id');
    }
    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function credit_provider()
    {
        return $this->hasOne(CreditProvider::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function guarantees()
    {
        return $this->belongsToMany(Guarantee::class);
    }

    public function debtors()
    {
        return $this->belongsToMany(Client::class, 'credit_debtor', 'id', 'debtor_id');
    }

}
