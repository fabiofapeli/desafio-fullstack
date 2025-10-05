<?php

namespace Src\Infra\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'contract_id',
        'type',
        'price',
        'payment_at',
        'status',
    ];

    protected $casts = [
        'payment_at' => 'datetime',
        'price' => 'float',
    ];

    public function contract()
    {
        return $this->belongsTo(ContractModel::class, 'contract_id');
    }
}
