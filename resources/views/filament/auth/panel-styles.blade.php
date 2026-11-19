{{-- Admin-wide style touches (no build step). --}}
<style>
    /*
     * Login / password pages (Filament "simple" layout): a subtle field of brand pixel squares.
     * One 160px SVG tile with a few squares in the brand purples, very low opacity.
     */
    .fi-simple-layout {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cg fill='%23612FE2'%3E%3Crect x='12' y='18' width='14' height='14' rx='2' opacity='.10'/%3E%3Crect x='96' y='40' width='10' height='10' rx='2' opacity='.07'/%3E%3Crect x='58' y='104' width='18' height='18' rx='3' opacity='.06'/%3E%3Crect x='132' y='122' width='8' height='8' rx='1.5' opacity='.12'/%3E%3C/g%3E%3Crect x='30' y='70' width='8' height='8' rx='1.5' fill='%238152F8' opacity='.09'/%3E%3C/svg%3E");
        background-size: 160px 160px;
    }

    .dark .fi-simple-layout {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cg fill='%238152F8'%3E%3Crect x='12' y='18' width='14' height='14' rx='2' opacity='.14'/%3E%3Crect x='96' y='40' width='10' height='10' rx='2' opacity='.10'/%3E%3Crect x='58' y='104' width='18' height='18' rx='3' opacity='.08'/%3E%3Crect x='132' y='122' width='8' height='8' rx='1.5' opacity='.16'/%3E%3C/g%3E%3Crect x='30' y='70' width='8' height='8' rx='1.5' fill='%23612FE2' opacity='.16'/%3E%3C/svg%3E");
    }

    /* Centre the logo on the login card. */
    .fi-simple-header .fi-logo {
        margin-inline: auto;
    }
</style>
