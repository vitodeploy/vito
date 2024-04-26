<x-app-layout>
    <x-slot name="pageTitle">Admin - Users</x-slot>

    <x-container>
        <x-card-header>
            <x-slot name="title">Users</x-slot>
            <x-slot name="description">Here you can manage users</x-slot>
            <x-slot name="aside">
                @include("admin.users.partials.create-user")
            </x-slot>
        </x-card-header>
        <div class="space-y-3" x-data="{ deleteAction: '' }">
            <x-table>
                <x-thead>
                    <x-tr>
                        <x-th>ID</x-th>
                        <x-th>Name</x-th>
                        <x-th>Email</x-th>
                        <x-th>Role</x-th>
                        <x-th></x-th>
                    </x-tr>
                </x-thead>
                <x-tbody>
                    @foreach ($users as $user)
                        <x-tr>
                            <x-td>{{ $user->id }}</x-td>
                            <x-td>{{ $user->name }}</x-td>
                            <x-td>{{ $user->email }}</x-td>
                            <x-td>
                                <div class="inline-flex">
                                    @if ($user->role === \App\Enums\UserRole::ADMIN)
                                        <x-status status="success">ADMIN</x-status>
                                    @else
                                        <x-status status="info">USER</x-status>
                                    @endif
                                </div>
                            </x-td>
                            <x-td class="text-right">
                                <x-icon-button>
                                    <x-heroicon name="o-trash" class="h-5 w-5" />
                                </x-icon-button>
                                <x-icon-button :href="route('admin.users.show', ['user' => $user])">
                                    <x-heroicon name="o-cog-6-tooth" class="h-5 w-5" />
                                </x-icon-button>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-tbody>
            </x-table>
        </div>
    </x-container>
</x-app-layout>
