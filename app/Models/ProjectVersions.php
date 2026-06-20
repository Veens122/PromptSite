<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectVersions extends Model
{
    //
    protected $fillable = [
        'project_id',
        'version_number',
        'html_content',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
