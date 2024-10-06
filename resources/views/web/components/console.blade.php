<div
    x-data="{
        user: '{{ $server->ssh_user }}',
        running: false,
        command: '',
        output: '',
        runUrl: '{{ route("servers.console.run", ["server" => $server]) }}',
        async run() {
            if (! this.command) return
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
    <div>
        <x-console-view id="console-output">
            <div class="w-full" x-text="output"></div>
        </x-console-view>
        <form onsubmit="return false" id="console-form" class="mt-5 flex items-center justify-between">
            <div class="grid w-full grid-cols-8 gap-2">
                <x-filament::input.wrapper class="col-span-1 w-full">
                    <x-filament::input.select
                        id="user"
                        name="user"
                        x-model="user"
                        class="w-full"
                        x-bind:disabled="running"
                    >
                        <option value="root">root</option>
                        <option value="{{ $server->ssh_user }}">{{ $server->ssh_user }}</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper class="col-span-6 w-full">
                    <x-filament::input
                        id="command"
                        name="command"
                        x-model="command"
                        type="text"
                        placeholder="Type your command here..."
                        class="mx-1 flex-grow"
                        autocomplete="off"
                    />
                </x-filament::input.wrapper>
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-play"
                    type="submit"
                    id="btn-run"
                    x-on:click="run"
                    class="col-span-1"
                    x-show="!running"
                />
                <x-filament::button
                    color="gray"
                    type="button"
                    id="btn-stop"
                    x-on:click="stop"
                    class="col-span-1"
                    x-show="running"
                    icon="heroicon-o-stop"
                />
            </div>
        </form>
    </div>
</div>
