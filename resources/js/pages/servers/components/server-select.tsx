import { type Server } from '@/types/server';
import { useState, useEffect, useRef, type ReactNode } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

interface ServerSelectProps {
  value: string;
  valueBy?: keyof Server;
  onValueChange?: (selectedServer?: Server) => void;
  // Advanced API for server-switch usage
  onValueChangeAdvanced?: (value: string, server: Server) => void;
  id?: string;
  prefetch?: boolean;
  placeholder?: string;
  trigger?: ReactNode;
  className?: string;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  footer?: ReactNode;
  showIp?: boolean;
}

export default function ServerSelect({
  value,
  valueBy = 'id',
  onValueChange,
  onValueChangeAdvanced,
  id,
  prefetch,
  placeholder = 'Select server...',
  trigger,
  className,
  open: controlledOpen,
  onOpenChange: controlledOnOpenChange,
  footer,
  showIp = true,
}: ServerSelectProps) {
  const page = usePage<SharedData>();
  const [internalOpen, setInternalOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [selected, setSelected] = useState<string>(value);
  const loadMoreRef = useRef<HTMLDivElement>(null);
  const refetchRef = useRef<(() => void) | null>(null);

  const open = controlledOpen !== undefined ? controlledOpen : internalOpen;
  const setOpen = controlledOnOpenChange || setInternalOpen;

  useEffect(() => {
    setSelected(value);
  }, [value]);

  // Debounce query input
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      setDebouncedQuery(query);
    }, 300);

    return () => clearTimeout(timeoutId);
  }, [query]);

  const { data, isFetching, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery<Server[]>({
    queryKey: ['servers', page.props.auth.currentProject?.id, debouncedQuery],
    queryFn: async ({ pageParam = 1 }) => {
      const response = await axios.get(route('servers.json', { query: debouncedQuery || '', page: pageParam }));
      return response.data;
    },
    enabled: open && prefetch !== false,
    staleTime: Infinity,
    gcTime: 1000 * 60 * 5,
    refetchOnMount: false,
    refetchOnWindowFocus: false,
    initialPageParam: 1,
    getNextPageParam: (lastPage, allPages) => {
      if (!lastPage || !Array.isArray(lastPage)) {
        return undefined;
      }
      return lastPage.length === 10 ? allPages.length + 1 : undefined;
    },
  });

  const servers = data?.pages.flat() ?? [];

  useEffect(() => {
    if (refetch) {
      refetchRef.current = refetch;
    }
  }, [refetch]);

  useEffect(() => {
    if (!open || !hasNextPage) return;

    let observer: IntersectionObserver | null = null;
    const timeoutId = setTimeout(() => {
      if (!loadMoreRef.current) return;

      observer = new IntersectionObserver(
        (entries) => {
          const [entry] = entries;
          if (entry.isIntersecting && hasNextPage && !isFetchingNextPage) {
            fetchNextPage();
          }
        },
        { threshold: 0.1 },
      );

      observer.observe(loadMoreRef.current);
    }, 100);

    return () => {
      clearTimeout(timeoutId);
      if (observer) {
        observer.disconnect();
      }
    };
  }, [open, hasNextPage, isFetchingNextPage, fetchNextPage, debouncedQuery, servers.length]);

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      const commandList = document.querySelector('[data-slot="command-list"]');
      if (commandList instanceof HTMLElement) {
        commandList.scrollTop = 0;
      }
      setQuery('');
    }
  };

  const selectedServer = servers.find((server) => String(server[valueBy] as Server[keyof Server]) === selected);

  const handleSelect = (server: Server, currentValue: string) => {
    const newSelected = currentValue === selected ? '' : currentValue;
    setSelected(newSelected);
    setOpen(false);

    // Support both API patterns
    if (onValueChangeAdvanced) {
      onValueChangeAdvanced(newSelected, server);
    } else if (onValueChange) {
      onValueChange(newSelected === '' ? undefined : server);
    }
  };

  const defaultTrigger = (
    <Button id={id} variant="outline" role="combobox" aria-expanded={open} className={cn('w-full justify-between', className)}>
      {selectedServer ? selectedServer.name : placeholder}
      <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
    </Button>
  );

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>{trigger || defaultTrigger}</PopoverTrigger>
      <PopoverContent className="flex max-h-[400px] w-56 flex-col p-0" align="start">
        <Command shouldFilter={false} className="flex flex-col overflow-hidden">
          <CommandInput placeholder="Search server..." value={query} onValueChange={setQuery} />
          <CommandList data-slot="command-list" className="min-h-0 flex-1 overflow-y-auto" onWheel={(e) => e.stopPropagation()}>
            {servers.length === 0 ? (
              <div className="text-muted-foreground py-6 text-center text-sm">
                {isFetching ? 'Searching...' : query === '' ? 'Start typing to search servers' : 'No servers found.'}
              </div>
            ) : (
              <CommandGroup>
                {servers.map((server: Server) => {
                  const serverValue = String(server[valueBy] as Server[keyof Server]);
                  return (
                    <CommandItem
                      key={`server-select-${server.id}`}
                      value={serverValue}
                      onSelect={() => handleSelect(server, serverValue)}
                      className="truncate"
                    >
                      {server.name}
                      {showIp && ` (${server.ip})`}
                      <CheckIcon className={cn('ml-auto', selected === serverValue ? 'opacity-100' : 'opacity-0')} />
                    </CommandItem>
                  );
                })}
                {hasNextPage && (
                  <div ref={loadMoreRef} className="flex justify-center py-2">
                    {isFetchingNextPage ? (
                      <span className="text-muted-foreground text-xs">Loading more...</span>
                    ) : (
                      <span className="text-muted-foreground text-xs">Scroll for more</span>
                    )}
                  </div>
                )}
              </CommandGroup>
            )}
          </CommandList>
          {footer && <div className="shrink-0 border-t">{footer}</div>}
        </Command>
      </PopoverContent>
    </Popover>
  );
}

// Named export for backward compatibility
export { ServerSelect };
