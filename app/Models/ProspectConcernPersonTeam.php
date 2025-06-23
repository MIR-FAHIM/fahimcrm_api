<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProspectConcernPersonTeam extends Model
{
    protected $table = 'prospect_concern_person_teams';

    protected $fillable = [
        'prospect_id',
        'employee_id',
        'is_active',
        'notify',
    ];

    /**
     * Get the prospect associated with the team member.
     */
    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    /**
     * Get the employee associated with the team.
     */
    public function employee()
    {
        return $this->belongsTo(User::class);
    }
}
