@if($paginator->hasPages())
    <div class="ma-pagination" style="display:flex;gap:10px;align-items:center;justify-content:flex-end">
        @if($paginator->onFirstPage())
            <span class="ma-btn-secondary" style="opacity:.5">Previous</span>
        @else
            <a class="ma-btn-secondary" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a class="ma-btn-secondary" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span class="ma-btn-secondary" style="opacity:.5">Next</span>
        @endif
    </div>
@endif
