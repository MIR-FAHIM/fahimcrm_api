<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkImport extends Model
{
    protected $fillable = [
        'module',
        'file_name',
        'uploaded_by',
        'status',
        'total_rows',
        'valid_count',
        'warning_count',
        'failed_count',
        'imported_count',
        'skipped_count',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
    ];

    public function rows()
    {
        return $this->hasMany(BulkImportRow::class);
    }
}
