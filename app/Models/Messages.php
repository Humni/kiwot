<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    protected $fillable = ['user_id','received','replied'];
}
