@foreach($topBlocks as $block)
    {{ $block }}
@endforeach

server {
    @foreach($blocks as $block)
        {{ $block }}
    @endforeach
}
