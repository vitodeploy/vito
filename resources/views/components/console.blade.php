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
        completionSuggestions: [],
        directorySuggestions: [],
        showSuggestions: false,
        selectedSuggestionIndex: -1,
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

            this.fetchCompletionSuggestions()

            $watch('command', (value) => {
                if (! value.trim()) {
                    this.fetchCompletionSuggestions()
                    this.showSuggestions = false
                    this.selectedSuggestionIndex = 0
                }
            })

            document
                .getElementById('command')
                .addEventListener('keydown', (event) => {
                    // Ctrl+U: Clear line before cursor
                    if (event.ctrlKey && event.key === 'u') {
                        event.preventDefault()
                        const cursorPos = event.target.selectionStart
                        this.command = this.command.substring(cursorPos)
                    }

                    // Ctrl+K: Clear line after cursor
                    if (event.ctrlKey && event.key === 'k') {
                        event.preventDefault()
                        const cursorPos = event.target.selectionStart
                        this.command = this.command.substring(0, cursorPos)
                    }

                    // Ctrl+W: Delete word before cursor
                    if (event.ctrlKey && event.key === 'w') {
                        event.preventDefault()
                        const cursorPos = event.target.selectionStart
                        const beforeCursor = this.command.substring(0, cursorPos)
                        const afterCursor = this.command.substring(cursorPos)
                        const lastWord = beforeCursor.replace(/\s*\S*$/, '')
                        this.command = lastWord + afterCursor
                        event.target.selectionStart = lastWord.length
                        event.target.selectionEnd = lastWord.length
                    }

                    // Ctrl+A: Move cursor to start of line
                    if (event.ctrlKey && event.key === 'a') {
                        event.preventDefault()
                        event.target.selectionStart = 0
                        event.target.selectionEnd = 0
                    }

                    // Ctrl+E: Move cursor to end of line
                    if (event.ctrlKey && event.key === 'e') {
                        event.preventDefault()
                        event.target.selectionStart = this.command.length
                        event.target.selectionEnd = this.command.length
                    }

                    // Alt+B: Move backward one word
                    if (event.altKey && event.key === 'b') {
                        event.preventDefault()
                        const cursorPos = event.target.selectionStart
                        const beforeCursor = this.command.substring(0, cursorPos)
                        const newPos = beforeCursor.replace(/\S+\s*$/, '').length
                        event.target.selectionStart = newPos
                        event.target.selectionEnd = newPos
                    }

                    // Alt+F: Move forward one word
                    if (event.altKey && event.key === 'f') {
                        event.preventDefault()
                        const cursorPos = event.target.selectionStart
                        const afterCursor = this.command.substring(cursorPos)
                        const match = afterCursor.match(/^\s*\S+/)
                        if (match) {
                            const newPos = cursorPos + match[0].length
                            event.target.selectionStart = newPos
                            event.target.selectionEnd = newPos
                        }
                    }

                    // Ctrl+C: Cancel current command
                    if (event.ctrlKey && event.key === 'c') {
                        event.preventDefault()
                        this.command = ''
                        this.output += this.shellPrefix + '^C\n'
                    }

                    // Ctrl+L: Clear screen (already implemented, but moved here)
                    if (event.ctrlKey && event.key === 'l') {
                        event.preventDefault()
                        if (this.running) return
                        this.output = ''
                    }
                })
        },
        async run() {
            if (! this.command) return

            if (this.command.trim() === 'clear') {
                this.output = ''
                this.command = ''
                return
            }

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
            await this.fetchCompletionSuggestions()
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
        handleTabCompletion(event) {
            event.preventDefault()

            if (this.showSuggestions) {
                if (event.shiftKey) {
                    this.selectedSuggestionIndex =
                        this.selectedSuggestionIndex <= 0
                            ? this.getCurrentSuggestions().length - 1
                            : this.selectedSuggestionIndex - 1
                } else {
                    this.selectedSuggestionIndex =
                        this.selectedSuggestionIndex >=
                        this.getCurrentSuggestions().length - 1
                            ? 0
                            : this.selectedSuggestionIndex + 1
                }
                this.scrollSelectedIntoView()
                return
            }

            const commandParts = this.command.trim().split(' ')
            const isCD = commandParts[0] === 'cd'
            const currentPath = commandParts.length > 1 ? commandParts[1] : ''

            const pathParts = currentPath.split('/')
            const partialName = pathParts.pop()
            const basePath = pathParts.join('/')

            const suggestions = this.getCurrentSuggestions()

            if (! currentPath.trim()) {
                this.showSuggestions = true
                this.selectedSuggestionIndex = 0
                return
            }

            const matches = suggestions.filter((s) =>
                typeof s === 'string'
                    ? s.toLowerCase().startsWith(partialName.toLowerCase())
                    : s.name.toLowerCase().startsWith(partialName.toLowerCase()),
            )

            if (matches.length === 1) {
                const matchValue = matches[0].name || matches[0]
                const fullPath = basePath ? `${basePath}/${matchValue}` : matchValue

                if (isCD) {
                    this.command = `cd ${fullPath}`
                } else {
                    const parts = this.command.split(' ')
                    parts[parts.length - 1] = fullPath
                    this.command = parts.join(' ')
                }

                if (matches[0].isDirectory) {
                    this.fetchCompletionSuggestions(fullPath)
                }

                this.showSuggestions = false
            } else if (matches.length > 1) {
                this.showSuggestions = true
                this.selectedSuggestionIndex = 0
            }

            if (basePath) {
                this.fetchCompletionSuggestions(basePath)
            }
        },

        async fetchCompletionSuggestions(path = '') {
            this.completionSuggestions = []
            this.directorySuggestions = []
            this.selectedSuggestionIndex = -1

            try {
                const fetchOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        user: this.user,
                        command: 'ls -la1 ' + (path ? path : '.'),
                    }),
                }

                const response = await fetch(this.runUrl, fetchOptions)
                const responseClone = response.clone()
                const reader = responseClone.body.getReader()
                const decoder = new TextDecoder('utf-8')
                let result = ''

                while (true) {
                    const { value, done } = await reader.read()
                    if (done) break
                    result += decoder.decode(value, { stream: true })
                }

                const lines = result
                    .split('\n')
                    .filter((line) => line && ! line.startsWith('total'))

                this.completionSuggestions = lines
                    .map((line) => {
                        const parts = line.split(/\s+/)
                        const name = parts.slice(8).join(' ')
                        const isDirectory = line.startsWith('d')
                        return { name, isDirectory }
                    })
                    .filter((item) => item.name !== '.' && item.name !== '..')
                    .map((item) => ({
                        name: item.name,
                        isDirectory: item.isDirectory,
                    }))

                this.directorySuggestions = this.completionSuggestions
                    .filter((item) => item.isDirectory)
                    .map((item) => item.name)
            } catch (error) {
                console.error('Error fetching completions:', error)
                this.completionSuggestions = []
                this.directorySuggestions = []
            }
        },
        processSuggestions(isCD, currentPath, basePath, partialName) {
            const suggestions = this.getCurrentSuggestions()

            if (! currentPath.trim()) {
                this.showSuggestions = true
                this.selectedSuggestionIndex = 0
                return
            }

            const matches = suggestions.filter((s) =>
                typeof s === 'string'
                    ? s.toLowerCase().startsWith(partialName.toLowerCase())
                    : s.name.toLowerCase().startsWith(partialName.toLowerCase()),
            )

            if (matches.length === 1) {
                const matchValue = matches[0].name || matches[0]
                const fullPath = basePath ? `${basePath}/${matchValue}` : matchValue

                if (isCD) {
                    this.command = `cd ${fullPath}`
                } else {
                    const parts = this.command.split(' ')
                    parts[parts.length - 1] = fullPath
                    this.command = parts.join(' ')
                }

                if (matches[0].isDirectory) {
                    this.fetchCompletionSuggestions(fullPath)
                }

                this.showSuggestions = false
            } else if (matches.length > 1) {
                this.showSuggestions = true
                this.selectedSuggestionIndex = 0
            }
        },

        getCurrentSuggestions() {
            if (this.command.trim().startsWith('cd')) {
                return this.directorySuggestions
            }
            return this.completionSuggestions.map((s) => s.name)
        },
        scrollSelectedIntoView() {
            setTimeout(() => {
                const container = document.querySelector(
                    '[x-ref=suggestionsContainer]',
                )
                const selectedElement =
                    container.children[this.selectedSuggestionIndex]
                if (selectedElement) {
                    selectedElement.scrollIntoView({ block: 'nearest' })
                }
            }, 0)
        },
        handleKeyNavigation(event) {
            if (! this.showSuggestions) return

            if (
                event.key === 'ArrowDown' ||
                (event.key === 'Tab' && ! event.shiftKey)
            ) {
                event.preventDefault()
                this.selectedSuggestionIndex = Math.min(
                    this.selectedSuggestionIndex + 1,
                    this.getCurrentSuggestions().length - 1,
                )
                this.scrollSelectedIntoView()
            } else if (
                event.key === 'ArrowUp' ||
                (event.key === 'Tab' && event.shiftKey)
            ) {
                event.preventDefault()
                this.selectedSuggestionIndex = Math.max(
                    this.selectedSuggestionIndex - 1,
                    0,
                )
                this.scrollSelectedIntoView()
            } else if (event.key === 'Enter' && this.selectedSuggestionIndex >= 0) {
                event.preventDefault()
                this.selectSuggestion(
                    this.getCurrentSuggestions()[this.selectedSuggestionIndex],
                )
            } else if (event.key === 'Escape') {
                this.showSuggestions = false
                this.selectedSuggestionIndex = -1
            }
        },

        selectSuggestion(suggestion) {
            const suggestionValue = suggestion.name || suggestion
            const isCD = this.command.trim().startsWith('cd')

            const commandParts = this.command.trim().split(' ')
            const currentPath = commandParts.length > 1 ? commandParts[1] : ''
            const pathParts = currentPath.split('/')
            pathParts.pop()
            const basePath = pathParts.join('/')

            const fullPath = basePath
                ? `${basePath}/${suggestionValue}`
                : suggestionValue

            if (isCD) {
                this.command = `cd ${fullPath}`
            } else {
                const parts = this.command.split(' ')
                parts[parts.length - 1] = fullPath
                this.command = parts.join(' ')
            }

            this.showSuggestions = false
            this.selectedSuggestionIndex = -1
        },
    }"
