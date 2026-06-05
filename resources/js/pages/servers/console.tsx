import { useEffect, useRef, useCallback, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { WebLinksAddon } from '@xterm/addon-web-links';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { RefreshCwIcon } from 'lucide-react';
import '@xterm/xterm/css/xterm.css';

type ConnectionStatus = 'disconnected' | 'connecting' | 'connected' | 'error';

export default function Console() {
  const { server, ssh_users, csrf_token } = usePage<{ server: Server; ssh_users: string[]; csrf_token: string }>().props;
  const [user, setUser] = useState(server.ssh_user);
  const [status, setStatus] = useState<ConnectionStatus>('disconnected');

  const terminalRef = useRef<HTMLDivElement>(null);
  const xtermRef = useRef<Terminal | null>(null);
  const fitAddonRef = useRef<FitAddon | null>(null);
  const wsRef = useRef<WebSocket | null>(null);

  const requestToken = useCallback(
    async (sshUser: string): Promise<{ token: string; url: string } | null> => {
      try {
        const response = await fetch(route('console.token', { server: server.id }), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf_token,
          },
          body: JSON.stringify({ user: sshUser }),
        });
        if (!response.ok) return null;
        return await response.json();
      } catch {
        return null;
      }
    },
    [server.id, csrf_token],
  );

  const disconnect = useCallback(() => {
    if (wsRef.current) {
      wsRef.current.close();
      wsRef.current = null;
    }
    setStatus('disconnected');
  }, []);

  const connect = useCallback(
    async (sshUser: string) => {
      disconnect();

      const term = xtermRef.current;
      if (!term) return;

      setStatus('connecting');
      term.writeln('\r\nConnecting...');

      const tokenData = await requestToken(sshUser);
      if (!tokenData) {
        setStatus('error');
        term.writeln('\r\n\x1b[31mFailed to get authentication token.\x1b[0m');
        return;
      }

      const dims = fitAddonRef.current?.proposeDimensions();
      const cols = dims?.cols ?? 80;
      const rows = dims?.rows ?? 24;
      const ws = new WebSocket(`${tokenData.url}?token=${tokenData.token}&cols=${cols}&rows=${rows}`);
      wsRef.current = ws;

      ws.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          switch (data.type) {
            case 'connected':
              setStatus('connected');
              term.reset();
              term.focus();
              fitAddonRef.current?.fit();
              break;
            case 'output':
              term.write(data.data);
              break;
            case 'error':
              setStatus('error');
              term.writeln(`\r\n\x1b[31m${data.message}\x1b[0m`);
              break;
            case 'disconnected':
              setStatus('disconnected');
              term.writeln(`\r\n\x1b[33m${data.message || 'Disconnected'}\x1b[0m`);
              break;
          }
        } catch {
          term.write(event.data);
        }
      };

      ws.onerror = () => {
        setStatus('error');
        term.writeln('\r\n\x1b[31mWebSocket connection error.\x1b[0m');
      };

      ws.onclose = () => {
        if (wsRef.current === ws) {
          setStatus('disconnected');
          term.writeln('\r\n\x1b[33mConnection closed.\x1b[0m');
          wsRef.current = null;
        }
      };
    },
    [disconnect, requestToken],
  );

  const newSession = useCallback(() => {
    disconnect();
    xtermRef.current?.reset();
    connect(user);
  }, [disconnect, connect, user]);

  const handleUserChange = useCallback(
    (newUser: string) => {
      setUser(newUser);
      disconnect();
      xtermRef.current?.reset();
      connect(newUser);
    },
    [disconnect, connect],
  );

  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      e.preventDefault();
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, []);

  useEffect(() => {
    const handleResize = () => {
      fitAddonRef.current?.fit();
      const dims = fitAddonRef.current?.proposeDimensions();
      if (dims && wsRef.current?.readyState === WebSocket.OPEN) {
        wsRef.current.send(JSON.stringify({ type: 'resize', cols: dims.cols, rows: dims.rows }));
      }
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Initialize xterm + connect on mount
  useEffect(() => {
    const container = terminalRef.current;
    if (!container || xtermRef.current) return;

    const term = new Terminal({
      cursorBlink: true,
      fontSize: 14,
      fontFamily: 'Menlo, Monaco, "Courier New", monospace',
      theme: {
        background: '#000000',
        foreground: '#ffffff',
        cursor: '#ffffff',
        selectionBackground: '#ffffff40',
      },
      allowProposedApi: true,
    });

    const fitAddon = new FitAddon();
    const webLinksAddon = new WebLinksAddon();

    term.loadAddon(fitAddon);
    term.loadAddon(webLinksAddon);
    term.open(container);
    fitAddon.fit();

    xtermRef.current = term;
    fitAddonRef.current = fitAddon;

    term.onData((data) => {
      if (wsRef.current?.readyState === WebSocket.OPEN) {
        wsRef.current.send(JSON.stringify({ type: 'input', data }));
      }
    });

    // Connect immediately
    connect(server.ssh_user);

    return () => {
      wsRef.current?.close();
      term.dispose();
    };
  }, []);

  // Update document title with status
  const statusLabel = status === 'connected' ? '' : ` (${status})`;

  const statusDot = () => {
    switch (status) {
      case 'connected':
        return <span className="h-2 w-2 rounded-full bg-green-500" />;
      case 'connecting':
        return <span className="h-2 w-2 animate-pulse rounded-full bg-yellow-500" />;
      case 'error':
        return <span className="h-2 w-2 rounded-full bg-red-500" />;
      default:
        return <span className="h-2 w-2 rounded-full bg-gray-500" />;
    }
  };

  return (
    <div className="bg-background flex h-screen flex-col">
      <Head title={`Terminal - ${server.name}${statusLabel}`} />

      {/* Toolbar */}
      <div className="border-border bg-card flex items-center justify-between border-b px-3 py-1.5">
        <div className="flex items-center gap-2">
          <span className="text-foreground text-sm font-medium">{server.name}</span>
          {statusDot()}
        </div>

        <div className="flex items-center gap-2">
          <Select value={user} onValueChange={handleUserChange} disabled={status === 'connecting'}>
            <SelectTrigger className="h-7 w-24 text-xs">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {ssh_users.map((sshUser) => (
                <SelectItem key={sshUser} value={sshUser}>
                  {sshUser}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Button
            variant="ghost"
            size="icon"
            className="text-muted-foreground hover:text-foreground h-7 w-7"
            onClick={newSession}
            title="New Session"
          >
            <RefreshCwIcon className="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>

      {/* Terminal */}
      <div className="min-h-0 flex-1 p-1">
        <div ref={terminalRef} className="h-full w-full" />
      </div>
    </div>
  );
}
