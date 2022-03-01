<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestModels;

use byteit\LaravelExtendedStateMachines\Tests\database\factories\SalesManagerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableAlias;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesManager extends Model implements AuthenticatableAlias
{
    use Authenticatable, HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return SalesManagerFactory::new();
    }

}
