<?php

namespace Src\Infra\Eloquent;

use Database\Factories\UserModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = ['name', 'email'];

    protected static function newFactory()
    {
        return UserModelFactory::new();
    }
}
