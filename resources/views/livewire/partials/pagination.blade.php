<div>
    @if ($paginator->hasPages())
        <nav
            role="navigation"
            aria-label="Pagination Navigation"
            class="flex justify-between"
        >
            <span>
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <x-secondary-button href="javascript:" disabled>
                        {!! __("pagination.previous") !!}
                    </x-secondary-button>
                @else
                    @if (method_exists($paginator, "getCursorName"))
                        <x-secondary-button
                            type="button"
                            dusk="previousPage"
                            wire:click="setPage('{{$paginator->previousCursor()->encode()}}','{{ $paginator->getCursorName() }}')"
                            wire:loading.attr="disabled"
                        >
                            {!! __("pagination.previous") !!}
                        </x-secondary-button>
                    @else
                        <x-secondary-button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                        >
                            {!! __("pagination.previous") !!}
                        </x-secondary-button>
                    @endif
                @endif
            </span>

            <span>
                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    @if (method_exists($paginator, "getCursorName"))
                        <x-secondary-button
                            type="button"
                            dusk="nextPage"
                            wire:click="setPage('{{$paginator->nextCursor()->encode()}}','{{ $paginator->getCursorName() }}')"
                            wire:loading.attr="disabled"
                        >
                            {!! __("pagination.next") !!}
                        </x-secondary-button>
                    @else
                        <x-secondary-button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                        >
                            {!! __("pagination.next") !!}
                        </x-secondary-button>
                    @endif
                @else
                    <x-secondary-button href="javascript:" disabled>
                        {!! __("pagination.next") !!}
                    </x-secondary-button>
                @endif
            </span>
        </nav>
    @endif
</div>
