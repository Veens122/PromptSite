<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    //
    protected $fillable = [
        'user_id',
        'title',
        'prompt',
        'raw_html',
        'slug',
        'html',
        'images',
        'status',
    ];

    public function imageJobs()
    {
        return $this->hasMany(ImageJob::class);
    }

    protected $casts = [
        'images' => 'array',
    ];
}
