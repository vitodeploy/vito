import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.directive('clipboard', (el) => {
    let text = el.textContent

    el.addEventListener('click', () => {
        navigator.clipboard.writeText(text)
    })
})

Livewire.start()

import htmx from "htmx.org";
window.htmx = htmx;
window.htmx.defineExtension('disable-element', {
    onEvent: function (name, evt) {
        let elt = evt.detail.elt;
        let target = elt.getAttribute("hx-disable-element");
        let targetElements = (target === "self") ? [ elt ] : document.querySelectorAll(target);

        for (let i = 0; i < targetElements.length; i++) {
            if (name === "htmx:beforeRequest" && targetElements[i]) {
                targetElements[i].disabled = true;
            } else if (name === "htmx:afterRequest" && targetElements[i]) {
                targetElements[i].disabled = false;
            }
        }
    }
});
document.body.addEventListener('htmx:configRequest', (event) => {
    event.detail.headers['X-CSRF-TOKEN'] = document.head.querySelector('meta[name="csrf-token"]').content;
});

import toastr from 'toastr';
window.toastr = toastr;
window.toastr.options = {
    "debug": false,
    "positionClass": "toast-bottom-right",
    "preventDuplicates": true,
}
