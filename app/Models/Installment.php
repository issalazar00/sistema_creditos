<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
  use HasFactory;

  protected $fillable = [
    'credit_id',
    'installment_number',
    'value',
    'payment_date',
    'days_past_due',
    'late_interests_value',
    'interest_value',
    'additional_interest_value',
    'capital_value',
    'paid_balance',
    'paid_capital',
    'payment_register',
    'status',
    'capital_balance',
    'payment_commitment',
    'collected_by'
  ];

  protected $appends = [
    'days_past_due_calculated'
  ];

  public function credit()
  {
    return $this->belongsTo(Credit::class, 'credit_id');
  }

  public function collectedBy()
  {
    return $this->belongsTo(User::class, 'collected_by');
  }

  public function getDaysPastDueCalculatedAttribute()
  {
    $now = now();
    $payment_date = Carbon::parse($this->payment_date);
    $days_past_due = 0;
    if (($payment_date < $now) && $this->status != 1) {
      $days_past_due = $this->days_past_due ? $this->days_past_due :  $now->diffInDays($payment_date);
    }
    return  $days_past_due + $this->days_past_due;
  }

  public function changeStatus()
  {
    $this->status = 1;
    $this->save();
  }
}
