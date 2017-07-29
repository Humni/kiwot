<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversations extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'conversation_id';

    protected $fillable = ['user_id','subject','lat','lon', 'last_active'];
}
