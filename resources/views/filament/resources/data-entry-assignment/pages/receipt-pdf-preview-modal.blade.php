@php
    use App\Exports\ReceiptPDFExport;
@endphp

<div class="flex flex-col gap-4 w-full h-full">
    <!-- PDF Preview Container -->
    <div class="flex-1 bg-gray-100 rounded-lg overflow-hidden border border-gray-300">
        <iframe 
            id="pdf-preview"
            src="{{ route('receipts.pdf', $receipt) }}" 
            class="w-full h-full"
            style="border: none;">
        </iframe>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-3 justify-end">
        <a href="{{ route('receipts.pdf', $receipt) }}" 
           download="receipt_{{ $receipt->receipt_number }}.pdf"
           class="inline-flex items-center px-4 py-2 font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Download PDF
        </a>
        <button 
            type="button"
            onclick="document.getElementById('pdf-modal').closest('.fi-modal').style.display='none'"
            class="inline-flex items-center px-4 py-2 font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Exit
        </button>
    </div>
</div>

<style>
    iframe {
        display: block;
    }
</style>
