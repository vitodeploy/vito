import preset from "../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    presets: [preset],
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
        "./app/Web/**/*.php",
        "./resources/views/web/**/*.blade.php",
        "./resources/views/components/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],
};
