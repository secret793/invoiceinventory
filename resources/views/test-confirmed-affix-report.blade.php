<!DOCTYPE html>
<html>
<head>
    <title>Test Confirmed Affix Report</title>
    @livewireStyles
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Test Confirmed Affix Report</h1>
        
        <div class="bg-white rounded-lg shadow p-6">
            @livewire('confirmed-affix-report-modal')
        </div>
    </div>

    @livewireScripts
</body>
</html>
