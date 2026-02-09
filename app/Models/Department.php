<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['organization_id', 'name', 'description'];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function jobPostings() { return $this->hasMany(JobPosting::class); }
    public function employees() { return $this->hasMany(Employee::class); }
}
