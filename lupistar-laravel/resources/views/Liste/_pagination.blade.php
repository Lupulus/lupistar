@if ($paginator->lastPage() > 1)
    @php
        $current = (int) $paginator->currentPage();
        $last = (int) $paginator->lastPage();
        $maxVisible = 5;
        $showCompact = $last > $maxVisible;

        $start = 1;
        $end = $last;
        if ($showCompact) {
            $start = max(1, min($current - 2, $last - ($maxVisible - 1)));
            $end = min($last, $start + ($maxVisible - 1));
        }
    @endphp

    <div class="pagination" data-current-page="{{ $current }}" data-last-page="{{ $last }}">
        @if($showCompact)
            @if($current > 1)
                <a href="#" data-page="1" class="pagination-control">&lt;&lt;</a>
                <a href="#" data-page="{{ $current - 1 }}" class="pagination-control">&lt;</a>
            @else
                <span class="pagination-control disabled">&lt;&lt;</span>
                <span class="pagination-control disabled">&lt;</span>
            @endif
        @endif

        @for ($p = $start; $p <= $end; $p++)
            <a href="#" data-page="{{ $p }}" class="@if($p === $current) active @endif">{{ $p }}</a>
        @endfor

        @if($showCompact)
            @if($current < $last)
                <a href="#" data-page="{{ $current + 1 }}" class="pagination-control">&gt;</a>
                <a href="#" data-page="{{ $last }}" class="pagination-control">&gt;&gt;</a>
            @else
                <span class="pagination-control disabled">&gt;</span>
                <span class="pagination-control disabled">&gt;&gt;</span>
            @endif

            <span class="pagination-go">
                <input type="text" class="pagination-go-input" inputmode="numeric" pattern="[0-9]*" placeholder="Page">
                <button type="button" class="pagination-go-btn">Go</button>
            </span>
        @endif
    </div>
@endif
