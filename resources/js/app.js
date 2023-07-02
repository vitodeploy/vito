import './bootstrap';
import Echo from "laravel-echo"
import Pusher from "pusher-js"
import Alpine from 'alpinejs';
import Clipboard from "@ryangjchandler/alpine-clipboard";

Alpine.plugin(Clipboard)

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'app-key',
    wsHost: 'localhost',
    wsPort: 6001,
    cluster: '',
    forceTLS: false,
    disableStats: true,
});

window.Pusher = Pusher;

window.Alpine = Alpine;

Alpine.start();

