<?php

use App\Core\Router;

$router = Router::getInstance();

// ── Auth ──────────────────────────────────────────────────────────────────────
$router->post('/api/auth/login',  [\App\Controllers\AuthController::class, 'login']);
$router->post('/api/auth/logout', [\App\Controllers\AuthController::class, 'logout'], ['auth']);
$router->get('/api/auth/me',      [\App\Controllers\AuthController::class, 'me'],     ['auth']);

// ── Sidebar ───────────────────────────────────────────────────────────────────
$router->get('/api/sidebar', [\App\Controllers\SidebarController::class, 'index'], ['auth']);

// ── Devices ───────────────────────────────────────────────────────────────────
$router->get('/api/devices',                     [\App\Controllers\DeviceController::class, 'index'],          ['auth']);
$router->get('/api/devices/stats',               [\App\Controllers\DeviceController::class, 'stats'],          ['auth']);
$router->get('/api/devices/import-template',     [\App\Controllers\DeviceController::class, 'importTemplate'], ['auth']);
$router->post('/api/devices',                    [\App\Controllers\DeviceController::class, 'store'],          ['auth']);
$router->post('/api/devices/bulk-status',        [\App\Controllers\DeviceController::class, 'bulkStatus'],     ['auth']);
$router->post('/api/devices/bulk-delete',        [\App\Controllers\DeviceController::class, 'bulkDelete'],     ['auth']);
$router->post('/api/devices/sync-icloud',        [\App\Controllers\DeviceController::class, 'syncICloud'],     ['auth']);
$router->post('/api/devices/import',             [\App\Controllers\DeviceController::class, 'import'],         ['auth']);
$router->get('/api/devices/{id}',                [\App\Controllers\DeviceController::class, 'show'],           ['auth']);
$router->put('/api/devices/{id}',                [\App\Controllers\DeviceController::class, 'update'],         ['auth']);
$router->patch('/api/devices/{id}',              [\App\Controllers\DeviceController::class, 'update'],         ['auth']);
$router->delete('/api/devices/{id}',             [\App\Controllers\DeviceController::class, 'destroy'],        ['auth']);

// ── Store / Device Stock ───────────────────────────────────────────────────────
$router->get('/api/stores',               [\App\Controllers\StoreController::class, 'index'],       ['auth']);
$router->get('/api/stores/stats',         [\App\Controllers\StoreController::class, 'stats'],       ['auth']);
$router->post('/api/stores',              [\App\Controllers\StoreController::class, 'store'],       ['auth']);
$router->post('/api/stores/bulk-status',  [\App\Controllers\StoreController::class, 'bulkStatus'], ['auth']);
$router->post('/api/stores/bulk-delete',  [\App\Controllers\StoreController::class, 'bulkDelete'], ['auth']);
$router->get('/api/stores/{id}',          [\App\Controllers\StoreController::class, 'show'],        ['auth']);
$router->put('/api/stores/{id}',          [\App\Controllers\StoreController::class, 'update'],      ['auth']);
$router->patch('/api/stores/{id}',        [\App\Controllers\StoreController::class, 'update'],      ['auth']);
$router->delete('/api/stores/{id}',       [\App\Controllers\StoreController::class, 'destroy'],     ['auth']);

// ── Other Items ───────────────────────────────────────────────────────────────
$router->get('/api/other-items',              [\App\Controllers\OtherItemController::class, 'index'],       ['auth']);
$router->get('/api/other-items/stats',        [\App\Controllers\OtherItemController::class, 'stats'],       ['auth']);
$router->post('/api/other-items',             [\App\Controllers\OtherItemController::class, 'store'],       ['auth']);
$router->post('/api/other-items/bulk-status', [\App\Controllers\OtherItemController::class, 'bulkStatus'], ['auth']);
$router->put('/api/other-items/{id}',         [\App\Controllers\OtherItemController::class, 'update'],      ['auth']);
$router->patch('/api/other-items/{id}',       [\App\Controllers\OtherItemController::class, 'update'],      ['auth']);
$router->delete('/api/other-items/{id}',      [\App\Controllers\OtherItemController::class, 'destroy'],     ['auth']);

