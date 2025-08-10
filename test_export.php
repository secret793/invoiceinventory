<?php

// Quick test of the export functionality
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the export route exists
$router = app('router');
$routes = $router->getRoutes();

echo "Testing export route for confirmed-affix-report...\n";

foreach ($routes->getRoutes() as $route) {
    if (str_contains($route->getName() ?? '', 'export.confirmed-affix-report')) {
        echo "✓ Route found: " . $route->getName() . " - " . $route->uri() . "\n";
        echo "✓ Methods: " . implode(', ', $route->methods()) . "\n";
        echo "✓ Middleware: " . implode(', ', $route->middleware()) . "\n";
        break;
    }
}

// Test the export class exists and can be instantiated
try {
    $export = new \App\Exports\ConfirmedAffixReportExport();
    echo "✓ ConfirmedAffixReportExport class can be instantiated\n";
} catch (Exception $e) {
    echo "✗ Error instantiating ConfirmedAffixReportExport: " . $e->getMessage() . "\n";
}

// Test the controller exists
try {
    $controller = new \App\Http\Controllers\ConfirmedAffixReportController();
    echo "✓ ConfirmedAffixReportController class can be instantiated\n";
} catch (Exception $e) {
    echo "✗ Error instantiating ConfirmedAffixReportController: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
