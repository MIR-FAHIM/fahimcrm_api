<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    protected $table = 'prospects';

    protected $fillable = [
        'prospect_name',
        'is_individual',
        'industry_type_id',
        'interested_for_id',
        'information_source_id',
        'website_link',
        'facebook_page',
        'linkedin',
        'zone_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'note',
        'is_active',
        'is_opportunity',
        'status',
        'stage_id',
        'priority_id',
        'last_activity',
    ];

    protected $casts = [
        'is_individual' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Example relationships (uncomment & modify if needed)
    public function industryType()
    {
        return $this->belongsTo(IndustryType::class);
    }
    public function stage()
    {
        return $this->belongsTo(ProspectStage::class);
    }
    public function logActivities()
    {
        return $this->hasMany(ProspectLogActivity::class);
    }

    public function concernPersons()
    {
        return $this->hasMany(AddProspectContact::class, 'prospect_id');
    }

    public function interestedFor()
    {
        return $this->belongsTo(ProductItem::class, 'interested_for_id');
    }

    public function informationSource()
    {
        return $this->belongsTo(InformationSource::class, 'information_source_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
