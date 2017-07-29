<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'messsage_id';

    protected $fillable = ['user_id','received','replied'];
}
