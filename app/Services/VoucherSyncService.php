<?php

// =====================================
// VOUCHER SYNC SERVICE - FIXED EXECUTION TIME
// File: app/Services/VoucherSyncService.php (REPLACE existing syncFromSpreadsheet method)
// =====================================

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class VoucherSyncService
{
    private $spreadsheetId;
    private $errors = [];
    private $successCount = 0;
    private $updateCount = 0;
    private $createCount = 0;
    private $skipCount = 0;
    private $processedCodes = [];

    public function __construct()
    {
        $this->spreadsheetId = config('google-sheets.voucher.spreadsheet_id', env('GOOGLE_VOUCHER_SPREADSHEET_ID'));
        
        if (empty($this->spreadsheetId)) {
            throw new Exception('Voucher spreadsheet ID not configured. Please set GOOGLE_VOUCHER_SPREADSHEET_ID in .env file');
        }
    }

    public function syncFromSpreadsheet(array $options = []): array
    {
        $syncLog = null;
        
        try {
            $startTime = now();
            
            $syncLog = VoucherSyncLog::create([
                'sync_type' => 'spreadsheet_to_db',
                'status' => 'running',
                'synced_at' => $startTime,
                'records_processed' => 0,
                'errors_count' => 0
            ]);

            $this->resetCounters();

            $connectionTest = $this->testConnection();
            if (!$connectionTest['success']) {
                throw new Exception($connectionTest['message']);
            }

            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                throw new Exception('No data retrieved from Google Sheets');
            }

            Log::info('VoucherSync: Processing ' . count($data) . ' rows from spreadsheet');

            DB::transaction(function () use ($data) {
                $this->processVoucherRows($data);
            });

            $endTime = now();
            // FIX: Ensure execution time is positive integer
            $executionTimeMs = max(0, abs((int) $endTime->diffInMilliseconds($startTime)));

            // Update sync log with FIXED execution time
            $syncLog->update([
                'status' => count($this->errors) === 0 ? 'success' : 'partial',
                'records_processed' => $this->successCount,
                'errors_count' => count($this->errors),
                'error_details' => !empty($this->errors) ? implode("\n", $this->errors) : null,
                'execution_time_ms' => $executionTimeMs  // Now guaranteed to be positive integer
            ]);

            Log::info('VoucherSync completed', [
                'processed' => $this->successCount,
                'created' => $this->createCount,
                'updated' => $this->updateCount,
                'errors' => count($this->errors),
                'execution_time_ms' => $executionTimeMs
            ]);

            return [
                'success' => true,
                'status' => count($this->errors) === 0 ? 'success' : 'partial',
                'processed' => $this->successCount,
                'created' => $this->createCount,
                'updated' => $this->updateCount,
                'errors' => count($this->errors),
                'error_details' => $this->errors
            ];

        } catch (Exception $e) {
            $endTime = now();
            $executionTimeMs = max(0, abs((int) $endTime->diffInMilliseconds($startTime ?? now())));

            if ($syncLog) {
                $syncLog->update([
                    'status' => 'error',
                    'error_details' => $e->getMessage(),
                    'execution_time_ms' => $executionTimeMs
                ]);
            }

            Log::error('Voucher sync failed', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $this->spreadsheetId,
                'execution_time_ms' => $executionTimeMs
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'processed' => $this->successCount,
                'errors' => count($this->errors) + 1,
                'error_details' => array_merge($this->errors, [$e->getMessage()])
            ];
        }
    }

    public function syncFromSpreadsheetForceNew(array $options = []): array
    {
        $syncLog = null;
        
        try {
            $startTime = now();
            
            $syncLog = VoucherSyncLog::create([
                'sync_type' => 'spreadsheet_to_db_force_new',
                'status' => 'running',
                'synced_at' => $startTime,
                'records_processed' => 0,
                'errors_count' => 0
            ]);

            $this->resetCounters();

            $connectionTest = $this->testConnection();
            if (!$connectionTest['success']) {
                throw new Exception($connectionTest['message']);
            }

            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                throw new Exception('No data retrieved from Google Sheets');
            }

            Log::info('VoucherSync: Force creating new records for ' . count($data) . ' rows from spreadsheet');

            DB::transaction(function () use ($data) {
                $this->processVoucherRowsForceNew($data);
            });

            $endTime = now();
            $executionTimeMs = max(0, abs((int) $endTime->diffInMilliseconds($startTime)));

            $syncLog->update([
                'status' => count($this->errors) === 0 ? 'success' : 'partial',
                'records_processed' => $this->successCount,
                'errors_count' => count($this->errors),
                'error_details' => !empty($this->errors) ? implode("\n", $this->errors) : null,
                'execution_time_ms' => $executionTimeMs
            ]);

            Log::info('VoucherSync force new completed', [
                'processed' => $this->successCount,
                'created' => $this->createCount,
                'updated' => $this->updateCount,
                'errors' => count($this->errors),
                'execution_time_ms' => $executionTimeMs
            ]);

            return [
                'success' => true,
                'status' => count($this->errors) === 0 ? 'success' : 'partial',
                'processed' => $this->successCount,
                'created' => $this->createCount,
                'updated' => $this->updateCount,
                'errors' => count($this->errors),
                'error_details' => $this->errors
            ];

        } catch (Exception $e) {
            $endTime = now();
            $executionTimeMs = max(0, abs((int) $endTime->diffInMilliseconds($startTime ?? now())));

            if ($syncLog) {
                $syncLog->update([
                    'status' => 'error',
                    'error_details' => $e->getMessage(),
                    'execution_time_ms' => $executionTimeMs
                ]);
            }

            Log::error('Voucher sync force new failed', [
                'error' => $e->getMessage(),
                'spreadsheet_id' => $this->spreadsheetId,
                'execution_time_ms' => $executionTimeMs
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'processed' => $this->successCount,
                'errors' => count($this->errors) + 1,
                'error_details' => array_merge($this->errors, [$e->getMessage()])
            ];
        }
    }

    private function processVoucherRowsForceNew(array $data): void
    {
        foreach ($data as $index => $row) {
            try {
                $rowNumber = $index + 2; // +2 karena mulai dari row 2 (skip header)

                $voucherCode = trim($row['voucher_code'] ?? '');
                
                if (empty($voucherCode)) {
                    $this->skipCount++;
                    Log::debug('VoucherSync: Skipping row with empty voucher code', ['row' => $rowNumber]);
                    continue;
                }

                $this->processedCodes[] = $voucherCode;
                $this->forceCreateNewVoucher($row, $rowNumber);
                $this->successCount++;

            } catch (Exception $e) {
                $errorMsg = "Row {$rowNumber}: " . $e->getMessage();
                $this->errors[] = $errorMsg;
                Log::error('VoucherSync: Row processing failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'voucher_code' => $row['voucher_code'] ?? 'unknown'
                ]);
            }
        }
    }

    private function forceCreateNewVoucher(array $row, int $rowNumber): void
    {
        $originalCode = strtoupper(trim($row['voucher_code']));
        
        // Generate unique code for this row
        $uniqueVoucherCode = $this->generateUniqueVoucherCode($originalCode, $row, $rowNumber);
        
        $voucherData = [
            'code_product' => $row['code_product'] ?: 'All product',
            'voucher_code' => $uniqueVoucherCode,
            'name_voucher' => $row['name_voucher'] ?: ('Voucher ' . $uniqueVoucherCode),
            'start_date' => $this->parseDate($row['start']),
            'end_date' => $this->parseDate($row['end']),
            'min_purchase' => $this->parseRupiah($row['min_purchase']),
            'quota' => (int) ($row['quota'] ?: 100),
            'claim_per_customer' => (int) ($row['claim_per_customer'] ?: 1),
            'voucher_type' => strtoupper($row['voucher_type'] ?: 'NOMINAL'),
            'value' => $row['value'] ?: ($row['voucher_type'] === 'NOMINAL' ? 'Rp10.000' : '5%'),
            'discount_max' => $this->parseRupiah($row['discount_max']),
            'category_customer' => $row['category_customer'] ?: 'all customer',
            'spreadsheet_row_id' => $rowNumber,
            'sync_status' => 'synced',
            'is_active' => true
        ];

        // Validate voucher type
        if (!in_array($voucherData['voucher_type'], ['NOMINAL', 'PERCENT'])) {
            $voucherData['voucher_type'] = 'NOMINAL';
        }

        // FORCE CREATE: Always create new record
        Voucher::create($voucherData);
        $this->createCount++;
        
        Log::info('VoucherSync: Forced new voucher created', [
            'unique_code' => $uniqueVoucherCode,
            'original_code' => $originalCode,
            'row' => $rowNumber
        ]);
    }

    private function resetCounters(): void
    {
        $this->errors = [];
        $this->successCount = 0;
        $this->updateCount = 0;
        $this->createCount = 0;
        $this->skipCount = 0;
        $this->processedCodes = [];
    }

    private function fetchGoogleSheetsData(): array
    {
        // Use the specific gid from your spreadsheet URL
        $csvUrls = [
            "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv&gid=1986222516",
            "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv&gid=0",
            "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv"
        ];

        foreach ($csvUrls as $index => $csvUrl) {
            try {
                Log::info("VoucherSync: Trying CSV URL method " . ($index + 1), ['url' => $csvUrl]);
                
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])
                    ->retry(2, 1000)
                    ->get($csvUrl);

                if ($response->successful()) {
                    $csvContent = $response->body();
                    
                    if (!empty($csvContent) && !str_contains($csvContent, '<!DOCTYPE html>')) {
                        Log::info("VoucherSync: Successfully fetched data using method " . ($index + 1));
                        return $this->parseCsvContent($csvContent);
                    }
                }

            } catch (Exception $e) {
                Log::warning("VoucherSync: Method " . ($index + 1) . " threw exception: " . $e->getMessage());
                continue;
            }
        }

        throw new Exception('Could not access Google Spreadsheet data');
    }

    private function parseCsvContent(string $csvContent): array
    {
        if (empty($csvContent)) {
            throw new Exception('Empty CSV content received');
        }

        $temp = tmpfile();
        fwrite($temp, $csvContent);
        rewind($temp);
        
        $data = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($temp, 0, ',', '"')) !== false) {
            $lineNumber++;
            
            if ($lineNumber === 1) {
                $headers = array_map('trim', $row);
                Log::info('VoucherSync: Raw headers detected', ['headers' => $headers]);
                
                // Expected headers based on your spreadsheet:
                // A=code_Product, B=voucher_code, C=name_voucher, D=start, E=end, 
                // F=min_purchase, G=quota, H=claim_per_customer, I=voucher_type, 
                // J=value, K=discount_max, L=category_customer
                
                continue;
            }

            if (empty($row) || count($row) < 2) {
                continue;
            }

            // Ensure we have at least 12 columns (A to L)
            $row = array_pad($row, 12, '');
            
            // Map by position instead of header names
            $rowData = [
                'code_product' => trim($row[0] ?? ''),           // A
                'voucher_code' => trim($row[1] ?? ''),           // B  
                'name_voucher' => trim($row[2] ?? ''),           // C
                'start' => trim($row[3] ?? ''),                  // D
                'end' => trim($row[4] ?? ''),                    // E
                'min_purchase' => trim($row[5] ?? ''),           // F
                'quota' => trim($row[6] ?? ''),                  // G
                'claim_per_customer' => trim($row[7] ?? ''),     // H
                'voucher_type' => trim($row[8] ?? ''),           // I
                'value' => trim($row[9] ?? ''),                  // J
                'discount_max' => trim($row[10] ?? ''),          // K
                'category_customer' => trim($row[11] ?? ''),     // L
            ];
            
            // Check if row has voucher code (minimal requirement)
            if (!empty($rowData['voucher_code'])) {
                $data[] = $rowData;
                Log::debug('VoucherSync: Valid row found', [
                    'line' => $lineNumber,
                    'voucher_code' => $rowData['voucher_code']
                ]);
            } else {
                Log::debug('VoucherSync: Skipping row without voucher code', ['line' => $lineNumber]);
            }
        }

        fclose($temp);

        if (empty($data)) {
            throw new Exception('No valid voucher rows found in spreadsheet. Please check if column B (voucher_code) has data.');
        }

        Log::info('VoucherSync: Data parsed successfully', ['total_rows' => count($data)]);
        return $data;
    }

    private function processVoucherRows(array $data): void
    {
        foreach ($data as $index => $row) {
            try {
                $rowNumber = $index + 2; // +2 karena mulai dari row 2 (skip header)

                $voucherCode = trim($row['voucher_code'] ?? '');
                
                if (empty($voucherCode)) {
                    $this->skipCount++;
                    Log::debug('VoucherSync: Skipping row with empty voucher code', ['row' => $rowNumber]);
                    continue;
                }

                $this->processedCodes[] = $voucherCode;
                $this->createOrUpdateVoucher($row, $rowNumber);
                $this->successCount++;

            } catch (Exception $e) {
                $errorMsg = "Row {$rowNumber}: " . $e->getMessage();
                $this->errors[] = $errorMsg;
                Log::error('VoucherSync: Row processing failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'voucher_code' => $row['voucher_code'] ?? 'unknown'
                ]);
            }
        }
    }

    private function createOrUpdateVoucher(array $row, int $rowNumber): void
    {
        $voucherCode = strtoupper(trim($row['voucher_code']));
        
        // NEW: Create unique voucher code based on row to avoid duplicates
        $uniqueVoucherCode = $this->generateUniqueVoucherCode($voucherCode, $row, $rowNumber);
        
        $voucherData = [
            'code_product' => $row['code_product'] ?: 'All product',
            'voucher_code' => $uniqueVoucherCode,  // Use unique code
            'name_voucher' => $row['name_voucher'] ?: ('Voucher ' . $uniqueVoucherCode),
            'start_date' => $this->parseDate($row['start']),
            'end_date' => $this->parseDate($row['end']),
            'min_purchase' => $this->parseRupiah($row['min_purchase']),
            'quota' => (int) ($row['quota'] ?: 100),
            'claim_per_customer' => (int) ($row['claim_per_customer'] ?: 1),
            'voucher_type' => strtoupper($row['voucher_type'] ?: 'NOMINAL'),
            'value' => $row['value'] ?: ($row['voucher_type'] === 'NOMINAL' ? 'Rp10.000' : '5%'),
            'discount_max' => $this->parseRupiah($row['discount_max']),
            'category_customer' => $row['category_customer'] ?: 'all customer',
            'spreadsheet_row_id' => $rowNumber,
            'sync_status' => 'synced',
            'is_active' => true
        ];

        // Validate voucher type
        if (!in_array($voucherData['voucher_type'], ['NOMINAL', 'PERCENT'])) {
            Log::warning("VoucherSync: Invalid voucher type '{$voucherData['voucher_type']}' for {$uniqueVoucherCode}, defaulting to NOMINAL");
            $voucherData['voucher_type'] = 'NOMINAL';
        }

        // NEW APPROACH: Check by spreadsheet_row_id first, then voucher_code
        $existingVoucher = Voucher::where('spreadsheet_row_id', $rowNumber)->first();
        
        if (!$existingVoucher) {
            // If no voucher exists for this row, check by voucher_code
            $existingVoucher = Voucher::where('voucher_code', $uniqueVoucherCode)->first();
        }

        if ($existingVoucher) {
            // Update existing - preserve usage count
            $currentUsage = $existingVoucher->total_used;
            $existingVoucher->update($voucherData);
            $existingVoucher->update(['total_used' => $currentUsage]);
            $this->updateCount++;
            
            Log::info('VoucherSync: Voucher updated', [
                'code' => $uniqueVoucherCode,
                'row' => $rowNumber,
                'original_code' => $voucherCode
            ]);
        } else {
            // Always create new record for each row
            Voucher::create($voucherData);
            $this->createCount++;
            
            Log::info('VoucherSync: Voucher created', [
                'code' => $uniqueVoucherCode,
                'row' => $rowNumber,
                'original_code' => $voucherCode
            ]);
        }
    }

    private function generateUniqueVoucherCode(string $originalCode, array $row, int $rowNumber): string
{
    return strtoupper(trim($originalCode)); // persis sesuai sheet
}

    private function parseDate($dateString): ?Carbon
    {
        if (empty(trim($dateString))) {
            return null;
        }

        try {
            // Handle format: dd/mm/yyyy,HH:MM:SS,AM/PM
            $dateString = preg_replace('/[,\s]+/', ' ', trim($dateString));
            
            $formats = [
                'd/m/Y H:i:s A',
                'd/m/Y H:i:s',
                'd/m/Y',
                'Y-m-d H:i:s',
                'Y-m-d',
                'm/d/Y H:i:s A',
                'm/d/Y H:i:s',
                'm/d/Y'
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateString);
                } catch (Exception $e) {
                    continue;
                }
            }

            Log::warning("VoucherSync: Could not parse date, using default: {$dateString}");
            return now()->addDays(30);
            
        } catch (Exception $e) {
            Log::warning("VoucherSync: Date parsing error for '{$dateString}': " . $e->getMessage());
            return now()->addDays(30);
        }
    }

    private function parseRupiah($rupiahString): float
    {
        if (empty(trim($rupiahString))) {
            return 0.0;
        }

        // Remove Rp, dots, spaces, commas
        $cleaned = preg_replace('/[Rp\s\.,]/', '', $rupiahString);
        $cleaned = preg_replace('/[^\d]/', '', $cleaned);
        
        return (float) ($cleaned ?: 0);
    }

    public function syncToSpreadsheet(): array
    {
        try {
            $vouchers = Voucher::where('is_active', true)->orderBy('created_at')->get();
            
            Log::info('VoucherSync: Sync to spreadsheet requested', ['voucher_count' => $vouchers->count()]);
            
            return [
                'success' => true,
                'status' => 'success',
                'processed' => $vouchers->count(),
                'message' => 'Sync to spreadsheet feature will be implemented when needed'
            ];
            
        } catch (Exception $e) {
            Log::error('VoucherSync: Sync to spreadsheet failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to sync to spreadsheet: ' . $e->getMessage()
            ];
        }
    }

    public function testConnection(): array
    {
        try {
            $csvUrls = [
                "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv&gid=1986222516",
                "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv&gid=0",
                "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv"
            ];

            $lastError = '';
            foreach ($csvUrls as $csvUrl) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                        ->get($csvUrl);
                    
                    if ($response->successful()) {
                        $content = $response->body();
                        if (!empty($content) && !str_contains($content, '<!DOCTYPE html>')) {
                            return [
                                'success' => true,
                                'message' => 'Connection to Google Spreadsheet successful',
                                'url_used' => $csvUrl
                            ];
                        }
                    }
                    $lastError = 'Status: ' . $response->status();
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                }
            }
            
            return [
                'success' => false,
                'message' => 'Cannot access Google Spreadsheet. Last error: ' . $lastError,
                'spreadsheet_id' => $this->spreadsheetId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    public function createSampleData(): array
    {
        try {
            $sampleVouchers = [
                [
                    'code_product' => 'All product',
                    'voucher_code' => 'SAMPLE50',
                    'name_voucher' => 'Sample Voucher 50k',
                    'start_date' => now(),
                    'end_date' => now()->addMonth(),
                    'min_purchase' => 100000,
                    'quota' => 100,
                    'claim_per_customer' => 1,
                    'voucher_type' => 'NOMINAL',
                    'value' => 'Rp50.000',
                    'discount_max' => 50000,
                    'category_customer' => 'all customer',
                    'is_active' => true,
                    'sync_status' => 'manual'
                ],
                [
                    'code_product' => 'All product',
                    'voucher_code' => 'SAMPLE10PCT',
                    'name_voucher' => 'Sample 10% Discount',
                    'start_date' => now(),
                    'end_date' => now()->addMonth(),
                    'min_purchase' => 200000,
                    'quota' => 50,
                    'claim_per_customer' => 1,
                    'voucher_type' => 'PERCENT',
                    'value' => '10%',
                    'discount_max' => 100000,
                    'category_customer' => 'all customer',
                    'is_active' => true,
                    'sync_status' => 'manual'
                ]
            ];

            foreach ($sampleVouchers as $voucherData) {
                Voucher::updateOrCreate(
                    ['voucher_code' => $voucherData['voucher_code']],
                    $voucherData
                );
            }

            return [
                'success' => true,
                'message' => 'Sample voucher data created successfully',
                'created' => count($sampleVouchers)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create sample data: ' . $e->getMessage()
            ];
        }
    }
}