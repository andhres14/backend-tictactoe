<?php

namespace App\Models\MySQL;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $table = "players";
    protected $fillable = ['nick'];
}
