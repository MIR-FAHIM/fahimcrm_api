<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddProspectContact extends Model
{
    // Define the table name (optional, if it doesn't follow Laravel's default convention)
    protected $table = 'add_prospect_contacts';
   
    // Define the fillable fields (for mass assignment)
    protected $fillable = [
        'prospect_id',
        'person_name',
        'designation_id',
        'mobile',
        'email',
        'note',
        'is_primary',
        'is_responsive',
        'influencing_role_id',
        'birth_date',
        'anniversary',
        'is_switched_job',
        'attitude_id',
        'is_key_contact',
    ];

    // Define any relationships, if applicable
    // public function designation()
    // {
    //     return $this->belongsTo(Designation::class, 'designation_id');
    // }

    // public function attitude()
    // {
    //     return $this->belongsTo(Attitude::class, 'attitude_id');
    // }
    
    // Optionally, you can define custom attributes or methods if necessary
}
