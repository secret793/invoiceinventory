<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Company;
use App\Models\Device;
use App\Models\Store;
use App\Services\NotificationService;

class DeviceController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters = [
            'status'                 => $req->query('status'),
            'search'                 => $req->query('search'),
            'allocation_point_id'    => $req->query('allocation_point_id'),
            'distribution_point_id'  => $req->query('distribution_point_id'),
            'exclude_unconfigured'   => $req->query('exclude_unconfigured', false),
        ];

        $result = Device::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function stats(Request $req): void
    {
        Response::success(Device::statusCounts());
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Device::findOrFail($id);
        Response::success($row);
    }

    public function store(Request $req): void
    {
        $data = $req->validated([
            'device_type'  => 'required',
            'device_id'    => 'required',
            'date_received'=> 'required',
        ]);

        $user = $req->user();
        $data['user_id']       = $user['id'];
        $data['status']        = $data['status'] ?? 'UNCONFIGURED';
        $data['is_configured'] = isset($data['is_configured']) ? (int) $data['is_configured'] : 0;
        $data['batch_number']  = $data['batch_number'] ?? ('BATCH-' . date('Ymd'));

        $device = Device::create($data);

        // Auto-create Store mirror
        try {
            Store::create([
                'device_id'     => $device['id'],
                'serial_number' => $data['serial_number'] ?? $data['device_id'],
                'device_type'   => $data['device_type'],
                'batch_number'  => $data['batch_number'],
                'date_received' => $data['date_received'],
                'status'        => $data['status'],
                'sim_number'    => $data['sim_number'] ?? null,
                'sim_operator'  => $data['sim_operator'] ?? null,
                'user_id'       => $user['id'],
            ]);
        } catch (\Throwable) {}

        NotificationService::created('Device', $data['device_id']);
        Response::success($device, 'Device created successfully', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        unset($data['id'], $data['created_at']);

        $device = Device::update($id, $data);
        Store::syncFromDevice($device);

        NotificationService::updated('Device', (string) $id);
        Response::success($device, 'Device updated successfully');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Device::findOrFail($id);
        Device::delete($id);
        NotificationService::deleted('Device', (string) $id);
        Response::success(null, 'Device deleted successfully');
    }

    public function bulkStatus(Request $req): void
    {
        $data   = $req->json();
        $ids    = $data['ids']    ?? [];
        $status = $data['status'] ?? '';

        if (empty($ids) || !$status) {
            Response::error('ids and status are required');
        }

        $count = Device::bulkUpdateStatus($ids, $status);

        // Sync to stores
        foreach ($ids as $id) {
            $device = Device::find((int) $id);
            if ($device) Store::syncFromDevice($device);
        }

        Response::success(['updated' => $count], "Status updated for $count device(s)");
    }

    public function bulkDelete(Request $req): void
    {
        $data = $req->json();
        $ids  = $data['ids'] ?? [];
        if (empty($ids)) Response::error('ids are required');

        $count = 0;
        foreach ($ids as $id) {
            $device = Device::find((int) $id);
            if (!$device) continue;
            Device::delete((int) $id);
            $count++;
        }

        Response::success(['deleted' => $count], "$count device(s) deleted");
    }

    public function syncICloud(Request $req): void
    {
        Response::success([], 'iCloud sync initiated (not implemented in standalone mode)');
    }

    public function importTemplate(Request $req): void
    {
        $filename = 'Device_Import_Template.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";

        $headers = ['Device ID', 'Device Type', 'Serial Number', 'Batch Number', 'Date Received', 'Status', 'SIM Number', 'SIM Operator', 'Company', 'Notes'];
        echo implode(',', $headers) . "\r\n";

        // Example rows — Device ID must be numbers only, no length limit
        $examples = [
            ['1001', 'JT701',  'SN-20240001', 'BATCH-' . date('Ymd'), date('Y-m-d'), 'UNCONFIGURED', '',           'Africell', '', ''],
            ['1002', 'JT709A', 'SN-20240002', 'BATCH-' . date('Ymd'), date('Y-m-d'), 'CONFIGURED',   '2207000001', 'Gamcel',   'Banjul Shipping Co.', ''],
            ['1003', 'JT709C', 'SN-20240003', 'BATCH-' . date('Ymd'), date('Y-m-d'), 'ONLINE',       '2207000002', 'QCell',    'Atlantic Trading Ltd.', 'Example note'],
        ];
        foreach ($examples as $row) {
            echo implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n";
        }
        exit;
    }

    public function import(Request $req): void
    {
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error occurred');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'])) {
            Response::error('Only CSV and XLSX files are supported');
        }

        $user = $req->user();
        $rows = ($ext === 'xlsx')
            ? $this->parseXlsx($file['tmp_name'])
            : $this->parseCsv($file['tmp_name']);

        if (empty($rows)) Response::error('File is empty or could not be parsed');

        // First row = headers
        $headers = array_map('strtolower', array_map('trim', array_shift($rows)));
        // Strip UTF-8 BOM from the very first header (Excel saves CSV with BOM)
        if (isset($headers[0])) {
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF\xef\xbb\xbf");
        }
        $headerMap = array_flip($headers);

        $col = function (array $row, string ...$names) use ($headerMap): string {
            foreach ($names as $n) {
                $n = strtolower($n);
                if (isset($headerMap[$n]) && isset($row[$headerMap[$n]])) {
                    return trim($row[$headerMap[$n]]);
                }
            }
            return '';
        };

        $created = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            if (empty(array_filter($row))) continue; // skip blank rows

            $deviceId = $col($row, 'device id', 'device_id', 'id');
            if (!$deviceId) { $errors[] = "Row " . ($i + 2) . ": Device ID is required"; $skipped++; continue; }
            if (!preg_match('/^[0-9]+$/', $deviceId)) {
                $errors[] = "Row " . ($i + 2) . ": Device ID '$deviceId' must contain numbers only";
                $skipped++; continue;
            }

            $deviceType = $col($row, 'device type', 'device_type', 'type');
            if (!$deviceType) { $errors[] = "Row " . ($i + 2) . ": Device Type is required"; $skipped++; continue; }

            $rawDate = $col($row, 'date received', 'date_received', 'date');
            if (!$rawDate) {
                $dateReceived = date('Y-m-d');
            } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawDate, $m)) {
                // DD/MM/YYYY → YYYY-MM-DD
                $dateReceived = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDate)) {
                $dateReceived = $rawDate; // already ISO
            } else {
                $ts = strtotime($rawDate);
                $dateReceived = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
            }

            $status = strtoupper($col($row, 'status'));
            $allowedStatuses = ['UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'];
            if (!in_array($status, $allowedStatuses)) $status = 'UNCONFIGURED';

            $allowedTypes = ['JT701', 'JT709A', 'JT709C'];
            if (!in_array($deviceType, $allowedTypes)) {
                $errors[] = "Row " . ($i + 2) . ": Invalid device type '$deviceType'. Must be one of: " . implode(', ', $allowedTypes);
                $skipped++; continue;
            }

            try {
                $batchNum = $col($row, 'batch number', 'batch_number', 'batch') ?: ('BATCH-' . date('Ymd'));

                // Resolve company by name (case-insensitive)
                $companyId = null;
                $companyName = $col($row, 'company', 'company name', 'company_name');
                if ($companyName) {
                    $company = \App\Core\Database::queryOne(
                        "SELECT id FROM companies WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))",
                        [$companyName]
                    );
                    if ($company) {
                        $companyId = (int) $company['id'];
                    } else {
                        $errors[] = "Row " . ($i + 2) . ": Company '$companyName' not found — device imported without company assignment";
                    }
                }

                $device = Device::create([
                    'device_id'    => $deviceId,
                    'device_type'  => $deviceType,
                    'serial_number'=> $col($row, 'serial number', 'serial_number', 'serial'),
                    'batch_number' => $batchNum,
                    'date_received'=> $dateReceived,
                    'status'       => $status,
                    'sim_number'   => $col($row, 'sim number', 'sim_number', 'sim'),
                    'sim_operator' => $col($row, 'sim operator', 'sim_operator', 'operator'),
                    'notes'        => $col($row, 'notes', 'note'),
                    'company_id'   => $companyId,
                    'user_id'      => $user['id'],
                ]);

                // Auto-create store entry
                try {
                    Store::create([
                        'device_id'     => $device['id'],
                        'serial_number' => $col($row, 'serial number', 'serial_number', 'serial') ?: $deviceId,
                        'device_type'   => $deviceType,
                        'batch_number'  => $batchNum,
                        'date_received' => $dateReceived,
                        'status'        => $status,
                        'sim_number'    => $col($row, 'sim number', 'sim_number', 'sim') ?: null,
                        'sim_operator'  => $col($row, 'sim operator', 'sim_operator', 'operator') ?: null,
                        'user_id'       => $user['id'],
                    ]);
                } catch (\Throwable) {}

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
                $skipped++;
            }
        }

        Response::success([
            'created' => $created,
            'skipped' => $skipped,
            'errors'  => $errors,
        ], "$created device(s) imported" . ($skipped ? ", $skipped skipped" : ''));
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) return [];
        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = $line;
        }
        fclose($handle);
        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) return [];
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $doc = new \DOMDocument();
            @$doc->loadXML($ssXml);
            foreach ($doc->getElementsByTagName('si') as $si) {
                $sharedStrings[] = $si->textContent;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetXml) return [];

        $doc = new \DOMDocument();
        @$doc->loadXML($sheetXml);

        // Build column-letter to index mapping helper
        $colLetterToIndex = function (string $ref): int {
            $col = preg_replace('/[0-9]/', '', $ref);
            $n = 0;
            foreach (str_split(strtoupper($col)) as $c) {
                $n = $n * 26 + (ord($c) - ord('A') + 1);
            }
            return $n - 1;
        };

        $rows = [];
        foreach ($doc->getElementsByTagName('row') as $rowEl) {
            $rowData = [];
            $maxCol  = 0;
            $cells   = [];
            foreach ($rowEl->getElementsByTagName('c') as $cell) {
                $ref  = $cell->getAttribute('r');
                $type = $cell->getAttribute('t');
                $vEl  = $cell->getElementsByTagName('v')->item(0);
                $v    = $vEl ? $vEl->textContent : '';
                if ($type === 's') $v = $sharedStrings[(int) $v] ?? '';
                $idx = $colLetterToIndex($ref);
                $cells[$idx] = $v;
                if ($idx > $maxCol) $maxCol = $idx;
            }
            // Fill gaps
            for ($i = 0; $i <= $maxCol; $i++) {
                $rowData[] = $cells[$i] ?? '';
            }
            $rows[] = $rowData;
        }

        return $rows;
    }
}