// ── Transfers ─────────────────────────────────────────────────────────────────
$router->get('/api/transfers',                [\App\Controllers\TransferController::class, 'index'],            ['auth']);
$router->post('/api/transfers/bulk-cancel',   [\App\Controllers\TransferController::class, 'bulkCancel'],       ['auth']);
$router->post('/api/transfers/bulk-approve',  [\App\Controllers\TransferController::class, 'bulkApprove'],      ['auth']);
$router->post('/api/transfers/bulk-to-dp',    [\App\Controllers\TransferController::class, 'bulkTransferToDP'], ['auth']);
$router->delete('/api/transfers/{id}',        [\App\Controllers\TransferController::class, 'destroy'],          ['auth']);

// ── Distribution Points ───────────────────────────────────────────────────────
$router->get('/api/distribution-points',                         [\App\Controllers\DistributionPointController::class, 'index'],             ['auth']);
$router->post('/api/distribution-points',                        [\App\Controllers\DistributionPointController::class, 'store'],             ['auth']);
$router->get('/api/distribution-points/{id}',                    [\App\Controllers\DistributionPointController::class, 'show'],              ['auth']);
$router->get('/api/distribution-points/{id}/devices',            [\App\Controllers\DistributionPointController::class, 'devices'],           ['auth']);
$router->get('/api/distribution-points/{id}/status-counts',      [\App\Controllers\DistributionPointController::class, 'statusCounts'],      ['auth']);
$router->post('/api/distribution-points/{id}/accept-devices',    [\App\Controllers\DistributionPointController::class, 'acceptDevices'],     ['auth']);
$router->post('/api/distribution-points/{id}/send-to-ap',        [\App\Controllers\DistributionPointController::class, 'sendToAllocationPoint'], ['auth']);
$router->post('/api/distribution-points/{id}/return-inventory',  [\App\Controllers\DistributionPointController::class, 'returnToInventory'], ['auth']);
$router->post('/api/distribution-points/{id}/accept-returned',   [\App\Controllers\DistributionPointController::class, 'acceptReturned'],    ['auth']);
$router->post('/api/distribution-points/{id}/reject-devices',    [\App\Controllers\DistributionPointController::class, 'rejectDevices'],     ['auth']);
$router->post('/api/distribution-points/{id}/change-status',     [\App\Controllers\DistributionPointController::class, 'changeStatus'],      ['auth']);
$router->post('/api/distribution-points/{id}/send-to-another-dp',[\App\Controllers\DistributionPointController::class, 'sendToAnotherDP'],  ['auth']);
$router->put('/api/distribution-points/{id}',                    [\App\Controllers\DistributionPointController::class, 'update'],            ['auth']);
$router->patch('/api/distribution-points/{id}',                  [\App\Controllers\DistributionPointController::class, 'update'],            ['auth']);
$router->delete('/api/distribution-points/{id}',                 [\App\Controllers\DistributionPointController::class, 'destroy'],           ['auth']);

