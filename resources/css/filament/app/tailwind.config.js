import preset from "../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    presets: [preset],
    content: [
        "./app/Web/**/*.php",
        "./resources/views/web/**/*.blade.php",
        "./resources/views/components/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],
};
