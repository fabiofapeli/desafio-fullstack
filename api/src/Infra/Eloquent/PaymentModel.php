<?php

namespace Src\Infra\Eloquent;

use Database\Factories\PaymentModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'contract_id',
        'action',
        'type',
        'plan_value',
        'price',
        'credit',
        'payment_at',
        'status',
    ];

    protected $casts = [
        'payment_at' => 'datetime',
        'plan_value' => 'float',
        'credit' => 'float',
        'price' => 'float',
    ];


    protected static function newFactory()
    {
        return PaymentModelFactory::new();
    }

    public function contract()
    {
        return $this->belongsTo(ContractModel::class, 'contract_id');
    }
}
