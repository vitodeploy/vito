<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} - Console</x-slot>

    <div
        x-data="{
            user: '{{ $server->ssh_user }}',
            running: false,
            command: '',
            output: '',
            runUrl: '{{ route("servers.console.run", ["server" => $server]) }}',
            async run() {
                this.running = true
                this.output = this.command + '\n'
                const fetchOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        user: this.user,
                        command: this.command,
                    }),
                }

                this.command = ''
                const response = await fetch(this.runUrl, fetchOptions)
                const reader = response.body.getReader()
                const decoder = new TextDecoder('utf-8')

                while (true) {
                    if (! this.running) {
                        reader.cancel()
                        this.output += '\nStopped!'
                        break
                    }
                    const { value, done } = await reader.read()
                    if (done) break

                    const textChunk = decoder.decode(value, { stream: true })

                    this.output += textChunk

                    document.getElementById('console-output').scrollTop =
                        document.getElementById('console-output').scrollHeight
                }
                this.output += '\nDone!'
                this.running = false
            },
            stop() {
                this.running = false
            },
        }"
    >
        <x-card-header>
            <x-slot name="title">Headless Console</x-slot>
            <x-slot name="description">
                Here you can run ssh commands on your server and see the result right away.
                <br />
                <b>Note that</b>
                this is a headless console, it doesn't keep the current path. it will always run from the home path of
                the selected user.
            </x-slot>
        </x-card-header>

        <div class="space-y-3">
            <x-console-view id="console-output">
                <div class="w-full" x-text="output"></div>
            </x-console-view>
            <form onsubmit="return false" id="console-form" class="flex items-center justify-between">
                <x-select-input x-model="user" id="user" name="user" class="flex-none" data-tooltip="User">
                    <option value="{{ $server->ssh_user }}">{{ $server->ssh_user }}</option>
                    <option value="root">root</option>
                </x-select-input>
                <x-text-input
                    id="command"
                    name="command"
                    x-model="command"
                    type="text"
                    placeholder="Type your command here..."
                    class="mx-1 flex-grow"
                    autocomplete="off"
                />
                <x-secondary-button
                    type="button"
                    id="btn-stop"
                    x-on:click="stop"
                    class="mr-1 h-[40px]"
                    x-bind:disabled="!running"
                >
                    Stop
                </x-secondary-button>
                <x-primary-button
                    type="submit"
                    id="btn-run"
                    x-on:click="run"
                    class="h-[40px]"
                    x-bind:disabled="running || command === ''"
                >
                    Run
                </x-primary-button>
            </form>
        </div>
    </div>
</x-server-layout>
