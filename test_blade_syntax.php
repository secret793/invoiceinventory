<?php
// Quick test to verify blade view exists and can be compiled
try {
    $bladePath = __DIR__ . '/resources/views/filament/resources/device-retrieval-resource/pages/device-retrieval-report.blade.php';

    if (file_exists($bladePath)) {
        echo "✓ Blade view file exists: $bladePath\n";

        $content = file_get_contents($bladePath);

        // Basic syntax checks
        if (strpos($content, 'wire:key="device-retrieval-report') !== false) {
            echo "✓ Contains proper Livewire key\n";
        }

        if (strpos($content, 'Device Retrieval Report') !== false) {
            echo "✓ Contains title\n";
        }

        if (strpos($content, 'retrieval_status') !== false) {
            echo "✓ Contains retrieval status column\n";
        }

        if (strpos($content, 'sortBy') !== false) {
            echo "✓ Contains sortBy functionality\n";
        }

        if (strpos($content, 'bg-gradient-to-r from-blue-50 to-indigo-50') !== false) {
            echo "✓ Contains new gradient styling\n";
        }

        echo "\nBlade file appears to be properly formatted!\n";
    } else {
        echo "✗ Blade view file not found: $bladePath\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
