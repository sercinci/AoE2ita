<?php
namespace Entity;

use Illuminate\Database\Eloquent\Model;

/**
* Database tournaments
*/
class Tournament extends Model
{
    public $incrementing = false;

    /**
     * Tournament owner
     */
    public function user()
    {
        return $this->belongsTo('Entity\User');
    }

    /**
     * Tournament's teams
     */
    public function teams()
    {
        return $this->hasMany('Entity\Team');
    }
}