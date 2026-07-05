import { createServer } from 'node:net';

export function getAvailablePort(): Promise<number> {
  return new Promise((resolve, reject) => {
    const server = createServer();

    server.once('error', reject);
    server.listen(0, '127.0.0.1', () => {
      const address = server.address();
      if (typeof address === 'string' || address === null) {
        server.close(() => reject(new Error('Unable to allocate a TCP port')));
        return;
      }

      const port = address.port;
      server.close(() => resolve(port));
    });
  });
}
