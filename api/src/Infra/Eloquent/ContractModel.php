<?php

namespace Src\Infra\Eloquent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractModel extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    protected $fillable = [
        'user_id',
        'plan_id',
        'started_at',
        'expiration_date',
        'ended_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expiration_date' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(PlanModel::class, 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentModel::class, 'contract_id');
    }
}
