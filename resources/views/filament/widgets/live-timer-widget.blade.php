<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Current Server Time ({{ $this->getServerTime()['timezone'] }}):
                </span>
            </div>
            
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400 font-mono" id="live-timer">
                {{ $this->getServerTime()['formatted'] }}
            </div>
        </div>
    </x-filament::card>

    <script>
        (function() {
            // Get initial server timestamp from PHP (in milliseconds)
            const initialServerTimestamp = {{ $this->getServerTime()['timestamp'] }};
            const timezone = "{{ $this->getServerTime()['timezone'] }}";
            
            // Calculate offset between server time and client time
            const clientTimestamp = Date.now();
            const offset = initialServerTimestamp - clientTimestamp;
            
            // Debug logging (optional)
            console.log('Live Timer Widget initialized', {
                serverTimestamp: initialServerTimestamp,
                clientTimestamp: clientTimestamp,
                offset: offset,
                timezone: timezone
            });
            
            // Function to format date as YYYY-MM-DD HH:mm:ss
            function formatDateTime(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }
            
            // Function to update timer display
            function updateTimer() {
                // Calculate current server time using offset
                const serverTime = new Date(Date.now() + offset);
                const timerElement = document.getElementById('live-timer');
                
                if (timerElement) {
                    timerElement.textContent = formatDateTime(serverTime);
                }
            }
            
            // Update immediately on page load
            updateTimer();
            
            // Update every 1 second
            const updateInterval = setInterval(updateTimer, 1000);
            
            // Optional: Recalibrate every 5 minutes to prevent drift
            const recalibrateInterval = setInterval(function() {
                console.log('Timer recalibration checkpoint (offset currently: ' + offset + 'ms)');
                // In production, could make AJAX call here to get fresh server time
                // and recalculate offset
            }, 300000); // 5 minutes
            
            // Cleanup on page navigation (if using Livewire)
            document.addEventListener('livewire:navigated', function() {
                clearInterval(updateInterval);
                clearInterval(recalibrateInterval);
                console.log('Timer cleaned up on page navigation');
            });
        })();
    </script>
</x-filament::widget>
