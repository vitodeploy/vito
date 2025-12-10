import { type Site } from '@/types/site';
import { useState, useEffect, useRef, type ReactNode } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import axios from 'axios';

interface SiteSelectProps {
  serverId: number;
  value: string;
  valueBy?: keyof Site;
  onValueChange?: (selectedSite?: Site) => void;
  // Advanced API for site-switch usage
  onValueChangeAdvanced?: (value: string, site: Site) => void;
  id?: string;
  prefetch?: boolean;
  placeholder?: string;
  trigger?: ReactNode;
  className?: string;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  footer?: ReactNode;
}

export default function SiteSelect({
  serverId,
  value,
  valueBy = 'id',
  onValueChange,
  onValueChangeAdvanced,
  id,
  prefetch,
  placeholder = 'Select site...',
  trigger,
  className,
  open: controlledOpen,
  onOpenChange: controlledOnOpenChange,
  footer,
}: SiteSelectProps) {
  const [internalOpen, setInternalOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [selected, setSelected] = useState<string>(value);
  const loadMoreRef = useRef<HTMLDivElement>(null);
  const refetchRef = useRef<(() => void) | null>(null);
  const prevServerIdRef = useRef<number>(serverId);

  const open = controlledOpen !== undefined ? controlledOpen : internalOpen;
  const setOpen = controlledOnOpenChange || setInternalOpen;

  useEffect(() => {
    if (prevServerIdRef.current !== serverId) {
      prevServerIdRef.current = serverId;
      setSelected('');
      if (onValueChange) {
        onValueChange(undefined);
      }
    }
  }, [serverId]);

  useEffect(() => {
    if (prevServerIdRef.current === serverId) {
      setSelected(value);
    }
  }, [value, serverId]);

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      setDebouncedQuery(query);
    }, 300);

    return () => clearTimeout(timeoutId);
  }, [query]);

  const { data, isFetching, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery<Site[]>({
    queryKey: ['sites', serverId, debouncedQuery],
    queryFn: async ({ pageParam = 1 }) => {
      const response = await axios.get(route('sites.json', { server: serverId, query: debouncedQuery || '', page: pageParam }));
      return response.data;
    },
    enabled: open && prefetch !== false && !!serverId,
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

  const sites = data?.pages.flat() ?? [];

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
  }, [open, hasNextPage, isFetchingNextPage, fetchNextPage, debouncedQuery, sites.length]);

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

  const selectedSite = selected && sites.length > 0 ? sites.find((site) => String(site[valueBy] as Site[keyof Site]) === selected) : undefined;

  const handleSelect = (site: Site, currentValue: string) => {
    const newSelected = currentValue === selected ? '' : currentValue;
    setSelected(newSelected);
    setOpen(false);

    // Support both API patterns
    if (onValueChangeAdvanced) {
      onValueChangeAdvanced(newSelected, site);
    } else if (onValueChange) {
      onValueChange(newSelected === '' ? undefined : site);
    }
  };

  const defaultTrigger = (
    <Button id={id} variant="outline" role="combobox" aria-expanded={open} className={cn('w-full justify-between', className)}>
      {selectedSite ? selectedSite.domain : placeholder}
      <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
    </Button>
  );

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>{trigger || defaultTrigger}</PopoverTrigger>
      <PopoverContent className="flex max-h-[400px] w-56 flex-col p-0" align="start">
        <Command shouldFilter={false} className="flex flex-col overflow-hidden">
          <CommandInput placeholder="Search site..." value={query} onValueChange={setQuery} />
          <CommandList data-slot="command-list" className="min-h-0 flex-1 overflow-y-auto" onWheel={(e) => e.stopPropagation()}>
            {sites.length === 0 ? (
              <div className="text-muted-foreground py-6 text-center text-sm">
                {isFetching ? 'Searching...' : query === '' ? 'Start typing to search sites' : 'No sites found.'}
              </div>
            ) : (
              <CommandGroup>
                {sites.map((site: Site) => {
                  const siteValue = String(site[valueBy] as Site[keyof Site]);
                  return (
                    <CommandItem key={`site-select-${site.id}`} value={siteValue} onSelect={() => handleSelect(site, siteValue)} className="truncate">
                      {site.domain}
                      <CheckIcon className={cn('ml-auto', selected === siteValue ? 'opacity-100' : 'opacity-0')} />
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
export { SiteSelect };
