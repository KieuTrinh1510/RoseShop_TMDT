@if ($paginator->hasPages())
    <nav class="d-inline-block" aria-label="Pagination">
        <ul class="pagination rs-pagination mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Logic rút gọn trang với dấu ... --}}
            @php
                $currentPage = $paginator->currentPage();
                $lastPage    = $paginator->lastPage();
                $pages = [];

                if ($lastPage <= 3) {
                    for ($i = 1; $i <= $lastPage; $i++) {
                        $pages[] = $i;
                    }
                } else {
                    if ($currentPage == 1) {
                        $pages = [1, '...', $lastPage];
                    } elseif ($currentPage == $lastPage) {
                        $pages = [1, '...', $lastPage];
                    } else {
                        $pages[] = 1;
                        if ($currentPage > 2) {
                            $pages[] = '...';
                        }
                        $pages[] = $currentPage;
                        if ($currentPage < $lastPage - 1) {
                            $pages[] = '...';
                        }
                        $pages[] = $lastPage;
                    }
                }
            @endphp

            @foreach ($pages as $p)
                @if ($p === '...')
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link ellipsis">&hellip;</span></li>
                @elseif ($p == $currentPage)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $p }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($p) }}">{{ $p }}</a></li>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
