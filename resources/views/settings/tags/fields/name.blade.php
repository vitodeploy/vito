@php
    $id = "name-" . uniqid();
    if (! isset($items)) {
        $items = [];
    }
@endphp

<x-input-label :for="$id" :value="__('Name')" />
<x-autocomplete-text id="tag-name" name="name" :items="$items" :value="$value" placeholder="" />
@error("name")
    <x-input-error class="mt-2" :messages="$message" />
@enderror