// ── Allocation Points ─────────────────────────────────────────────────────────
$router->get('/api/allocation-points',                        [\App\Controllers\AllocationPointController::class, 'index'],            ['auth']);
$router->post('/api/allocation-points',                       [\App\Controllers\AllocationPointController::class, 'store'],            ['auth']);
$router->get('/api/allocation-points/{id}',                   [\App\Controllers\AllocationPointController::class, 'show'],             ['auth']);
$router->get('/api/allocation-points/{id}/devices',           [\App\Controllers\AllocationPointController::class, 'devices'],          ['auth']);
$router->get('/api/allocation-points/{id}/status-counts',     [\App\Controllers\AllocationPointController::class, 'statusCounts'],     ['auth']);
$router->post('/api/allocation-points/{id}/send-to-ap',       [\App\Controllers\AllocationPointController::class, 'sendToAP'],         ['auth']);
$router->post('/api/allocation-points/{id}/return-inventory', [\App\Controllers\AllocationPointController::class, 'returnToInventory'], ['auth']);
$router->post('/api/allocation-points/{id}/change-status',    [\App\Controllers\AllocationPointController::class, 'changeStatus'],     ['auth']);
$router->post('/api/allocation-points/{id}/collect',          [\App\Controllers\AllocationPointController::class, 'collectDevices'],     ['auth']);
$router->put('/api/allocation-points/{id}',                   [\App\Controllers\AllocationPointController::class, 'update'],           ['auth']);
$router->patch('/api/allocation-points/{id}',                 [\App\Controllers\AllocationPointController::class, 'update'],           ['auth']);
$router->delete('/api/allocation-points/{id}',                [\App\Controllers\AllocationPointController::class, 'destroy'],          ['auth']);

// ── Data Entry ────────────────────────────────────────────────────────────────
$router->get('/api/data-entry',                          [\App\Controllers\DataEntryController::class, 'index'],        ['auth']);
$router->post('/api/data-entry',                         [\App\Controllers\DataEntryController::class, 'store'],        ['auth']);
$router->get('/api/data-entry/{id}',                     [\App\Controllers\DataEntryController::class, 'show'],         ['auth']);
$router->put('/api/data-entry/{id}',                     [\App\Controllers\DataEntryController::class, 'update'],       ['auth']);
$router->patch('/api/data-entry/{id}',                   [\App\Controllers\DataEntryController::class, 'update'],       ['auth']);
$router->delete('/api/data-entry/{id}',                  [\App\Controllers\DataEntryController::class, 'destroy'],      ['auth']);
$router->post('/api/data-entry/{id}/assign-to-agent',    [\App\Controllers\DataEntryController::class, 'assignToAgent'], ['auth']);
$router->post('/api/data-entry/{id}/return',             [\App\Controllers\DataEntryController::class, 'returnDevice'],  ['auth']);
$router->get('/api/data-entry/{id}/dispatch-logs',       [\App\Controllers\DataEntryController::class, 'dispatchLogs'], ['auth']);
$router->get('/api/data-entry/{id}/receipts',            [\App\Controllers\DataEntryController::class, 'receipts'],     ['auth']);

// ── Confirmed Affixed ─────────────────────────────────────────────────────────
$router->get('/api/confirmed-affixed',                  [\App\Controllers\ConfirmedAffixedController::class, 'index'],           ['auth']);
$router->post('/api/confirmed-affixed',                 [\App\Controllers\ConfirmedAffixedController::class, 'store'],           ['auth']);
$router->get('/api/confirmed-affixed/report',           [\App\Controllers\ConfirmedAffixedController::class, 'report'],          ['auth']);
$router->get('/api/confirmed-affixed/export',           [\App\Controllers\ConfirmedAffixedController::class, 'exportReport'],    ['auth']);
$router->get('/api/confirmed-affixed/{id}',             [\App\Controllers\ConfirmedAffixedController::class, 'show'],            ['auth']);
$router->post('/api/confirmed-affixed/{id}/pick',       [\App\Controllers\ConfirmedAffixedController::class, 'pickForAffixing'], ['auth']);
$router->post('/api/confirmed-affixed/{id}/return',     [\App\Controllers\ConfirmedAffixedController::class, 'returnData'],      ['auth']);

