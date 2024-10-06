<div
    @if (isset($getExtraAttributeBag))
        {{ $getExtraAttributeBag() }}
    @endif
>
    {!! $content !!}
</div>
