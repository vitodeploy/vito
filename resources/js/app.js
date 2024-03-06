import Alpine from 'alpinejs';

Alpine.directive('clipboard', (el) => {
    let text = el.textContent

    el.addEventListener('click', () => {
        navigator.clipboard.writeText(text)
    })
})

window.Alpine = Alpine;

Alpine.start();

import htmx from "htmx.org";
window.htmx = htmx;
window.htmx.defineExtension('disable-element', {
    onEvent: function (name, evt) {
        let elt = evt.detail.elt;
        let target = elt.getAttribute("hx-disable-element");
        let targetElements = (target === "self") ? [elt] : document.querySelectorAll(target);

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
    if (window.getSelection) { window.getSelection().removeAllRanges(); }
    else if (document.selection) { document.selection.empty(); }
});
document.body.addEventListener('htmx:beforeRequest', (event) => {
    let targetElements = event.target.querySelectorAll('[hx-disable]');
    for (let i = 0; i < targetElements.length; i++) {
        targetElements[i].disabled = true;
    }
});
document.body.addEventListener('htmx:afterRequest', (event) => {
    let targetElements = event.target.querySelectorAll('[hx-disable]');
    for (let i = 0; i < targetElements.length; i++) {
        targetElements[i].disabled = false;
    }
});

import toastr from 'toastr';
window.toastr = toastr;
window.toastr.options = {
    "debug": false,
    "positionClass": "toast-bottom-right",
    "preventDuplicates": true,
}
