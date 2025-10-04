import { useRef, FormEvent, useCallback, useEffect, useState, ReactNode } from 'react';
import { Server } from '@/types/server';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { TerminalSquareIcon, PanelBottomIcon, PanelTopIcon, Trash2Icon, SquareIcon, LoaderCircleIcon, RefreshCwIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';

interface TerminalState {
  isExpanded: boolean;
  user: string;
  dir: string;
  output: string;
  shellPrefix: string;
  commandHistory: string[];
  historyIndex: number;
  serverId: number;
}

export default function InstantTerminal({ server, children }: { server: Server; children: ReactNode }) {
  const page = usePage<{ csrf_token: string }>();
  const [open, setOpen] = useState(false);

  // Helper functions for localStorage
  const getServerKey = (serverId: number) => `terminal_state_${serverId}`;

  const loadTerminalState = (): TerminalState | null => {
    if (typeof window === 'undefined') return null;

    try {
      const stored = localStorage.getItem(getServerKey(server.id));
      if (stored) {
        return JSON.parse(stored) as TerminalState;
      }
    } catch (error) {
      console.error('Failed to load terminal state from localStorage:', error);
    }
    return null;
  };

  const saveTerminalState = (state: TerminalState) => {
    if (typeof window === 'undefined') return;

    try {
      localStorage.setItem(getServerKey(server.id), JSON.stringify(state));
    } catch (error) {
      console.error('Failed to save terminal state to localStorage:', error);
    }
  };

  // Load initial state from localStorage
  const savedState = loadTerminalState();

  // Local state for terminal
  const [isExpanded, setIsExpanded] = useState(savedState?.isExpanded || false);
  const [user, setUser] = useState(savedState?.user || server.ssh_user);
  const [dir, setDir] = useState(savedState?.dir || '~');
  const [output, setOutput] = useState(savedState?.output || '');
  const [shellPrefix, setShellPrefix] = useState(savedState?.shellPrefix || '');
  const [commandHistory, setCommandHistory] = useState<string[]>(savedState?.commandHistory || []);
  const [historyIndex, setHistoryIndex] = useState(savedState?.historyIndex || -1);
  const [running, setRunning] = useState(false);
  const [command, setCommand] = useState('');
  const [cancelled, setCancelled] = useState(false);

  const outputRef = useRef<HTMLDivElement>(null);
  const commandRef = useRef<HTMLInputElement>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  const updateShellPrefixCallback = useCallback(
    (currentUser: string, currentDir: string) => {
      setShellPrefix(`${currentUser}@${server.name}:${currentDir}$`);
    },
    [server.name],
  );

  const focusCommand = () => {
    commandRef.current?.focus();
  };

  const getWorkingDir = useCallback(
    async (currentUser: string) => {
      try {
        const response = await fetch(route('console.working-dir', { server: server.id }));
        if (response.ok) {
          const data = await response.json();
          setDir(data.dir);
          updateShellPrefixCallback(currentUser, data.dir);
          return data.dir;
        }
      } catch (error) {
        console.error('Failed to get working directory:', error);
      }
      return dir;
    },
    [server.id, dir, updateShellPrefixCallback],
  );

  const scrollToBottom = () => {
    setTimeout(() => {
      if (outputRef.current) {
        outputRef.current.scrollTop = outputRef.current.scrollHeight;
      }
    }, 100);
  };

  const clearOutputCallback = useCallback(() => {
    if (!running) {
      setOutput('');
    }
  }, [running]);

  const initialize = useCallback(async () => {
    const currentDir = await getWorkingDir(user);
    updateShellPrefixCallback(user, currentDir);
    focusCommand();

    const handleMouseUp = () => {
      if (window.getSelection()?.toString()) {
        return;
      }
      focusCommand();
    };

    const storedHistory = localStorage.getItem('command_history');
    const history = storedHistory ? JSON.parse(storedHistory) : [];
    setCommandHistory(history);

    outputRef.current?.addEventListener('mouseup', handleMouseUp);

    return () => {
      outputRef.current?.removeEventListener('mouseup', handleMouseUp);
    };
  }, [user, updateShellPrefixCallback, getWorkingDir]);

  const handleUserChange = async (newUser: string) => {
    setUser(newUser);
    const currentDir = await getWorkingDir(newUser);
    updateShellPrefixCallback(newUser, currentDir);
  };

  const addToCommandHistory = (command: string) => {
    const storedHistory = localStorage.getItem('command_history');
    const history = storedHistory ? JSON.parse(storedHistory) : [];
    const updatedHistory = history.filter((cmd: string) => cmd !== command);
    updatedHistory.push(command);
    localStorage.setItem('command_history', JSON.stringify(updatedHistory));
    setCommandHistory(updatedHistory);
    setHistoryIndex(-1);
  };

  const run = async () => {
    if (!command.trim() || running) return;

    setRunning(true);
    setCancelled(false);
    const commandToRun = command.trim();
    const commandOutput = `${shellPrefix} ${commandToRun}\n`;

    // Create abort controller for this request
    abortControllerRef.current = new AbortController();

    // Add command to history
    addToCommandHistory(commandToRun);

    setOutput((prev: string) => prev + commandOutput);
    scrollToBottom();

    try {
      const response = await fetch(route('console.run', { server: server.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': page.props.csrf_token,
        },
        body: JSON.stringify({
          user,
          command,
        }),
        signal: abortControllerRef.current.signal,
      });

      setCommand('');

      if (response.body) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');

        while (true) {
          if (cancelled) {
            await reader.cancel();
            setOutput((prev) => prev + '\n');
            break;
          }

          const { value, done } = await reader.read();
          if (done) break;

          const textChunk = decoder.decode(value, { stream: true });
          setOutput((prev: string) => prev + textChunk);
          scrollToBottom();
        }
      }

      if (!cancelled) {
        setOutput((prev: string) => prev + '\n');
        await getWorkingDir(user);
      }
    } catch (error) {
      if (error instanceof Error && error.name === 'AbortError') {
        setOutput((prev) => prev + '\n');
      } else {
        console.error('Command execution failed:', error);
        setOutput((prev: string) => prev + '\nError executing command\n');
      }
    } finally {
      setRunning(false);
      setCancelled(false);
      abortControllerRef.current = null;
      setTimeout(() => focusCommand(), 100);
    }
  };

  const stop = () => {
    setCancelled(true);
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    setCommand('');
  };

  const newSession = async () => {
    await fetch(route('console.new-session', { server: server.id }), {});
    getWorkingDir(user);
    clearOutputCallback();
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    run();
  };

  // Save state to localStorage whenever it changes
  useEffect(() => {
    if (open) {
      const stateToSave = {
        isExpanded,
        user,
        dir,
        output,
        shellPrefix,
        commandHistory,
        historyIndex,
        serverId: server.id,
      };
      saveTerminalState(stateToSave);
    }
  }, [open, isExpanded, user, dir, output, shellPrefix, commandHistory, historyIndex, server.id]);

  // Initialize when terminal opens
  useEffect(() => {
    if (open) {
      initialize();
    }
  }, [open, initialize]);

  // Handle keyboard shortcuts
  useEffect(() => {
    if (!open) return;

    const handleKeydown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.key === 'l') {
        event.preventDefault();
        if (!running) {
          clearOutputCallback();
        }
      }
      if (event.key === 'Escape') {
        setOpen(false);
      }
      if (event.ctrlKey && event.key === 'c') {
        event.preventDefault();
        stop();
      }
    };

    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  }, [open, running, clearOutputCallback, setOpen, stop]);

  // Handle keyboard shortcuts
  useEffect(() => {
    const handleKeydown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'K') {
        event.preventDefault();
        setOpen(!open);
      }
    };

    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  }, [open, setOpen]);

  return (
    <Sheet open={open} onOpenChange={setOpen} modal>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent side="bottom" className={cn('flex flex-col p-0', isExpanded ? 'h-3/4' : 'h-1/3')} showClose={false}>
        <SheetHeader className="bg-muted/50 flex flex-row items-center justify-between border-b px-4 py-2">
          <div className="flex items-center gap-2">
            <TerminalSquareIcon className="h-4 w-4" />
            <SheetTitle className="text-sm font-medium">Headless Terminal - {server.name}</SheetTitle>
            <SheetDescription className="sr-only">Terminal</SheetDescription>
          </div>

          <div className="flex items-center gap-2">
            <Select value={user} onValueChange={handleUserChange} disabled={running}>
              <SelectTrigger className="h-7 w-20">
                <SelectValue />
              </SelectTrigger>
              <SelectContent side="top">
                {server.ssh_users.map((sshUser) => (
                  <SelectItem key={sshUser} value={sshUser}>
                    {sshUser}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {!running && (
              <Tooltip delayDuration={0}>
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="icon" className="h-7 w-7" onClick={clearOutputCallback}>
                    <Trash2Icon className="h-3 w-3" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Clear</TooltipContent>
              </Tooltip>
            )}

            {running && (
              <Tooltip delayDuration={0}>
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="icon" className="h-7 w-7" onClick={stop}>
                    <SquareIcon className="h-3 w-3" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Stop</TooltipContent>
              </Tooltip>
            )}

            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => newSession()}>
                  <RefreshCwIcon className="h-3 w-3" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>New Session</TooltipContent>
            </Tooltip>

            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => setIsExpanded(!isExpanded)}>
                  {isExpanded ? <PanelBottomIcon className="h-3 w-3" /> : <PanelTopIcon className="h-3 w-3" />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>{isExpanded ? 'Minimize' : 'Maximize'}</TooltipContent>
            </Tooltip>
          </div>
        </SheetHeader>

        {/* Terminal Content */}
        <div className="flex min-h-0 flex-1 flex-col bg-black">
          {/* Output Area - takes remaining space and is scrollable */}
          <div ref={outputRef} className="flex-1 overflow-auto">
            <div className="min-h-full p-4 font-mono text-sm break-all whitespace-pre-wrap text-white">{output}</div>
          </div>

          {/* Command Input - always sticks to bottom */}
          <div className="flex-shrink-0 border-0 p-4 text-white">
            {!running ? (
              <form onSubmit={handleSubmit} className="flex w-full items-center">
                <span className="flex-none font-mono text-sm">{shellPrefix}</span>
                <Input
                  ref={commandRef}
                  type="text"
                  value={command}
                  onChange={(e) => setCommand(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'ArrowUp') {
                      e.preventDefault();
                      if (commandHistory.length > 0) {
                        const newIndex = historyIndex === -1 ? commandHistory.length - 1 : Math.max(0, historyIndex - 1);
                        setHistoryIndex(newIndex);
                        setCommand(commandHistory[newIndex]);
                      }
                    } else if (e.key === 'ArrowDown') {
                      e.preventDefault();
                      if (historyIndex >= 0) {
                        const newIndex = historyIndex + 1;
                        if (newIndex >= commandHistory.length) {
                          setHistoryIndex(-1);
                          setCommand('');
                        } else {
                          setHistoryIndex(newIndex);
                          setCommand(commandHistory[newIndex]);
                        }
                      }
                    }
                  }}
                  className="ml-2 h-auto flex-grow border-0 bg-transparent! px-0 font-mono! text-sm shadow-none ring-0 outline-none focus:ring-0 focus:outline-none focus-visible:ring-0"
                  autoComplete="off"
                  autoFocus
                />
              </form>
            ) : (
              <div className="flex items-center gap-2">
                <LoaderCircleIcon className="text-muted-foreground h-4 w-4 animate-spin" />
                <span className="text-muted-foreground font-mono text-sm">Running command...</span>
                <button tabIndex={0} autoFocus className="opacity-0"></button>
              </div>
            )}
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
}
