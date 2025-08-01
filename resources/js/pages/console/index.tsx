import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, Trash2, Square, LoaderCircleIcon, RefreshCcwIcon } from 'lucide-react';
import Container from '@/components/container';
import { useState, useRef, FormEvent, useCallback } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import LogOutput from '@/components/log-output';

export default function Console() {
  const page = usePage<{
    server: Server;
  }>();

  const [user, setUser] = useState(page.props.server.ssh_user);
  const [running, setRunning] = useState(false);
  const [dir, setDir] = useState('~');
  const [command, setCommand] = useState('');
  const [output, setOutput] = useState('');
  const [shellPrefix, setShellPrefix] = useState('');
  const [clearAfterCommand] = useState(false);
  const [initialized, setInitialized] = useState(false);

  const outputRef = useRef<HTMLDivElement>(null);
  const commandRef = useRef<HTMLInputElement>(null);

  const updateShellPrefix = useCallback(
    (currentUser: string, currentDir: string) => {
      setShellPrefix(`${currentUser}@${page.props.server.name}:${currentDir}$`);
    },
    [page.props.server.name],
  );

  const focusCommand = () => {
    commandRef.current?.focus();
  };

  const getWorkingDir = useCallback(
    async (currentUser: string) => {
      try {
        const response = await fetch(route('console.working-dir', { server: page.props.server.id }));
        if (response.ok) {
          const data = await response.json();
          setDir(data.dir);
          updateShellPrefix(currentUser, data.dir);
          return data.dir;
        }
      } catch (error) {
        console.error('Failed to get working directory:', error);
      }
      return dir;
    },
    [page.props.server.id, dir, updateShellPrefix],
  );

  const scrollToBottom = () => {
    setTimeout(() => {
      if (outputRef.current) {
        outputRef.current.scrollTop = outputRef.current.scrollHeight;
      }
    }, 100);
  };

  const clearOutput = useCallback(() => {
    if (!running) {
      setOutput('');
    }
  }, [running]);

  const initialize = useCallback(async () => {
    if (initialized) return;

    const currentDir = await getWorkingDir(user);
    updateShellPrefix(user, currentDir);
    focusCommand();

    const handleKeydown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.key === 'l') {
        event.preventDefault();
        if (!running) {
          clearOutput();
        }
      }
    };

    const handleMouseUp = () => {
      if (window.getSelection()?.toString()) {
        return;
      }
      focusCommand();
    };

    document.addEventListener('keydown', handleKeydown);
    outputRef.current?.addEventListener('mouseup', handleMouseUp);

    setInitialized(true);

    return () => {
      document.removeEventListener('keydown', handleKeydown);
      outputRef.current?.removeEventListener('mouseup', handleMouseUp);
    };
  }, [user, updateShellPrefix, initialized, running, clearOutput, getWorkingDir]);

  const handleUserChange = async (newUser: string) => {
    setUser(newUser);
    const currentDir = await getWorkingDir(newUser);
    updateShellPrefix(newUser, currentDir);
  };

  const run = async () => {
    if (!command.trim() || running) return;

    setRunning(true);
    const commandOutput = `${shellPrefix} ${command}\n`;
    const cancelled = false;

    if (clearAfterCommand) {
      setOutput(commandOutput);
    } else {
      setOutput((prev) => prev + commandOutput);
    }

    scrollToBottom();

    try {
      const response = await fetch(route('console.run', { server: page.props.server.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': page.props.csrf_token as string,
        },
        body: JSON.stringify({
          user,
          command,
        }),
      });

      setCommand('');

      if (response.body) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');

        while (true) {
          if (cancelled) {
            await reader.cancel();
            setOutput((prev) => prev + '\nStopped!');
            break;
          }

          const { value, done } = await reader.read();
          if (done) break;

          const textChunk = decoder.decode(value, { stream: true });
          setOutput((prev) => prev + textChunk);
          scrollToBottom();
        }
      }

      setOutput((prev) => prev + '\n');
      await getWorkingDir(user);
    } catch (error) {
      console.error('Command execution failed:', error);
      setOutput((prev) => prev + '\nError executing command\n');
    } finally {
      setRunning(false);
      setTimeout(() => focusCommand(), 100);
    }
  };

  const stop = () => {
    setRunning(false);
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    run();
  };

  const newSession = async () => {
    await fetch(route('console.new-session', { server: page.props.server.id }), {});
    getWorkingDir(user);
  };

  // Initialize on first render
  if (!initialized) {
    initialize();
  }

  return (
    <ServerLayout>
      <Head title={`Console - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Headless Console" description="Here you can run console commands on your server" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/console" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Select value={user} onValueChange={handleUserChange} disabled={running}>
              <SelectTrigger className="w-20">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {page.props.server.ssh_users.map((sshUser) => (
                  <SelectItem key={sshUser} value={sshUser}>
                    {sshUser}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {!running && (
              <Button variant="outline" onClick={clearOutput}>
                <Trash2 className="h-4 w-4" />
                <span className="ml-2 hidden lg:block">Clear</span>
              </Button>
            )}
            {running && (
              <Button variant="outline" onClick={stop}>
                <Square className="h-4 w-4" />
                <span className="ml-2 hidden lg:block">Stop</span>
              </Button>
            )}
            <Button variant="outline" onClick={newSession}>
              <RefreshCcwIcon className="h-4 w-4" />
              <span className="ml-2 hidden lg:block">New Session</span>
            </Button>
          </div>
        </HeaderContainer>

        <div className="relative">
          <div ref={outputRef}>
            <LogOutput className="rounded-xl border pb-12 shadow-xs">{output}</LogOutput>
          </div>

          <div className="absolute right-0 bottom-0 left-0 p-4">
            {!running ? (
              <form onSubmit={handleSubmit} className="flex w-full items-center">
                <span className="flex-none">{shellPrefix}</span>
                <Input
                  ref={commandRef}
                  type="text"
                  value={command}
                  onChange={(e) => setCommand(e.target.value)}
                  className="ml-2 h-auto flex-grow border-0 bg-transparent! px-0 shadow-none ring-0 outline-none focus:ring-0 focus:outline-none focus-visible:ring-0"
                  autoComplete="off"
                  autoFocus
                />
                <button type="submit" className="hidden" />
              </form>
            ) : (
              <LoaderCircleIcon className="text-muted-foreground animate-spin" />
            )}
          </div>
        </div>
      </Container>
    </ServerLayout>
  );
}
