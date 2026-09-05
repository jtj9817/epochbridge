<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @routes
    <script>
        (function () {
            const path = window.location.pathname;
            const isPublicDarkRoute = path === '/'
                || path === '/login'
                || path === '/register'
                || path === '/forgot-password'
                || path.startsWith('/reset-password/')
                || path === '/two-factor-challenge'
                || path === '/verify-email'
                || path === '/confirm-password';

            if (isPublicDarkRoute) {
                document.documentElement.classList.add('dark');
                return;
            }

            let appearance = 'system';
            try {
                const stored = window.localStorage.getItem('appearance');
                if (stored === 'light' || stored === 'dark' || stored === 'system') {
                    appearance = stored;
                }
            } catch {
                appearance = 'system';
            }

            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const shouldUseDarkMode = appearance === 'dark' || (appearance === 'system' && prefersDark);
            document.documentElement.classList.toggle('dark', shouldUseDarkMode);
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="min-h-screen sd-bg sd-root font-sans antialiased">
    @inertia
</body>
</html>
