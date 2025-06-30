<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskImages extends Model
{
    // Optional if your table name is not plural of class name
    protected $table = 'task_images';

    // Fields that can be mass-assigned
    protected $fillable = [
        'task_id',
        'image_file',
        'image_url',
        'is_active',
        'note',
    ];

    // If timestamps are used (true by default)
    public $timestamps = true;

    // Relationship (assuming a Task model exists)
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }
}
