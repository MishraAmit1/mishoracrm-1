{{--
    App-wide pagination view. Same Bootstrap-5 markup/classes as Laravel's
    built-in view (styled in app.css under "Pagination"), but always windows
    the page numbers to the current page ± 2 with first/last jump links and
    an ellipsis — Laravel's own bootstrap-5 view instead renders every page
    number with no ellipsis whenever the paginator has fewer than
    ($onEachSide * 2) + 8 (14 by default) pages, which looks broken once a
    list has, say, 8-13 pages.
--}}
@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">@lang('pagination.previous')</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a></li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a></li>
                @else
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">@lang('pagination.next')</span></li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p class="small text-muted">
                    {!! __('Showing') !!}
                    <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                    {!! __('of') !!}
                    <span class="fw-semibold">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <ul class="pagination">
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

                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $windowStart = max(1, $current - 2);
                        $windowEnd = min($last, $current + 2);
                    @endphp

                    {{-- Jump to first page --}}
                    @if ($windowStart > 1)
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
                        @if ($windowStart > 2)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                        @endif
                    @endif

                    {{-- Windowed page numbers --}}
                    @foreach ($paginator->getUrlRange($windowStart, $windowEnd) as $page => $url)
                        @if ($page == $current)
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    {{-- Jump to last page --}}
                    @if ($windowEnd < $last)
                        @if ($windowEnd < $last - 1)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                        @endif
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a></li>
                    @endif

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
            </div>
        </div>
    </nav>
@endif
