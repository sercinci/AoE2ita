<?php
namespace Entity;

use Illuminate\Database\Eloquent\Model;

/**
* Database teams
*/
class Team extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    
    /**
     * Team's users
     */
    public function members()
    {
        return $this->belongsToMany('Entity\User', 'members');
    }

    /**
     * Team's tournament
     */
    public function tournament()
    {
        return $this->belongsTo('Entity\Tournament');
    }
}