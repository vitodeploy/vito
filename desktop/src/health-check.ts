export async function waitForHttp(url: string, timeoutMs: number): Promise<void> {
  const deadline = Date.now() + timeoutMs;
  let lastError: unknown;

  while (Date.now() < deadline) {
    try {
      const response = await fetch(url);
      if (response.ok) {
        return;
      }
      lastError = new Error(`Health check failed with ${response.status}`);
    } catch (error) {
      lastError = error;
    }

    await delay(500);
  }

  throw lastError instanceof Error ? lastError : new Error('Health check timed out');
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
