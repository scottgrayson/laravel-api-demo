<?php

namespace App\Models;

use App\Traits\UuidModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

class User extends Eloquent implements Authenticatable
{
    use Notifiable, UuidModel, AuthenticableTrait;

    protected $hidden = ['password'];
}
