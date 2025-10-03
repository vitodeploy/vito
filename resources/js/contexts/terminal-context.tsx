import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { Server } from '@/types/server';

interface TerminalState {
  isOpen: boolean;
  isExpanded: boolean;
  isPinned: boolean;
  user: string;
  dir: string;
  output: string;
  shellPrefix: string;
  commandHistory: string[];
  historyIndex: number;
  running: boolean;
  command: string;
  currentServer: Server | null;
  scrollPosition: number;
}

interface TerminalContextType {
  state: TerminalState;
  openTerminal: (server: Server) => void;
  updateServer: (server: Server) => void;
  closeTerminal: () => void;
  toggleExpanded: () => void;
  togglePinned: () => void;
  setUser: (user: string) => void;
  setDir: (dir: string) => void;
  setOutput: (output: string) => void;
  setShellPrefix: (prefix: string) => void;
  setCommandHistory: (history: string[]) => void;
  setHistoryIndex: (index: number) => void;
  setRunning: (running: boolean) => void;
  setCommand: (command: string) => void;
  updateOutput: (updater: (prev: string) => string) => void;
  clearOutput: () => void;
  resetTerminal: () => void;
  clearTerminalContent: () => void;
  setScrollPosition: (position: number) => void;
}

const initialState: TerminalState = {
  isOpen: false,
  isExpanded: false,
  isPinned: false,
  user: '',
  dir: '~',
  output: '',
  shellPrefix: '',
  commandHistory: [],
  historyIndex: -1,
  running: false,
  command: '',
  currentServer: null,
  scrollPosition: 0,
};

const TerminalContext = createContext<TerminalContextType | undefined>(undefined);

