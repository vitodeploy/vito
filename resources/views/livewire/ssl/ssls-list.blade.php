<div>
    <x-card-header>
        <x-slot name="title">{{ __("SSLs") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can manage your site's SSL certificates") }}
        </x-slot>
        <x-slot name="aside">
            <livewire:ssl.create-ssl :site="$site" />
        </x-slot>
    </x-card-header>
    <div x-data="" class="space-y-3">
        @if (count($ssls) > 0)
            <x-table>
                <tr>
                    <x-th>{{ __("Type") }}</x-th>
                    <x-th>{{ __("Created") }}</x-th>
                    <x-th>{{ __("Expires at") }}</x-th>
                    <x-th></x-th>
                </tr>
                @foreach ($ssls as $ssl)
                    <tr>
                        <x-td>{{ $ssl->type }}</x-td>
                        <x-td>
                            <x-datetime :value="$ssl->created_at" />
                        </x-td>
                        <x-td>
                            <x-datetime :value="$ssl->expires_at" />
                        </x-td>
                        <x-td>
                            <div class="flex items-center">
                                @include("livewire.ssl.partials.status", ["status" => $ssl->status])
                                <div class="inline">
                                    <x-icon-button
                                        x-on:click="$wire.deleteId = '{{ $ssl->id }}'; $dispatch('open-modal', 'delete-ssl')"
                                    >
                                        Delete
                                    </x-icon-button>
                                </div>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>
            <x-confirm-modal
                name="delete-ssl"
                :title="__('Confirm')"
                :description="__('Are you sure that you want to delete this SSL certificate?')"
                method="delete"
            />
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You don't have any SSL certificates yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
