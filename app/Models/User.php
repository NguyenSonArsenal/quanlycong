<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseModel
{
    use SoftDeletes;

    protected $table = 'user';

    const GENDER_BOY = 1;
    const GENDER_GIRL = 2;

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'gender',
        'address',
    ];
}
