<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DistributionPointController;
use App\Http\Controllers\DataEntryController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\DispatchReportController;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Export routes
Route::get('/export/dispatch-report/{assignment}', [DispatchReportController::class, 'export'])
    ->name('export.dispatch-report')
    ->middleware(['auth']);

Route::get('/export/confirmed-affix-report', [\App\Http\Controllers\ConfirmedAffixReportController::class, 'export'])
    ->name('export.confirmed-affix-report')
    ->middleware(['auth']);

Route::get('/export/device-retrieval-report', [\App\Http\Controllers\DeviceRetrievalReportController::class, 'export'])
    ->name('export.device-retrieval-report')
    ->middleware(['auth']);

// Test route for Livewire component
Route::get('/test-confirmed-affix-report', function () {
    return view('test-confirmed-affix-report');
})->middleware('auth');

// Redirect root and any unmatched routes to admin panel
Route::get('/', RedirectController::class)->name('home');
Route::fallback(RedirectController::class);

// Protected routes that require authentication
Route::middleware(['auth'])->group(function () {
    Route::post('/devices/import', [DeviceController::class, 'import'])->name('devices.import');
    Route::get('/distribution-points/{id}', [DistributionPointController::class, 'show'])->name('distribution-points.show');
    Route::get('/distribution-points', [DistributionPointController::class, 'index'])->name('distribution.points.index');
    Route::get('/admin/data-entry/allocation-point/{id}', [DataEntryController::class, 'show'])->name('data-entry.allocation-point');

    // Dispatch routes
    Route::prefix('dispatch')->group(function () {
        Route::get('/devices', [DispatchController::class, 'create'])->name('dispatch.devices');
        Route::post('/store', [DispatchController::class, 'store'])->name('dispatch.store');
        Route::get('/create', [DispatchController::class, 'create'])->name('dispatch.create');
        Route::get('/devices/cancel/{assignment_id}', [DispatchController::class, 'cancel'])->name('dispatch.cancel');
        Route::get('/devices/{devices}/{assignment_id}', [DispatchController::class, 'create'])->name('dispatch.devices');
    });

    // API routes
    Route::prefix('api')->group(function () {
        Route::get('/regimes/{regime}/destinations', function (App\Models\Regime $regime) {
            return $regime->destinations()->where('status', 'active')->get();
        });
    });

    // Add this temporary debug route
    Route::get('/debug-permissions', function () {
        $user = auth()->user();
        $point = \App\Models\AllocationPoint::find(11); // The ID you're trying to access

        dd([
            'user_roles' => $user->roles->pluck('name'),
            'user_permissions' => $user->permissions->pluck('name'),
            'point_name' => $point->name,
            'required_permission' => 'view_allocationpoint_' . Str::slug($point->name),
            'has_permission' => $user->hasPermissionTo('view_allocationpoint_' . Str::slug($point->name)),
        ]);
    });

    // Add this route for downloading invoices from device retrievals
    Route::get('/invoices/download/retrieval/{deviceRetrieval}', function (\App\Models\DeviceRetrieval $deviceRetrieval) {
        // Find the invoice related to this device retrieval
        $invoice = \App\Models\Invoice::where('device_retrieval_id', $deviceRetrieval->id)
            ->where('status', 'approved')
            ->first();

        // If no invoice exists, try to generate one
        if (!$invoice && $deviceRetrieval->payment_status === 'PD') {
            // Create a basic invoice for the device retrieval
            $invoice = \App\Models\Invoice::create([
                'reference_number' => 'INV-' . $deviceRetrieval->id . '-' . date('YmdHis'),
                'reference_date' => now(),
                'sad_boe' => $deviceRetrieval->boe,
                'agent' => $deviceRetrieval->agent_contact,
                'overstay_days' => $deviceRetrieval->overstay_days,
                'total_amount' => $deviceRetrieval->overstay_amount,
                'description' => 'Overstay payment for device ' . $deviceRetrieval->device->device_id,
                'status' => 'approved',
                'device_retrieval_id' => $deviceRetrieval->id,
            ]);
        }

        if (!$invoice) {
            return back()->with('error', 'No approved invoice found for this device retrieval');
        }

        // Generate and return the PDF
        return \App\Services\InvoiceService::generatePdf($invoice);
    })->name('invoices.download.retrieval');
});

