<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageJob extends Model
{
    //
    protected $fillable = [
        'project_id',
        'token',
        'prompt',
        'size',
        'provider',
        'status',
        'result_url',
        'error',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}