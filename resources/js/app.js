import 'flowbite';
import 'flowbite/dist/datepicker.js';
import './ace-editor/ace-editor';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

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
    // if (window.getSelection) { window.getSelection().removeAllRanges(); }
    // else if (document.selection) { document.selection.empty(); }
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
document.body.addEventListener('htmx:afterSwap', (event) => {
    tippy('[data-tooltip]', {
        content(reference) {
            return reference.getAttribute('data-tooltip');
        },
    });
});

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
tippy('[data-tooltip]', {
    content(reference) {
        return reference.getAttribute('data-tooltip');
    },
});

window.copyToClipboard = async function (text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch (err) {
        const textArea = document.createElement("textarea");
        textArea.value = text;

        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";

        document.body.prepend(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            //
        } finally {
            textArea.remove();
        }
    }
}
