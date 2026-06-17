@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#312E81">

    <title>VitoDeploy | Vito Managed Server</title>
    <meta name="description" content="A free, self-hosted server management tool. Server management made simple.">

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDI0IDEwMjQiPjxkZWZzPjxsaW5lYXJHcmFkaWVudCBpZD0iZyIgeDE9IjUxMiIgeTE9IjIyIiB4Mj0iNTEyIiB5Mj0iMTAwMiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPjxzdG9wIHN0b3AtY29sb3I9IiM2MzY2RjEiLz48c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMzOTNCOEIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB4PSIyMiIgeT0iMjIiIHdpZHRoPSI5ODAiIGhlaWdodD0iOTgwIiByeD0iMjAwIiBmaWxsPSIjMzEyRTgxIi8+PHJlY3QgeD0iMjIiIHk9IjIyIiB3aWR0aD0iOTgwIiBoZWlnaHQ9Ijk4MCIgcng9IjIwMCIgZmlsbD0idXJsKCNnKSIvPjxwYXRoIGQ9Ik01MTEuODc3IDgzMC45NUM0OTkuNDEgODMwLjk1IDQ4OC45MjcgODI4LjExNyA0ODAuNDI3IDgyMi40NUM0NzIuNDk0IDgxNi4yMTcgNDY1Ljk3NyA4MDcuMTUgNDYwLjg3NyA3OTUuMjVMMjMyLjIyNyAyNzcuNkMyMjcuNjk0IDI2NS43IDIyNi41NiAyNTUuNSAyMjguODI3IDI0N0MyMzEuMDk0IDIzOC41IDIzNS42MjcgMjMxLjk4MyAyNDIuNDI3IDIyNy40NUMyNDkuNzk0IDIyMi4zNSAyNTguNTc3IDIxOS44IDI2OC43NzcgMjE5LjhDMjgxLjgxIDIxOS44IDI5MS43MjcgMjIyLjYzMyAyOTguNTI3IDIyOC4zQzMwNS4zMjcgMjMzLjk2NyAzMTAuOTk0IDI0Mi40NjcgMzE1LjUyNyAyNTMuOEw1MjkuNzI3IDc1MC4ySDQ5OC4yNzdMNzEwLjc3NyAyNTIuOTVDNzE1Ljg3NyAyNDIuMTgzIDcyMS44MjcgMjMzLjk2NyA3MjguNjI3IDIyOC4zQzczNS45OTQgMjIyLjYzMyA3NDUuOTEgMjE5LjggNzU4LjM3NyAyMTkuOEM3NjguNTc3IDIxOS44IDc3Ni43OTQgMjIyLjM1IDc4My4wMjcgMjI3LjQ1Qzc4OS44MjcgMjMxLjk4MyA3OTQuMDc3IDIzOC41IDc5NS43NzcgMjQ3Qzc5Ny40NzcgMjU1LjUgNzk1Ljc3NyAyNjUuNyA3OTAuNjc3IDI3Ny42TDU2Mi44NzcgNzk1LjI1QzU1Ny43NzcgODA3LjE1IDU1MS4yNiA4MTYuMjE3IDU0My4zMjcgODIyLjQ1QzUzNS4zOTQgODI4LjExNyA1MjQuOTEgODMwLjk1IDUxMS44NzcgODMwLjk1WiIgZmlsbD0id2hpdGUiLz48L3N2Zz4=">

    <meta name="robots" content="noindex,follow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="This server is managed by VitoDeploy">
    <meta property="og:description" content="A free, self-hosted server management tool. Server management made simple.">
    <meta property="og:url" content="https://vitodeploy.com">
    <meta property="og:image" content="https://vitodeploy.com/img/logo.svg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="This server is managed by VitoDeploy">
    <meta name="twitter:description" content="A free, self-hosted server management tool. Server management made simple.">
    <meta name="twitter:image" content="https://vitodeploy.com/img/logo.svg">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "VitoDeploy",
        "description": "A free, self-hosted server management tool. Server management made simple.",
        "applicationCategory": "DeveloperApplication",
        "operatingSystem": "Linux",
        "url": "https://vitodeploy.com",
        "sameAs": [
            "https://vitodeploy.com",
            "https://github.com/vitodeploy/vito"
        ],
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            color: #E5E7EB;
            background-color: #0F0E27;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(99, 102, 241, 0.45), transparent 60%),
                radial-gradient(ellipse 60% 50% at 100% 100%, rgba(139, 92, 246, 0.25), transparent 60%),
                linear-gradient(180deg, #1E1B4B 0%, #0F0E27 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .page {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            text-align: center;
        }

        .card {
            max-width: 440px;
            width: 100%;
            padding: 36px 32px 32px;
            border-radius: 20px;
            background: rgba(17, 16, 49, 0.55);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(99, 102, 241, 0.22);
            box-shadow:
                0 20px 44px -16px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(255, 255, 255, 0.03) inset;
        }

        .logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            display: block;
        }

        .eyebrow {
            display: inline-block;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #A5B4FC;
            margin: 0 0 16px;
        }

        h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 10px;
            color: #FFFFFF;
            letter-spacing: -0.01em;
            line-height: 1.35;
        }

        .lede {
            font-size: 14px;
            color: #C7D2FE;
            margin: 0 0 20px;
            line-height: 1.55;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(165, 180, 252, 0.18), transparent);
            margin: 0 0 20px;
        }

        .sub {
            font-size: 12.5px;
            color: #8B92B5;
            margin: 0 0 24px;
            line-height: 1.55;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 13px;
            text-decoration: none;
            transition: transform 120ms ease, box-shadow 120ms ease, background-color 120ms ease, border-color 120ms ease;
            border: 1px solid transparent;
            line-height: 1;
        }

        .btn:focus-visible {
            outline: 2px solid #A5B4FC;
            outline-offset: 2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            color: #FFFFFF;
            box-shadow: 0 6px 14px -4px rgba(99, 102, 241, 0.55);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px -6px rgba(99, 102, 241, 0.7);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: #D1D5DB;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .btn svg {
            width: 14px;
            height: 14px;
        }

        footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #6B7280;
        }

        footer a {
            color: #A5B4FC;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card { padding: 28px 22px 24px; border-radius: 18px; }
            .logo { width: 56px; height: 56px; margin-bottom: 20px; }
            h1 { font-size: 18px; }
            .lede { font-size: 13.5px; }
            .actions { flex-direction: column; gap: 8px; }
            .actions .btn { width: 100%; justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .btn { transition: none; }
            .btn:hover { transform: none; }
        }
    </style>
</head>
<body>
    <main class="page">
        <article class="card">
            <svg class="logo" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="VitoDeploy logo">
                <defs>
                    <linearGradient id="vito-bg" x1="512" y1="22" x2="512" y2="1002" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#6366F1"/>
                        <stop offset="1" stop-color="#393B8B"/>
                    </linearGradient>
                </defs>
                <rect x="22" y="22" width="980" height="980" rx="200" fill="#312E81"/>
                <rect x="22" y="22" width="980" height="980" rx="200" fill="url(#vito-bg)"/>
                <path d="M511.877 830.95C499.41 830.95 488.927 828.117 480.427 822.45C472.494 816.217 465.977 807.15 460.877 795.25L232.227 277.6C227.694 265.7 226.56 255.5 228.827 247C231.094 238.5 235.627 231.983 242.427 227.45C249.794 222.35 258.577 219.8 268.777 219.8C281.81 219.8 291.727 222.633 298.527 228.3C305.327 233.967 310.994 242.467 315.527 253.8L529.727 750.2H498.277L710.777 252.95C715.877 242.183 721.827 233.967 728.627 228.3C735.994 222.633 745.91 219.8 758.377 219.8C768.577 219.8 776.794 222.35 783.027 227.45C789.827 231.983 794.077 238.5 795.777 247C797.477 255.5 795.777 265.7 790.677 277.6L562.877 795.25C557.777 807.15 551.26 816.217 543.327 822.45C535.394 828.117 524.91 830.95 511.877 830.95Z" fill="#FFFFFF"/>
            </svg>

            <span class="eyebrow">VitoDeploy</span>
            <h1>This server is managed by Vito</h1>
            <p class="lede">A free, self-hosted server management tool. Server management made simple.</p>

            <div class="divider"></div>

            <p class="sub">If you're the owner of this server, this domain hasn't been configured yet. Create a new site for it, or add it to an existing one.</p>

            <div class="actions">
                <a class="btn btn-primary" href="https://vitodeploy.com" rel="noopener" target="_blank">
                    <span>Visit website</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 17L17 7"/>
                        <path d="M7 7h10v10"/>
                    </svg>
                </a>
                <a class="btn btn-secondary" href="https://github.com/vitodeploy/vito" rel="noopener" target="_blank">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56 0-.27-.01-.99-.02-1.95-3.2.69-3.87-1.54-3.87-1.54-.52-1.33-1.28-1.68-1.28-1.68-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.74.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.04 0 0 .97-.31 3.18 1.18.92-.26 1.91-.39 2.9-.39.99 0 1.98.13 2.9.39 2.21-1.49 3.18-1.18 3.18-1.18.63 1.58.23 2.75.11 3.04.74.81 1.19 1.84 1.19 3.1 0 4.43-2.69 5.41-5.25 5.69.41.36.78 1.06.78 2.14 0 1.55-.01 2.8-.01 3.18 0 .31.21.68.8.56C20.21 21.39 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5z"/>
                    </svg>
                    <span>GitHub</span>
                </a>
            </div>
        </article>
    </main>

    <footer>
        Powered by <a href="https://vitodeploy.com" rel="noopener" target="_blank">VitoDeploy</a> &middot; <a href="https://github.com/vitodeploy/vito" rel="noopener" target="_blank">GitHub</a>
    </footer>
</body>
</html>
@endverbatim
