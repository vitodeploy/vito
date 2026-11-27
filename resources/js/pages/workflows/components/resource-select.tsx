import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { CheckIcon, ChevronsUpDownIcon, VariableIcon } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { WorkflowVariable } from '@/types/workflow-action';

type ResourceOption = { value: string; label: string; description: string | null };

const isId = (value: string | undefined): value is string => !!value && /^\d+$/.test(value);

export default function ResourceSelect({
  id,
  resource,
  parent,
  value,
  label,
  variables,
  onChange,
}: {
  id: string;
  resource: string;
  parent?: string;
  value: string;
  label: string;
  variables: WorkflowVariable[];
  onChange: (value: string) => void;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');

  useEffect(() => {
    const timeoutId = setTimeout(() => setDebouncedQuery(query), 300);
    return () => clearTimeout(timeoutId);
  }, [query]);

  const params = { query: debouncedQuery, parent: isId(parent) ? parent : undefined, selected: isId(value) ? value : undefined };
  const options = useQuery<ResourceOption[]>({
    queryKey: ['workflow-resources', resource, params],
    queryFn: async () => (await axios.get(`/workflows/resources/${encodeURIComponent(resource)}`, { params })).data,
    enabled: open || isId(value),
    staleTime: 1000 * 30,
  });

  const selected = options.data?.find((option) => option.value === value);
  const needle = query.trim().toLowerCase();
  const matchingVariables = variables.filter((variable) => variable.key.toLowerCase().includes(needle));
  const isVariable = /^\{\{?\w+\}?\}$/.test(value);

  const select = (next: string) => {
    onChange(next === value ? '' : next);
    setOpen(false);
    setQuery('');
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button id={id} type="button" variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between font-normal">
          <span className={cn('truncate', !value && 'text-muted-foreground', isVariable && 'font-mono')}>
            {selected ? selected.label : value ? (isId(value) ? `#${value}` : value) : `Select ${label.toLowerCase()}...`}
          </span>
          <ChevronsUpDownIcon className="opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="flex max-h-[360px] w-(--radix-popover-trigger-width) min-w-64 flex-col p-0" align="start">
        <Command shouldFilter={false} className="flex flex-col overflow-hidden">
          <CommandInput placeholder={`Search ${label.toLowerCase()} or type a value...`} value={query} onValueChange={setQuery} />
          <CommandList className="min-h-0 flex-1 overflow-y-auto" onWheel={(e) => e.stopPropagation()}>
            {matchingVariables.length > 0 && (
              <CommandGroup heading="Previous action outputs">
                {matchingVariables.map((variable) => (
                  <CommandItem key={`var-${variable.key}`} value={`{${variable.key}}`} onSelect={select}>
                    <VariableIcon />
                    <div className="flex min-w-0 flex-col">
                      <span className="font-mono">{`{${variable.key}}`}</span>
                      {variable.description && <span className="text-muted-foreground truncate text-xs">{variable.description}</span>}
                    </div>
                    <CheckIcon className={cn('ml-auto', value === `{${variable.key}}` ? 'opacity-100' : 'opacity-0')} />
                  </CommandItem>
                ))}
              </CommandGroup>
            )}
            <CommandGroup heading={label}>
              {options.isFetching && !options.data && <div className="text-muted-foreground py-4 text-center text-sm">Loading...</div>}
              {options.isError && <div className="text-destructive py-4 text-center text-sm">Failed to load {label.toLowerCase()}</div>}
              {options.isSuccess && options.data.length === 0 && <div className="text-muted-foreground py-4 text-center text-sm">Nothing found</div>}
              {options.data?.map((option) => (
                <CommandItem key={`opt-${option.value}`} value={option.value} onSelect={select}>
                  <div className="flex min-w-0 flex-col">
                    <span className="truncate">{option.label}</span>
                    {option.description && <span className="text-muted-foreground truncate text-xs">{option.description}</span>}
                  </div>
                  <CheckIcon className={cn('ml-auto', value === option.value ? 'opacity-100' : 'opacity-0')} />
                </CommandItem>
              ))}
            </CommandGroup>
            {query.trim() !== '' && (
              <CommandGroup heading="Custom">
                <CommandItem value={`custom-${query}`} onSelect={() => select(query.trim())}>
                  Use <span className="font-mono">{query.trim()}</span>
                </CommandItem>
              </CommandGroup>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
