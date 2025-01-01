<div
    x-data="{
        user: '{{ $server->ssh_user }}',
        running: false,
        dir: '{{ cache()->get("console.$server->id.dir", "~") }}',
        command: '',
        output: '',
        serverName: '{{ $server->name }}',
        shellPrefix: '',
        clearAfterCommand: false,
        runUrl: '{{ route("servers.console.run", ["server" => $server]) }}',
        init() {
            this.setShellPrefix()
            $watch('user', async (value) => {
                await this.getWorkingDir()
            })
            const consoleOutput = document.getElementById('console-output')
            consoleOutput.addEventListener('mouseup', (event) => {
                if (window.getSelection()?.toString()) {
                    return
                }
                this.focusCommand()
            })
            this.focusCommand()

            document.addEventListener('keydown', (event) => {
                if (event.ctrlKey && event.key === 'l') {
                    event.preventDefault()
                    if (this.running) return
                    this.output = ''
                }
            })
        },
        async run() {
            if (! this.command) return
            this.running = true
            let output = this.shellPrefix + ' ' + this.command + '\n'
            if (this.clearAfterCommand) {
                this.output = output
            } else {
                this.output += output
            }
            setTimeout(() => {
                document.getElementById('console-output').scrollTop =
                    document.getElementById('console-output').scrollHeight
            }, 100)
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
            this.setShellPrefix()

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

                setTimeout(() => {
                    document.getElementById('console-output').scrollTop =
                        document.getElementById('console-output').scrollHeight
                }, 100)
            }
            this.output += '\n'
            await this.getWorkingDir()
            this.running = false
            setTimeout(() => {
                document.getElementById('command').focus()
            }, 100)
        },
        stop() {
            this.running = false
        },
        setShellPrefix() {
            this.shellPrefix = `${this.user}@${this.serverName}:${this.dir}$`
        },
        focusCommand() {
            document.getElementById('command').focus()
        },
        async getWorkingDir() {
            const response = await fetch(
                '{{ route("servers.console.working-dir", ["server" => $server]) }}',
            )
            if (response.ok) {
                const data = await response.json()
                this.dir = data.dir
                this.setShellPrefix()
            }
        },
    }"
>
    <div class="relative">
        <form class="flex items-center justify-between">
            <x-filament::input.wrapper>
                <x-filament::input.select id="user" name="user" x-model="user" class="w-full" x-bind:disabled="running">
                    <option value="root">root</option>
                    <option value="{{ $server->ssh_user }}">{{ $server->ssh_user }}</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <div class="flex items-center">
                <x-filament::button
                    color="gray"
                    type="button"
                    x-on:click="output = ''"
                    icon="heroicon-o-trash"
                    tooltip="Clear"
                    x-show="!running"
                >
                    Clear
                </x-filament::button>
                <x-filament::button
                    color="gray"
                    type="button"
                    id="btn-stop"
                    x-on:click="stop"
                    x-show="running"
                    icon="heroicon-o-stop"
                    tooltip="Stop"
                    class="ml-2"
                >
                    Stop
                </x-filament::button>
            </div>
        </form>
        <x-console-view id="console-output" class="mt-5">
            <div class="w-full" x-text="output"></div>
        </x-console-view>
        <div
            class="relative -mt-5 flex h-[50px] w-full items-center rounded-b-xl border-b border-l border-r border-gray-200 bg-black px-5 font-mono text-gray-50 dark:border-gray-800"
        >
            <form class="flex w-full items-center" x-show="!running" onsubmit="return false" id="console-form">
                <div x-text="shellPrefix"></div>
                <input
                    type="text"
                    class="h-5 flex-grow border-0 bg-transparent p-0 px-1 outline-none ring-0 focus:outline-none focus:ring-0"
                    autofocus
                    id="command"
                    name="command"
                    x-model="command"
                    autocomplete="off"
                />
                <button type="submit" id="btn-run" x-on:click="run" class="hidden"></button>
            </form>
        </div>
    </div>
</div>
