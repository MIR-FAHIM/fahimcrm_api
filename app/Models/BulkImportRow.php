<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkImportRow extends Model
{
    protected $fillable = [
        'bulk_import_id',
        'row_number',
        'row_type',
        'match_key',
        'status',
        'raw_data',
        'normalized_data',
        'errors',
        'warnings',
        'created_record_id',
        'created_contact_id',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'normalized_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
    ];

    public function bulkImport()
    {
        return $this->belongsTo(BulkImport::class);
    }
}
