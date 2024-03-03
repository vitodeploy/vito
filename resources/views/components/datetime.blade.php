@props([
    "value",
])

{{ date_with_timezone($value, auth()->user()->timezone) }}