// ── Device Retrievals ─────────────────────────────────────────────────────────
$router->get('/api/device-retrievals',                               [\App\Controllers\DeviceRetrievalController::class, 'index'],            ['auth']);
$router->post('/api/device-retrievals',                              [\App\Controllers\DeviceRetrievalController::class, 'store'],            ['auth']);
$router->get('/api/device-retrievals/report',                        [\App\Controllers\DeviceRetrievalController::class, 'report'],           ['auth']);
$router->get('/api/device-retrievals/export',                        [\App\Controllers\DeviceRetrievalController::class, 'exportReport'],     ['auth']);
$router->get('/api/device-retrievals/overstay-devices',              [\App\Controllers\DeviceRetrievalController::class, 'overstayDevices'],  ['auth']);
$router->get('/api/device-retrievals/{id}',                          [\App\Controllers\DeviceRetrievalController::class, 'show'],             ['auth']);
$router->put('/api/device-retrievals/{id}',                          [\App\Controllers\DeviceRetrievalController::class, 'update'],           ['auth']);
$router->patch('/api/device-retrievals/{id}',                        [\App\Controllers\DeviceRetrievalController::class, 'update'],           ['auth']);
$router->delete('/api/device-retrievals/{id}',                       [\App\Controllers\DeviceRetrievalController::class, 'destroy'],          ['auth']);
$router->post('/api/device-retrievals/{id}/generate-invoice',        [\App\Controllers\DeviceRetrievalController::class, 'generateInvoice'],  ['auth']);
$router->get('/api/device-retrievals/{id}/invoice',                  [\App\Controllers\DeviceRetrievalController::class, 'invoice'],          ['auth']);
$router->get('/api/device-retrievals/{id}/download-invoice',         [\App\Controllers\DeviceRetrievalController::class, 'downloadInvoice'],  ['auth']);
$router->post('/api/device-retrievals/{id}/retrieve',                [\App\Controllers\DeviceRetrievalController::class, 'retrieve'],         ['auth']);
$router->post('/api/device-retrievals/{id}/return-outstation',       [\App\Controllers\DeviceRetrievalController::class, 'returnToOutstation'],['auth']);
$router->post('/api/device-retrievals/{id}/waiver',                  [\App\Controllers\DeviceRetrievalController::class, 'waiver'],           ['auth']);
$router->post('/api/device-retrievals/{id}/approve-payment',         [\App\Controllers\DeviceRetrievalController::class, 'approvePayment'],   ['auth']);
$router->post('/api/device-retrievals/{id}/manual-overstay',         [\App\Controllers\DeviceRetrievalController::class, 'manualOverstayDays'],['auth']);
$router->get('/api/device-retrievals/{id}/check-last-device',        [\App\Controllers\DeviceRetrievalController::class, 'checkLastDevice'],   ['auth']);

// ── Monitoring ────────────────────────────────────────────────────────────────
$router->get('/api/monitoring',                [\App\Controllers\MonitoringController::class, 'index'],   ['auth']);
$router->post('/api/monitoring/{id}/add-note', [\App\Controllers\MonitoringController::class, 'addNote'], ['auth']);

// ── Notifications ─────────────────────────────────────────────────────────────
$router->get('/api/notifications',              [\App\Controllers\NotificationController::class, 'index'],       ['auth']);
$router->get('/api/notifications/unread-count', [\App\Controllers\NotificationController::class, 'unreadCount'], ['auth']);
$router->post('/api/notifications/bulk-read',   [\App\Controllers\NotificationController::class, 'bulkRead'],    ['auth']);
$router->post('/api/notifications/bulk-unread', [\App\Controllers\NotificationController::class, 'bulkUnread'],  ['auth']);
$router->post('/api/notifications/{id}/read',   [\App\Controllers\NotificationController::class, 'markRead'],    ['auth']);
$router->post('/api/notifications/{id}/unread', [\App\Controllers\NotificationController::class, 'markUnread'],  ['auth']);