>
    <div class="relative">
        <form class="flex items-center justify-between">
            <x-filament::input.wrapper>
                <x-filament::input.select id="user" name="user" x-model="user" class="w-full" x-bind:disabled="running">
                    @foreach ($server->getSshUsers() as $user)
                        <option value="{{ $user }}">{{ $user }}</option>
                    @endforeach
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
                <div class="relative flex-grow">
                    <input
                        type="text"
                        class="h-5 w-full border-0 bg-transparent p-0 px-1 outline-none ring-0 focus:outline-none focus:ring-0"
                        autofocus
                        id="command"
                        name="command"
                        x-model="command"
                        autocomplete="off"
                        @keydown.tab.prevent="handleTabCompletion($event)"
                        @keydown="handleKeyNavigation($event)"
                        @click.away="showSuggestions = false"
                    />

                    <div
                        x-show="showSuggestions"
                        x-ref="suggestionsContainer"
                        class="absolute bottom-full left-0 mb-2 max-h-[200px] w-full overflow-y-auto rounded-lg border border-gray-700 bg-gray-900 shadow-lg"
                        style="display: none"
                    >
                        <template x-for="(suggestion, index) in getCurrentSuggestions()" :key="index">
                            <div
                                @click="selectSuggestion(suggestion)"
                                class="flex cursor-pointer items-center px-3 py-1 transition-colors duration-150 hover:bg-gray-700"
                                :class="{'bg-gray-700 text-white': index === selectedSuggestionIndex}"
                            >
                                <span x-text="suggestion.name || suggestion"></span>
                                <span x-show="suggestion.isDirectory" class="ml-2 text-gray-500">/</span>
                            </div>
                        </template>
                    </div>
                </div>
                <button type="submit" id="btn-run" x-on:click="run" class="hidden"></button>
            </form>
        </div>
    </div>
</div>
