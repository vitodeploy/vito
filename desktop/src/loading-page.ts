export function loadingPage(message: string): string {
  return dataPage('Starting Vito', message);
}

export function errorPage(message: string): string {
  return dataPage('Vito could not start', message);
}

function dataPage(title: string, message: string): string {
  const html = `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(title)}</title>
  <style>
    body {
      align-items: center;
      background: #0f172a;
      color: #e5e7eb;
      display: flex;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      height: 100vh;
      justify-content: center;
      margin: 0;
    }
    main {
      max-width: 520px;
      padding: 32px;
      text-align: center;
    }
    h1 {
      font-size: 22px;
      font-weight: 650;
      margin: 0 0 12px;
    }
    p {
      color: #cbd5e1;
      font-size: 14px;
      line-height: 1.6;
      margin: 0;
    }
  </style>
</head>
<body>
  <main>
    <h1>${escapeHtml(title)}</h1>
    <p>${escapeHtml(message)}</p>
  </main>
</body>
</html>`;

  return `data:text/html;charset=utf-8,${encodeURIComponent(html)}`;
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