// ── Receipts ──────────────────────────────────────────────────────────────────
$router->get('/api/receipts',         [\App\Controllers\ReceiptController::class, 'index'],   ['auth']);
$router->post('/api/receipts',        [\App\Controllers\ReceiptController::class, 'store'],   ['auth']);
$router->get('/api/receipts/{id}',    [\App\Controllers\ReceiptController::class, 'show'],    ['auth']);
$router->put('/api/receipts/{id}',    [\App\Controllers\ReceiptController::class, 'update'],  ['auth']);
$router->patch('/api/receipts/{id}',  [\App\Controllers\ReceiptController::class, 'update'],  ['auth']);
$router->delete('/api/receipts/{id}', [\App\Controllers\ReceiptController::class, 'destroy'], ['auth']);
$router->get('/api/receipts/{id}/pdf',[\App\Controllers\ReceiptController::class, 'pdf'],     ['auth']);

// ── Invoices ──────────────────────────────────────────────────────────────────
$router->get('/api/invoices',           [\App\Controllers\InvoiceController::class, 'index'],    ['auth']);
$router->get('/api/invoices/{id}',      [\App\Controllers\InvoiceController::class, 'show'],     ['auth']);
$router->post('/api/invoices/generate', [\App\Controllers\InvoiceController::class, 'generate'], ['auth']);
$router->get('/api/invoices/{id}/pdf',  [\App\Controllers\InvoiceController::class, 'pdf'],      ['auth']);

// ── Configuration ─────────────────────────────────────────────────────────────
$router->get('/api/routes',            [\App\Controllers\RouteController::class, 'index'],   ['auth']);
$router->post('/api/routes',           [\App\Controllers\RouteController::class, 'store'],   ['auth']);
$router->get('/api/routes/{id}',       [\App\Controllers\RouteController::class, 'show'],    ['auth']);
$router->put('/api/routes/{id}',       [\App\Controllers\RouteController::class, 'update'],  ['auth']);
$router->patch('/api/routes/{id}',     [\App\Controllers\RouteController::class, 'update'],  ['auth']);
$router->delete('/api/routes/{id}',    [\App\Controllers\RouteController::class, 'destroy'], ['auth']);

$router->get('/api/long-routes',         [\App\Controllers\LongRouteController::class, 'index'],   ['auth']);
$router->post('/api/long-routes',        [\App\Controllers\LongRouteController::class, 'store'],   ['auth']);
$router->get('/api/long-routes/{id}',    [\App\Controllers\LongRouteController::class, 'show'],    ['auth']);
$router->put('/api/long-routes/{id}',    [\App\Controllers\LongRouteController::class, 'update'],  ['auth']);
$router->patch('/api/long-routes/{id}',  [\App\Controllers\LongRouteController::class, 'update'],  ['auth']);
$router->delete('/api/long-routes/{id}', [\App\Controllers\LongRouteController::class, 'destroy'], ['auth']);

$router->get('/api/regimes',           [\App\Controllers\RegimeController::class, 'index'],   ['auth']);
$router->post('/api/regimes',          [\App\Controllers\RegimeController::class, 'store'],   ['auth']);
$router->get('/api/regimes/{id}',      [\App\Controllers\RegimeController::class, 'show'],    ['auth']);
$router->put('/api/regimes/{id}',      [\App\Controllers\RegimeController::class, 'update'],  ['auth']);
$router->patch('/api/regimes/{id}',    [\App\Controllers\RegimeController::class, 'update'],  ['auth']);
$router->delete('/api/regimes/{id}',   [\App\Controllers\RegimeController::class, 'destroy'], ['auth']);

$router->get('/api/destinations',          [\App\Controllers\DestinationController::class, 'index'],   ['auth']);
$router->post('/api/destinations',         [\App\Controllers\DestinationController::class, 'store'],   ['auth']);
$router->get('/api/destinations/{id}',     [\App\Controllers\DestinationController::class, 'show'],    ['auth']);
$router->put('/api/destinations/{id}',     [\App\Controllers\DestinationController::class, 'update'],  ['auth']);
$router->patch('/api/destinations/{id}',   [\App\Controllers\DestinationController::class, 'update'],  ['auth']);
$router->delete('/api/destinations/{id}',  [\App\Controllers\DestinationController::class, 'destroy'], ['auth']);

