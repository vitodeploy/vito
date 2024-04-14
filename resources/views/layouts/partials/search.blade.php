<div
    x-data="{
        open: false,
        isTyping: false,
        focused: false,
        searchQuery: '',
        isLoading: false,
        searchResult: [],
        noResults: false,
        showResults: false,
        keyUp: false,
        focusedItem: null,
        init() {
            document.onkeydown = (e) => {
                if (e.key === '/') {
                    if (
                        ! document.activeElement.type ||
                        document.activeElement.type === 'undefined'
                    ) {
                        this.openSearch()
                    }
                }
                if (e.key === 'Escape') {
                    this.close()
                } else {
                    this.changeFocus(e.code)
                }
            }
            $watch(
                'searchQuery',
                this.debounce(() => {
                    this.isTyping = false
                }, 700),
            )
            $watch('isTyping', (value) => {
                if (! value && this.keyUp && this.searchQuery.length > 0) {
                    this.search(this.searchQuery)
                }
                if (! value && this.searchQuery.length === 0) {
                    this.showResults = false
                    this.searchResult = []
                }
            })
        },
        close() {
            this.open = false
            this.isLoading = false
            this.focused = false
            this.focusedItem = null
            document.body.classList.remove('overflow-y-hidden')
        },
        openSearch() {
            this.open = true
            document.body.classList.add('overflow-y-hidden')
            setTimeout(() => {
                this.$refs.input.select()
            }, 50)
        },
        search(searchQuery) {
            this.showResults = true
            this.noResults = false
            this.isLoading = true
            fetch('/search?q=' + searchQuery)
                .then((response) => response.json())
                .then((data) => {
                    this.searchResult = data.results
                    if (this.searchResult.length === 0) {
                        this.noResults = true
                    }
                    this.keyUp = false
                })
                .catch((error) => {
                    console.error('Error:', error)
                })
                .then(() => {
                    this.isLoading = false
                })
        },
        keyEntered(e) {
            this.keyUp = true
            if (e.code === 'Escape') {
                this.close()
            }
            this.changeFocus(e.code)
        },
        changeFocus(type) {
            if (type === 'Backspace' && this.open && ! this.focused) {
                setTimeout(() => {
                    this.$refs.input.select()
                }, 100)
            }
            if (type === 'ArrowDown') {
                if (this.searchResult.length > 0) {
                    if (this.focusedItem !== null) {
                        if (this.focusedItem < this.searchResult.length - 1) {
                            this.focusedItem++
                        }
                    } else {
                        this.focusedItem = 0
                    }
                    document.getElementById(`result-${this.focusedItem}`).focus()
                }
            }
            if (type === 'ArrowUp') {
                if (this.searchResult.length > 0) {
                    if (this.focusedItem !== null) {
                        if (this.focusedItem > 0) {
                            this.focusedItem--
                        }
                        document
                            .getElementById(`result-${this.focusedItem}`)
                            .focus()
                    }
                }
            }
        },
        debounce(func, wait, immediate) {
            let timeout
            return function () {
                const context = this,
                    args = arguments
                const later = function () {
                    timeout = null
                    if (! immediate) func.apply(context, args)
                }
                const callNow = immediate && ! timeout
                clearTimeout(timeout)
                timeout = setTimeout(later, wait)
                if (callNow) func.apply(context, args)
            }
        },
    }"
    @open-search.window="openSearch"
>
    <div
        x-show="open"
        class="fixed bottom-0 left-0 right-0 top-0 z-[2000] flex max-w-full items-start justify-center"
    >
        <div
            x-on:click="close"
            class="fixed inset-0 bottom-0 left-0 right-0 top-0 z-[1000] items-center bg-gray-500 opacity-75 dark:bg-gray-900"
        ></div>
        <div class="absolute left-1 right-1 z-[1000] mt-20 md:left-auto md:right-auto lg:scale-110">
            <div class="w-full px-10 md:w-[500px]">
                <x-text-input
                    id="search-input"
                    x-ref="input"
                    @focus="focused = true"
                    @blur="focused = false"
                    type="text"
                    class="w-full"
                    @input="isTyping = true"
                    x-model="searchQuery"
                    placeholder="Search something..."
                    @keyup="keyEntered"
                    autocomplete="off"
                ></x-text-input>
                <div
                    x-show="showResults"
                    class="list-none divide-y divide-gray-100 rounded-md bg-white py-1 text-base shadow ring-1 ring-black ring-opacity-5 dark:divide-gray-700/50 dark:bg-gray-800"
                >
                    <div x-show="isLoading" class="w-full px-3 py-2 text-sm text-gray-800 dark:text-gray-300">
                        Please wait
                    </div>
                    <div
                        x-show="!isLoading && noResults"
                        class="w-full px-3 py-2 text-sm text-gray-800 dark:text-gray-300"
                    >
                        No results
                    </div>
                    <template x-for="(item, index) in searchResult">
                        <button
                            x-bind:id="`result-${index}`"
                            class="flex w-full items-center justify-between p-3 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-700/50 dark:focus:bg-gray-700"
                            x-on:click="window.location.href = item.url"
                        >
                            <div class="font-semibold text-primary-500" x-text="item.text"></div>
                            <div class="flex items-center">
                                <span
                                    class="mr-1 rounded-xl bg-gray-100 px-2 text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                                    x-text="item.project"
                                    data-tooltip="Project"
                                ></span>
                                <span
                                    class="rounded-xl bg-primary-100 px-2 text-primary-500 dark:bg-primary-500 dark:bg-opacity-10"
                                    x-text="item.type"
                                    data-tooltip="Type"
                                ></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