export function TerminalProvider({ children }: { children: ReactNode }) {
  // Load initial state from localStorage if available
  const loadInitialState = (): TerminalState => {
    if (typeof window === 'undefined') return initialState;

    try {
      const stored = localStorage.getItem('terminal_state');
      if (stored) {
        const parsed = JSON.parse(stored);
        // Only restore if terminal was open and pinned
        if (parsed.isOpen && parsed.isPinned && parsed.currentServer) {
          return parsed;
        }
      }
    } catch (error) {
      console.error('Failed to load terminal state from localStorage:', error);
    }

    return initialState;
  };

  const [state, setState] = useState<TerminalState>(loadInitialState);

  // Save state to localStorage whenever it changes
  const saveStateToStorage = useCallback((newState: TerminalState) => {
    if (typeof window === 'undefined') return;

    try {
      if (newState.currentServer) {
        // Always save terminal content for the current server
        const serverKey = `terminal_state_${newState.currentServer.id}`;

        if (newState.isOpen && newState.isPinned) {
          // Save full state when pinned
          localStorage.setItem('terminal_state', JSON.stringify(newState));
        } else if (newState.isOpen) {
          // Save content only when open but not pinned
          const contentState = {
            output: newState.output,
            dir: newState.dir,
            shellPrefix: newState.shellPrefix,
            commandHistory: newState.commandHistory,
            user: newState.user,
            serverId: newState.currentServer.id,
            scrollPosition: newState.scrollPosition,
          };
          localStorage.setItem(serverKey, JSON.stringify(contentState));
        } else {
          // Remove pinned state when closed
          localStorage.removeItem('terminal_state');
        }
      }
    } catch (error) {
      console.error('Failed to save terminal state to localStorage:', error);
    }
  }, []);

  // Custom setState that also saves to localStorage
  const setStateWithPersistence = useCallback(
    (newState: TerminalState | ((prev: TerminalState) => TerminalState)) => {
      setState((prev) => {
        const updatedState = typeof newState === 'function' ? newState(prev) : newState;
        saveStateToStorage(updatedState);
        return updatedState;
      });
    },
    [saveStateToStorage],
  );

  const openTerminal = useCallback(
    (server: Server) => {
      setStateWithPersistence((prev) => {
        // Check if we have saved content for this server
        let savedContent = null;
        if (typeof window !== 'undefined') {
          try {
            const serverKey = `terminal_state_${server.id}`;
            const stored = localStorage.getItem(serverKey);
            if (stored) {
              savedContent = JSON.parse(stored);
            }
          } catch (error) {
            console.error('Failed to load server terminal content:', error);
          }
        }

        // If switching to a different server, reset state
        if (prev.currentServer?.id !== server.id) {
          return {
            ...prev,
            isOpen: true,
            currentServer: server,
            user: server.ssh_user,
            // Restore saved content if available, otherwise reset
            output: savedContent?.output || '',
            dir: savedContent?.dir || '~',
            shellPrefix: savedContent?.shellPrefix || '',
            commandHistory: savedContent?.commandHistory || [],
            scrollPosition: savedContent?.scrollPosition || 0,
            command: '',
            running: false,
            historyIndex: -1,
          };
        }

        // Same server - restore content if available
        return {
          ...prev,
          isOpen: true,
          currentServer: server,
          user: server.ssh_user,
          // Restore saved content if available
          output: savedContent?.output || prev.output,
          dir: savedContent?.dir || prev.dir,
          shellPrefix: savedContent?.shellPrefix || prev.shellPrefix,
          commandHistory: savedContent?.commandHistory || prev.commandHistory,
          scrollPosition: savedContent?.scrollPosition || prev.scrollPosition,
          command: '',
          running: false,
          historyIndex: -1,
        };
      });
    },
    [setStateWithPersistence],
  );

  const updateServer = useCallback(
    (server: Server) => {
      setStateWithPersistence((prev) => {
        // If terminal is open and we're switching to a different server, reset state
        if (prev.isOpen && prev.currentServer?.id !== server.id) {
          return {
            ...prev,
            currentServer: server,
            user: server.ssh_user,
            output: '',
            dir: '~',
            shellPrefix: '',
            command: '',
            running: false,
            historyIndex: -1,
          };
        }
        // If terminal is closed or same server, just update the server info
        // Don't reset content when terminal is closed
        return {
          ...prev,
          currentServer: server,
          user: server.ssh_user,
        };
      });
    },
    [setStateWithPersistence],
  );

  const closeTerminal = useCallback(() => {
    setStateWithPersistence((prev) => ({
      ...prev,
      isOpen: false,
      isPinned: false,
    }));
  }, [setStateWithPersistence]);

  const toggleExpanded = useCallback(() => {
    setStateWithPersistence((prev) => ({
      ...prev,
      isExpanded: !prev.isExpanded,
    }));
  }, [setStateWithPersistence]);

  const togglePinned = useCallback(() => {
    setStateWithPersistence((prev) => ({
      ...prev,
      isPinned: !prev.isPinned,
    }));
  }, [setStateWithPersistence]);

  const setUser = useCallback(
    (user: string) => {
      setStateWithPersistence((prev) => ({ ...prev, user }));
    },
    [setStateWithPersistence],
  );

  const setDir = useCallback(
    (dir: string) => {
      setStateWithPersistence((prev) => ({ ...prev, dir }));
    },
    [setStateWithPersistence],
  );

  const setOutput = useCallback(
    (output: string) => {
      setStateWithPersistence((prev) => ({ ...prev, output }));
    },
    [setStateWithPersistence],
  );

  const setShellPrefix = useCallback(
    (shellPrefix: string) => {
      setStateWithPersistence((prev) => ({ ...prev, shellPrefix }));
    },
    [setStateWithPersistence],
  );

  const setCommandHistory = useCallback(
    (commandHistory: string[]) => {
      setStateWithPersistence((prev) => ({ ...prev, commandHistory }));
    },
    [setStateWithPersistence],
  );

  const setHistoryIndex = useCallback(
    (historyIndex: number) => {
      setStateWithPersistence((prev) => ({ ...prev, historyIndex }));
    },
    [setStateWithPersistence],
  );

  const setRunning = useCallback(
    (running: boolean) => {
      setStateWithPersistence((prev) => ({ ...prev, running }));
    },
    [setStateWithPersistence],
  );

  const setCommand = useCallback(
    (command: string) => {
      setStateWithPersistence((prev) => ({ ...prev, command }));
    },
    [setStateWithPersistence],
  );

  const updateOutput = useCallback(
    (updater: (prev: string) => string) => {
      setStateWithPersistence((prev) => ({ ...prev, output: updater(prev.output) }));
    },
    [setStateWithPersistence],
  );

  const clearOutput = useCallback(() => {
    setStateWithPersistence((prev) => ({ ...prev, output: '' }));
  }, [setStateWithPersistence]);

  const resetTerminal = useCallback(() => {
    setStateWithPersistence(initialState);
  }, [setStateWithPersistence]);

  const clearTerminalContent = useCallback(() => {
    if (typeof window !== 'undefined') {
      try {
        // Clear server-specific content storage
        const serverKey = `terminal_state_${state.currentServer?.id}`;
        if (serverKey) {
          localStorage.removeItem(serverKey);
        }
        // Clear pinned terminal storage
        localStorage.removeItem('terminal_state');
      } catch (error) {
        console.error('Failed to clear terminal content:', error);
      }
    }
    setStateWithPersistence(initialState);
  }, [setStateWithPersistence, state.currentServer]);

  const setScrollPosition = useCallback(
    (scrollPosition: number) => {
      setStateWithPersistence((prev) => ({ ...prev, scrollPosition }));
    },
    [setStateWithPersistence],
  );

  const value: TerminalContextType = {
    state,
    openTerminal,
    updateServer,
    closeTerminal,
    toggleExpanded,
    togglePinned,
    setUser,
    setDir,
    setOutput,
    setShellPrefix,
    setCommandHistory,
    setHistoryIndex,
    setRunning,
    setCommand,
    updateOutput,
    clearOutput,
    resetTerminal,
    clearTerminalContent,
    setScrollPosition,
  };

  return <TerminalContext.Provider value={value}>{children}</TerminalContext.Provider>;
}

export function useTerminal() {
  const context = useContext(TerminalContext);
  if (context === undefined) {
    throw new Error('useTerminal must be used within a TerminalProvider');
  }
  return context;
}
