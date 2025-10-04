<?php

namespace Src\Infra\Eloquent;

use Database\Factories\PlanModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanModel extends Model
{
    use HasFactory;
    protected $table = 'plans';

    protected $fillable = [
        'description', 'numberOfClients', 'gigabytesStorage', 'price', 'active',
    ];

    protected static function newFactory(): PlanModelFactory
    {
        return PlanModelFactory::new();
    }
}
