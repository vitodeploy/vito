<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("SSLs") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can manage your site's SSL certificates") }}
        </x-slot>
        <x-slot name="aside">
            @include("ssls.partials.create-ssl")
        </x-slot>
    </x-card-header>
    <x-live id="live-ssls">
        <div x-data="" class="space-y-3">
            @if (count($ssls) > 0)
                <x-table>
                    <x-tr>
                        <x-th>{{ __("Type") }}</x-th>
                        <x-th>{{ __("Domains") }}</x-th>
                        <x-th>{{ __("Created") }}</x-th>
                        <x-th>{{ __("Expires at") }}</x-th>
                        <x-th></x-th>
                    </x-tr>
                    @foreach ($ssls as $ssl)
                        <x-tr>
                            <x-td>{{ $ssl->type }}</x-td>
                            <x-td>
                                <div class="flex-col space-y-1">
                                    @foreach ($ssl->getDomains() as $domain)
                                        <x-status status="disabled" class="lowercase">
                                            {{ $domain }}
                                        </x-status>
                                    @endforeach
                                </div>
                            </x-td>
                            <x-td>
                                <x-datetime :value="$ssl->created_at" />
                            </x-td>
                            <x-td>
                                <x-datetime :value="$ssl->expires_at" />
                            </x-td>
                            <x-td>
                                <div class="flex items-center">
                                    @include("ssls.partials.status", ["status" => $ssl->status])
                                    <div class="inline">
                                        <x-icon-button
                                            x-on:click="deleteAction = '{{ route('servers.sites.ssl.destroy', ['server' => $server, 'site' => $site, 'ssl' => $ssl]) }}'; $dispatch('open-modal', 'delete-ssl')"
                                        >
                                            <x-heroicon name="o-trash" class="h-5 w-5" />
                                        </x-icon-button>
                                    </div>
                                </div>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-table>
            @else
                <x-simple-card>
                    <div class="text-center">
                        {{ __("You don't have any SSL certificates yet!") }}
                    </div>
                </x-simple-card>
            @endif
        </div>
    </x-live>
    <x-confirmation-modal
        name="delete-ssl"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this SSL certificate?')"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
