<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(JobRequest::class, 'job_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
