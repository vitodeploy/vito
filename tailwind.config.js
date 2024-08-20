import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import colors from "tailwindcss/colors";
import flowbite from "flowbite/plugin";

/** @type {import("tailwindcss").Config} */
export default {
    darkMode: "class",

    safelist: [
        // Safelist all colors for text, background, border, etc.
        {
            pattern:
                /text-(red|green|blue|yellow|indigo|purple|pink|gray|white|black|orange|lime|emerald|teal|cyan|sky|violet|rose|fuchsia|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ["dark"], // Ensure dark mode variants are also included
        },
        {
            pattern:
                /bg-(red|green|blue|yellow|indigo|purple|pink|gray|white|black|orange|lime|emerald|teal|cyan|sky|violet|rose|fuchsia|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ["dark"],
        },
        {
            pattern:
                /border-(red|green|blue|yellow|indigo|purple|pink|gray|white|black|orange|lime|emerald|teal|cyan|sky|violet|rose|fuchsia|amber|slate|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900)/,
            variants: ["dark"],
        },
    ],

    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./node_modules/flowbite/**/*.js",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: colors.slate,
                primary: colors.indigo,
                green: colors.emerald,
            },
            variants: {
                extend: {
                    border: ["last"],
                },
            },
        },
    },

    plugins: [
        forms,
        flowbite({
            charts: true,
        }),
    ],
};