// ── System Settings ───────────────────────────────────────────────────────────
$router->get('/api/settings/exchange-rate',   [\App\Controllers\SystemSettingController::class, 'exchangeRate'], ['auth']);
$router->get('/api/system-settings',         [\App\Controllers\SystemSettingController::class, 'index'],  ['auth']);
$router->put('/api/system-settings/{id}',    [\App\Controllers\SystemSettingController::class, 'update'], ['auth']);
$router->patch('/api/system-settings/{id}',  [\App\Controllers\SystemSettingController::class, 'update'], ['auth']);

// ── Users ─────────────────────────────────────────────────────────────────────
$router->get('/api/users',          [\App\Controllers\UserController::class, 'index'],   ['auth']);
$router->post('/api/users',         [\App\Controllers\UserController::class, 'store'],   ['auth']);
$router->get('/api/users/{id}',     [\App\Controllers\UserController::class, 'show'],    ['auth']);
$router->put('/api/users/{id}',     [\App\Controllers\UserController::class, 'update'],  ['auth']);
$router->patch('/api/users/{id}',   [\App\Controllers\UserController::class, 'update'],  ['auth']);
$router->delete('/api/users/{id}',  [\App\Controllers\UserController::class, 'destroy'], ['auth']);

// ── Roles & Permissions ───────────────────────────────────────────────────────
$router->get('/api/roles',          [\App\Controllers\RoleController::class, 'index'],   ['auth']);
$router->post('/api/roles',         [\App\Controllers\RoleController::class, 'store'],   ['auth']);
$router->get('/api/roles/{id}',     [\App\Controllers\RoleController::class, 'show'],    ['auth']);
$router->put('/api/roles/{id}',     [\App\Controllers\RoleController::class, 'update'],  ['auth']);
$router->patch('/api/roles/{id}',   [\App\Controllers\RoleController::class, 'update'],  ['auth']);
$router->delete('/api/roles/{id}',  [\App\Controllers\RoleController::class, 'destroy'], ['auth']);
$router->get('/api/permissions',    [\App\Controllers\PermissionController::class, 'index'], ['auth']);

// ── Reports ───────────────────────────────────────────────────────────────────
$router->get('/api/reports/dispatch/{id}',             [\App\Controllers\ReportController::class, 'dispatchReport'],         ['auth']);
$router->get('/api/reports/confirmed-affix',           [\App\Controllers\ReportController::class, 'confirmedAffixReport'],   ['auth']);
$router->get('/api/reports/device-retrieval',          [\App\Controllers\ReportController::class, 'deviceRetrievalReport'],  ['auth']);
$router->get('/api/reports/device-retrieval-2',        [\App\Controllers\ReportController::class, 'deviceRetrievalReport2'], ['auth']);
$router->get('/api/reports/receipts',                  [\App\Controllers\ReportController::class, 'receipts'],               ['auth']);
$router->get('/api/reports/generated-receipts',        [\App\Controllers\ReportController::class, 'generatedReceipts'],      ['auth']);
$router->get('/api/reports/dispatch-finance-records',  [\App\Controllers\ReportController::class, 'dispatchFinanceRecords'], ['auth']);
$router->get('/api/reports/overstay-receipts',         [\App\Controllers\ReportController::class, 'overstayReceipts'],       ['auth']);
$router->get('/api/reports/overstay-invoices',         [\App\Controllers\ReportController::class, 'overstayInvoices'],       ['auth']);
$router->get('/api/reports/overstay-devices',          [\App\Controllers\ReportController::class, 'overstayDevices'],        ['auth']);
$router->get('/api/reports/overstay-devices-pdf',      [\App\Controllers\ReportController::class, 'overstayDevicesPdf'],     ['auth']);
