@if ($paginator->lastPage() > 1)
    <div class="pagination">
        @for ($p = 1; $p <= $paginator->lastPage(); $p++)
            <a href="#" data-page="{{ $p }}" class="@if($p === $paginator->currentPage()) active @endif">{{ $p }}</a>
        @endfor
    </div>
@endif
