<?php

namespace App\Http\Controllers;

use App\Models\AddProspectContact;
use App\Models\BulkImport;
use App\Models\BulkImportRow;
use App\Models\Client;
use App\Models\Prospect;
use App\Models\ProspectStageChangeLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class ProspectBulkImportController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'uploaded_by' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'csv', 'txt'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Only .xlsx, .csv, or .txt files are supported.',
            ], 422);
        }

        try {
            $rows = $extension === 'xlsx'
                ? $this->readXlsxRows($file->getRealPath())
                : $this->readCsvRows($file->getRealPath());

            if (count($rows) < 2) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'The uploaded file has no import rows.',
                ], 422);
            }

            $headers = $this->normalizeHeaders(array_shift($rows));
            $seenContacts = [];
            $rowModels = [];
            $summary = [
                'total_rows' => 0,
                'valid_rows' => 0,
                'warning_rows' => 0,
                'failed_rows' => 0,
                'new_prospects' => 0,
                'existing_prospects' => 0,
                'contacts_to_add' => 0,
                'contacts_to_skip' => 0,
            ];

            $import = BulkImport::create([
                'module' => 'prospect',
                'file_name' => $file->getClientOriginalName(),
                'uploaded_by' => $request->input('uploaded_by'),
                'status' => 'previewed',
            ]);

            foreach ($rows as $index => $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rawData = $this->combineRow($headers, $row);
                $result = $this->normalizeAndValidateRow($rawData, $seenContacts);
                $status = count($result['errors']) ? 'failed' : (count($result['warnings']) ? 'warning' : 'valid');

                $summary['total_rows']++;
                $summary[$status === 'failed' ? 'failed_rows' : ($status === 'warning' ? 'warning_rows' : 'valid_rows')]++;

                if (in_array('Existing prospect will be updated.', $result['warnings'])) {
                    $summary['existing_prospects']++;
                } else {
                    $summary['new_prospects']++;
                }

                if ($result['normalized_data']['contact']) {
                    if (in_array('Contact email already exists and will be skipped.', $result['warnings'])) {
                        $summary['contacts_to_skip']++;
                    } else {
                        $summary['contacts_to_add']++;
                    }
                }

                $rowModels[] = BulkImportRow::create([
                    'bulk_import_id' => $import->id,
                    'row_number' => $index + 2,
                    'row_type' => 'prospect_contact',
                    'match_key' => $result['match_key'],
                    'status' => $status,
                    'raw_data' => $rawData,
                    'normalized_data' => $result['normalized_data'],
                    'errors' => $result['errors'],
                    'warnings' => $result['warnings'],
                ]);
            }

            $import->update([
                'total_rows' => $summary['total_rows'],
                'valid_count' => $summary['valid_rows'],
                'warning_count' => $summary['warning_rows'],
                'failed_count' => $summary['failed_rows'],
                'summary' => $summary,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect import preview generated successfully.',
                'import_id' => $import->id,
                'summary' => $summary,
                'rows' => $rowModels,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview prospect import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'import_id' => 'required|exists:bulk_imports,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'errors' => $validator->errors()], 422);
        }

        $import = BulkImport::where('module', 'prospect')->findOrFail($request->import_id);

        if ($import->status === 'completed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'This import has already been completed.',
            ], 400);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($import->rows()->whereIn('status', ['valid', 'warning'])->orderBy('row_number')->get() as $row) {
            try {
                DB::transaction(function () use ($row, &$imported, &$skipped) {
                    $data = $row->normalized_data;
                    $prospectData = $data['prospect'];
                    $prospectData['status'] = $this->nullableIntegerValue($prospectData['status'] ?? null)
                        ?? $this->defaultProspectStageStatusId();
                    $prospectData['stage_id'] = $this->nullableIntegerValue($prospectData['stage_id'] ?? null)
                        ?? $this->defaultProspectStageStatusId();
                    $contactData = $data['contact'];
                    $isClient = (bool) ($data['is_client'] ?? false);

                    $prospect = Prospect::where('prospect_name', $prospectData['prospect_name'])->first();

                    if ($prospect) {
                        $prospect->fill($this->filledValues($prospectData));
                        $prospect->save();
                    } else {
                        $prospect = Prospect::create($prospectData);

                        ProspectStageChangeLog::create([
                            'prospect_id' => $prospect->id,
                            'old_stage' => null,
                            'new_stage' => $prospect->stage_id,
                            'changed_by' => 1,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }

                    if ($isClient && !Client::where('prospect_id', $prospect->id)->exists()) {
                        Client::create([
                            'prospect_id' => $prospect->id,
                            'client_code' => uniqid('CLT-'),
                            'status' => 'active',
                            'isActive' => true,
                        ]);
                    }

                    $contact = null;

                    if ($contactData) {
                        $emailExists = AddProspectContact::where('email', $contactData['email'])->exists();

                        if ($emailExists) {
                            $skipped++;
                        } else {
                            $contactData['prospect_id'] = $prospect->id;
                            $contact = AddProspectContact::create($contactData);
                        }
                    }

                    $row->update([
                        'status' => 'imported',
                        'created_record_id' => $prospect->id,
                        'created_contact_id' => $contact?->id,
                    ]);

                    $imported++;
                });
            } catch (Exception $e) {
                $row->update([
                    'status' => 'failed',
                    'errors' => array_merge($row->errors ?? [], [$e->getMessage()]),
                ]);
            }
        }

        $skipped += $import->rows()->where('status', 'failed')->count();
        $failedRows = $import->rows()->where('status', 'failed')->count();

        $import->update([
            'status' => $failedRows ? 'completed_with_errors' : 'completed',
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'failed_count' => $failedRows,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Prospect import completed.',
            'summary' => [
                'imported_rows' => $imported,
                'skipped_rows' => $skipped,
                'failed_rows' => $failedRows,
            ],
        ], 200);
    }

    public function history(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => BulkImport::where('module', 'prospect')->latest()->limit(30)->get(),
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $import = BulkImport::where('module', 'prospect')->with('rows')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $import,
        ], 200);
    }

    public function template()
    {
        $headers = [
            'prospect_key',
            'prospect_name',
            'industry_type_id',
            'interested_for_id',
            'information_source_id',
            'division_name',
            'district_name',
            'thana_name',
            'upazila_name',
            'website_link',
            'facebook_page',
            'linkedin',
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
            'contact_person_name',
            'contact_designation_id',
            'contact_mobile',
            'contact_email',
            'contact_note',
            'influencing_role_id',
            'birth_date',
            'anniversary',
            'is_switched_job',
            'attitude_id',
        ];

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, [
                'ABC-001',
                'ABC Company',
                '',
                '',
                '',
                'Dhaka',
                'Dhaka',
                'Dhanmondi',
                '',
                'https://abc.com',
                '',
                '',
                '',
                '',
                'Dhaka',
                'Imported prospect',
                '1',
                '0',
                '1',
                '',
                '',
                '',
                'Mr. Rahim',
                '',
                '017xxxxxxxx',
                'rahim@example.com',
                'Primary contact',
                '',
                '',
                '',
                '0',
                '',
            ]);
            fclose($handle);
        }, 'prospect_bulk_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function normalizeAndValidateRow(array $rawData, array &$seenContacts): array
    {
        $errors = [];
        $warnings = [];
        $prospectName = $this->clean($rawData['prospect_name'] ?? null);
        $prospectKey = $this->clean($rawData['prospect_key'] ?? null);
        $matchKey = strtolower($prospectKey ?: $prospectName);

        if (!$prospectName) {
            $errors[] = 'prospect_name is required.';
        }

        $locationIds = $this->resolveLocationIds($rawData, $warnings);

        $prospectData = [
            'prospect_name' => $prospectName,
            'is_individual' => false,
            'industry_type_id' => $this->nullableId($rawData['industry_type_id'] ?? null, 'industry_types', $errors),
            'interested_for_id' => $this->nullableId($rawData['interested_for_id'] ?? null, 'product_items', $errors),
            'information_source_id' => $this->nullableId($rawData['information_source_id'] ?? null, 'information_sources', $errors),
            'website_link' => $this->clean($rawData['website_link'] ?? null),
            'facebook_page' => $this->clean($rawData['facebook_page'] ?? null),
            'linkedin' => $this->clean($rawData['linkedin'] ?? null),
            'zone_id' => $this->nullableIdOrWarning($rawData['zone_id'] ?? null, 'zones', $warnings),
            'division_id' => $locationIds['division_id'],
            'district_id' => $locationIds['district_id'],
            'thana_id' => $locationIds['thana_id'],
            'type' => 'prospect',
            'latitude' => $this->nullableNumber($rawData['latitude'] ?? null, 'latitude', $errors),
            'longitude' => $this->nullableNumber($rawData['longitude'] ?? null, 'longitude', $errors),
            'address' => $this->clean($rawData['address'] ?? null),
            'note' => $this->clean($rawData['note'] ?? null),
            'is_active' => $this->booleanValue($rawData['is_active'] ?? true),
            'is_opportunity' => $this->booleanValue($rawData['is_opportunity'] ?? false),
            'status' => $this->nullableIntegerOrDefaultWarning($rawData['status'] ?? null, 'Prospect status', $warnings),
            'stage_id' => $this->nullableProspectStageIdOrDefault($rawData['stage_id'] ?? null, $warnings),
            'priority_id' => $this->nullableId($rawData['priority_id'] ?? null, 'priorities', $errors),
            'last_activity' => $this->clean($rawData['last_activity'] ?? null),
        ];

        if ($prospectName && Prospect::where('prospect_name', $prospectName)->exists()) {
            $warnings[] = 'Existing prospect will be updated.';
        }

        $contactData = $this->normalizeContact($rawData, $errors, $warnings, $seenContacts);

        return [
            'match_key' => $matchKey,
            'normalized_data' => [
                'prospect' => $prospectData,
                'contact' => $contactData,
                'is_client' => false,
            ],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function normalizeContact(array $rawData, array &$errors, array &$warnings, array &$seenContacts): ?array
    {
        $personName = $this->clean($rawData['contact_person_name'] ?? $rawData['person_name'] ?? null);
        $mobile = $this->clean($rawData['contact_mobile'] ?? $rawData['mobile'] ?? null);
        $email = $this->clean($rawData['contact_email'] ?? $rawData['email'] ?? null);
        $hasContact = $personName || $mobile || $email;

        if (!$hasContact) {
            return null;
        }

        if (!$personName) {
            $errors[] = 'contact_person_name is required when contact data is provided.';
        }

        if (!$mobile) {
            $errors[] = 'contact_mobile is required when contact data is provided.';
        }

        if (!$email) {
            $errors[] = 'contact_email is required when contact data is provided.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'contact_email is invalid.';
        }

        $emailKey = strtolower((string) $email);

        if ($emailKey && isset($seenContacts[$emailKey])) {
            $warnings[] = 'Duplicate contact email in file; confirm will import the first available row only.';
        }

        if ($emailKey) {
            $seenContacts[$emailKey] = true;
        }

        if ($email && AddProspectContact::where('email', $email)->exists()) {
            $warnings[] = 'Contact email already exists and will be skipped.';
        }

        return [
            'person_name' => $personName,
            'designation_id' => $this->nullableId($rawData['contact_designation_id'] ?? $rawData['designation_id'] ?? null, 'designations', $errors),
            'mobile' => $mobile,
            'email' => $email,
            'note' => $this->clean($rawData['contact_note'] ?? null),
            'is_primary' => false,
            'is_responsive' => true,
            'influencing_role_id' => $this->nullableId($rawData['influencing_role_id'] ?? null, 'influencing_roles', $errors),
            'birth_date' => $this->clean($rawData['birth_date'] ?? null),
            'anniversary' => $this->clean($rawData['anniversary'] ?? null),
            'is_switched_job' => $this->booleanValue($rawData['is_switched_job'] ?? false),
            'attitude_id' => $this->nullableId($rawData['attitude_id'] ?? null, 'attitudes', $errors, false),
            'is_key_contact' => false,
        ];
    }

    private function resolveLocationIds(array $rawData, array &$warnings): array
    {
        $divisionInput = $this->firstClean($rawData, ['division_name', 'division', 'division_id']);
        $districtInput = $this->firstClean($rawData, ['district_name', 'district', 'district_id']);
        $thanaInput = $this->firstClean($rawData, ['thana_name', 'upazila_name', 'thana', 'upazila', 'thana_id']);

        $divisionId = $this->resolveIdOrName(
            $divisionInput,
            'divisions',
            'Division',
            $warnings
        );

        $districtId = $this->resolveIdOrName(
            $districtInput,
            'districts',
            'District',
            $warnings,
            $divisionId ? ['division_id' => $divisionId] : []
        );

        $thanaId = $this->resolveIdOrName(
            $thanaInput,
            'upazilas',
            'Thana/Upazila',
            $warnings,
            $districtId ? ['district_id' => $districtId] : []
        );

        return [
            'division_id' => $divisionId,
            'district_id' => $districtId,
            'thana_id' => $thanaId,
        ];
    }

    private function resolveIdOrName($value, string $table, string $label, array &$warnings, array $constraints = []): ?int
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $query = DB::table($table);

        foreach ($constraints as $column => $constraintValue) {
            $query->where($column, $constraintValue);
        }

        if (ctype_digit((string) $value)) {
            $exists = (clone $query)->where('id', (int) $value)->exists();

            if ($exists) {
                return (int) $value;
            }

            $warnings[] = "{$label} ID {$value} was not found. It will be saved empty.";
            return null;
        }

        $match = $this->findLocationNameMatch($query, $value);

        if ($match) {
            return (int) $match->id;
        }

        $warnings[] = "{$label} \"{$value}\" was not found. It will be saved empty.";
        return null;
    }

    private function findLocationNameMatch($query, string $value)
    {
        $normalizedValue = $this->normalizeMatchText($value);
        $candidates = $query->select('id', 'name', 'bn_name')->get();

        foreach ($candidates as $candidate) {
            if ($this->normalizeMatchText($candidate->name ?? '') === $normalizedValue) {
                return $candidate;
            }

            if ($this->normalizeMatchText($candidate->bn_name ?? '') === $normalizedValue) {
                return $candidate;
            }
        }

        return null;
    }

    private function firstClean(array $data, array $keys)
    {
        foreach ($keys as $key) {
            $value = $this->clean($data[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new Exception('PHP Zip extension is required to import Excel files.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new Exception('Unable to open Excel file.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if (!$sheetXml) {
            $zip->close();
            throw new Exception('The Excel file must contain data in the first sheet.');
        }

        $xml = simplexml_load_string($sheetXml);
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];

        foreach ($xml->xpath('//x:sheetData/x:row') as $rowNode) {
            $row = [];

            foreach ($rowNode->xpath('x:c') as $cell) {
                $cellRef = (string) $cell['r'];
                $columnIndex = $this->columnIndex($cellRef);
                $type = (string) $cell['t'];
                $value = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $value = (string) $cell->is->t;
                }

                $row[$columnIndex] = $value;
            }

            if ($row) {
                ksort($row);
                $rows[] = array_map(fn ($index) => $row[$index] ?? '', range(0, max(array_keys($row))));
            }
        }

        $zip->close();

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');

        if (!$xmlString) {
            return [];
        }

        $xml = simplexml_load_string($xmlString);
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];

        foreach ($xml->xpath('//x:si') as $item) {
            $textParts = [];

            foreach ($item->xpath('.//x:t') as $text) {
                $textParts[] = (string) $text;
            }

            $strings[] = implode('', $textParts);
        }

        return $strings;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string) $header), '_'));
        }, $headers);
    }

    private function combineRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if (!$header) {
                continue;
            }

            $data[$header] = $row[$index] ?? null;
        }

        return $data;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->clean($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function clean($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'undefined' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function nullableId($value, string $table, array &$errors, bool $validateExists = true): ?int
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            $errors[] = "{$table} id must be a valid integer.";
            return null;
        }

        if ($validateExists && Schema::hasTable($table) && !DB::table($table)->where('id', (int) $value)->exists()) {
            $errors[] = "{$table} id {$value} was not found.";
            return null;
        }

        return (int) $value;
    }

    private function nullableIdOrWarning($value, string $table, array &$warnings): ?int
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            $warnings[] = "{$table} id {$value} is not numeric. It will be saved empty.";
            return null;
        }

        if (Schema::hasTable($table) && !DB::table($table)->where('id', (int) $value)->exists()) {
            $warnings[] = "{$table} id {$value} was not found. It will be saved empty.";
            return null;
        }

        return (int) $value;
    }

    private function nullableIntegerOrWarning($value, string $label, array &$warnings): ?int
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            $warnings[] = "{$label} \"{$value}\" is not numeric. It will be saved empty.";
            return null;
        }

        return (int) $value;
    }

    private function nullableIntegerOrDefaultWarning($value, string $label, array &$warnings): int
    {
        $defaultStatusId = $this->defaultProspectStageStatusId();
        $value = $this->clean($value);

        if ($value === null) {
            return $defaultStatusId;
        }

        if (!ctype_digit((string) $value)) {
            $warnings[] = "{$label} \"{$value}\" is not numeric. Default status ID {$defaultStatusId} will be used.";
            return $defaultStatusId;
        }

        return (int) $value;
    }

    private function nullableProspectStageIdOrDefault($value, array &$warnings): int
    {
        $defaultStageId = $this->defaultProspectStageStatusId();
        $value = $this->clean($value);

        if ($value === null) {
            return $defaultStageId;
        }

        if (!ctype_digit((string) $value)) {
            $warnings[] = "Prospect stage \"{$value}\" is not numeric. Default stage ID {$defaultStageId} will be used.";
            return $defaultStageId;
        }

        if (Schema::hasTable('prospect_stages') && !DB::table('prospect_stages')->where('id', (int) $value)->exists()) {
            $warnings[] = "Prospect stage ID {$value} was not found. Default stage ID {$defaultStageId} will be used.";
            return $defaultStageId;
        }

        return (int) $value;
    }

    private function nullableIntegerValue($value): ?int
    {
        $value = $this->clean($value);

        if ($value === null || !ctype_digit((string) $value)) {
            return null;
        }

        return (int) $value;
    }

    private function defaultProspectStageStatusId(): int
    {
        if (Schema::hasTable('prospect_stages')) {
            $statusId = DB::table('prospect_stages')->orderBy('id')->value('id');

            if ($statusId) {
                return (int) $statusId;
            }
        }

        return 1;
    }

    private function nullableNumber($value, string $field, array &$errors): ?float
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            $errors[] = "{$field} must be numeric.";
            return null;
        }

        return (float) $value;
    }

    private function booleanValue($value): int
    {
        $value = strtolower((string) $this->clean($value));

        return in_array($value, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
    }

    private function normalizeMatchText($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }

    private function filledValues(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/[A-Z]+/', $cellRef, $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
