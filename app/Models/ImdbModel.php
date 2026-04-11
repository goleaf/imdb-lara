<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ImdbModel extends Model
{
    protected $connection = 'imdb_mysql';

    public $timestamps = false;
}
