<?php

namespace App\Models\Base;

use Illuminate\Foundation\Auth\User as Authenticatable;

class BaseModel extends Authenticatable
{
    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
