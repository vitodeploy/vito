import { type Project } from '@/types/project';
import { useState, useEffect, useRef } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { ReactNode } from 'react';

interface ProjectSelectProps {
  value?: string;
  onValueChange: (value: string, project: Project) => void;
  placeholder?: string;
  trigger?: ReactNode;
  className?: string;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  footer?: ReactNode;
}

export function ProjectSelect({
  value,
  onValueChange,
  placeholder = 'Select project...',
  trigger,
  className,
  open: controlledOpen,
  onOpenChange: controlledOnOpenChange,
  footer,
}: ProjectSelectProps) {
  const [internalOpen, setInternalOpen] = useState(false);
  const [query, setQuery] = useState('');
  const loadMoreRef = useRef<HTMLDivElement>(null);

  const open = controlledOpen !== undefined ? controlledOpen : internalOpen;
  const setOpen = controlledOnOpenChange || setInternalOpen;

  const { data, isFetching, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery<Project[]>({
    queryKey: ['projects', query],
    queryFn: async ({ pageParam = 1 }) => {
      const response = await axios.get(route('projects.json', { query: query || '', page: pageParam }));
      return response.data;
    },
    enabled: open,
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

  const projects = data?.pages.flat() ?? [];
  const selectedProject = projects.find((project) => project.id.toString() === value);
  const refetchRef = useRef<(() => void) | null>(null);

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
  }, [open, hasNextPage, isFetchingNextPage, fetchNextPage, query, projects.length]);

  const handleClose = () => {
    const commandList = document.querySelector('[data-slot="command-list"]');
    if (commandList instanceof HTMLElement) {
      commandList.scrollTop = 0;
    }
    setQuery('');
  };

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      handleClose();
    }
  };

  const handleSelect = (project: Project) => {
    onValueChange(project.id.toString(), project);
    setOpen(false);
  };

  const defaultTrigger = (
    <Button variant="outline" role="combobox" aria-expanded={open} className={cn('w-full justify-between', className)}>
      {selectedProject ? selectedProject.name : placeholder}
      <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
    </Button>
  );

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>{trigger || defaultTrigger}</PopoverTrigger>
      <PopoverContent className="flex max-h-[400px] w-56 flex-col p-0" align="start">
        <Command shouldFilter={false} className="flex flex-col overflow-hidden">
          <CommandInput placeholder="Search project..." value={query} onValueChange={setQuery} />
          <CommandList className="min-h-0 flex-1 overflow-y-auto" onWheel={(e) => e.stopPropagation()}>
            {projects.length === 0 ? (
              <div className="text-muted-foreground py-6 text-center text-sm">
                {isFetching ? 'Searching...' : query === '' ? 'Start typing to search projects' : 'No projects found.'}
              </div>
            ) : (
              <CommandGroup>
                {projects.map((project: Project) => (
                  <CommandItem
                    key={`project-select-${project.id}`}
                    value={project.id.toString()}
                    onSelect={() => handleSelect(project)}
                    className="truncate"
                  >
                    {project.name}
                    <CheckIcon className={cn('ml-auto', value === project.id.toString() ? 'opacity-100' : 'opacity-0')} />
                  </CommandItem>
                ))}
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
