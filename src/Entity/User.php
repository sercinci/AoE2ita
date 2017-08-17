<?php
namespace Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;


/**
* Database users
*/
class User extends Model
{
    use SoftDeletes;
    use Sluggable;
    use SluggableScopeHelpers;

    protected $dates = ['deleted_at'];
    public $incrementing = false;
    //protected $fillable = ['avatar'];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'username'
            ]
        ];
    }

    /**
     * User's tournaments
     */
    public function tournaments()
    {
        return $this->hasMany('Entity\Tournament');
    }

    /**
     * Users's teams
     */
    public function teams()
    {
        return $this->belongsToMany('Entity\Team', 'members');
    }
}