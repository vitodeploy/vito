network:
  version: 2
  ethernets:
@foreach ($interfaces as $interface => $addresses)
    {!! $interface !!}:
      addresses:
@foreach ($addresses as $address)
        - {!! $address !!}
@endforeach
@endforeach
