import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Check, ChevronsUpDown, LoaderCircle, Plus, RefreshCw } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { useConfigs } from '@/stores/bootstrap-store';

type IsolatedUserSelectProps = {
  serverId: number;
  value: string;
  onValueChange: (value: string) => void;
  onSearchChange?: (value: string) => void;
};

const USERNAME_REGEX = /^[a-z_][a-z0-9_-]*[a-z0-9]$/;

type IsolatedUserOption = {
  user: string;
  sites_count: number;
};

export default function IsolatedUserSelect({ serverId, value, onValueChange, onSearchChange }: IsolatedUserSelectProps) {
  const configs = useConfigs()!;
  const reservedNames = configs.site.reserved_user_names ?? [];
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const query = useQuery<IsolatedUserOption[]>({
    queryKey: ['isolated-users', serverId],
    queryFn: async () => {
      return (await axios.get(route('sites.isolated-users', { server: serverId }))).data;
    },
    enabled: !!serverId,
  });

  const existingUsers = query.data ?? [];
  const trimmed = search.trim();
  const filtered = trimmed === '' ? existingUsers : existingUsers.filter((u) => u.user.toLowerCase().includes(trimmed.toLowerCase()));
  const exactMatch = existingUsers.some((u) => u.user === trimmed);
  const validUsername = USERNAME_REGEX.test(trimmed) && trimmed.length >= 3 && trimmed.length <= 32;
  const isReserved = trimmed.length > 0 && reservedNames.includes(trimmed);
  const showCreate = trimmed.length > 0 && !exactMatch && validUsername && !isReserved;

  const triggerLabel = value
    ? value
    : query.isError
      ? 'Failed to load users'
      : existingUsers.length === 0 && !query.isFetching
        ? 'Type a name to create a user'
        : 'Select or create user';

  const handleOpenChange = (next: boolean) => {
    setOpen(next);
    if (!next) {
      setSearch('');
    }
  };

  const handleSearchChange = (next: string) => {
    setSearch(next);
    if (next.length > 0) {
      onSearchChange?.(next);
    }
  };

  const pick = (next: string) => {
    onValueChange(next);
    setOpen(false);
    setSearch('');
  };

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>
        <Button
          id="user"
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={!serverId}
          className="w-full justify-between font-normal"
        >
          <span className={cn(!value && 'text-muted-foreground', 'flex items-center gap-2')}>
            {query.isFetching && !value && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
            {triggerLabel}
          </span>
          <ChevronsUpDown className="opacity-50" aria-hidden="true" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput placeholder="Search or type new user..." value={search} onValueChange={handleSearchChange} />
          <CommandList>
            {query.isError && (
              <CommandGroup>
                <CommandItem
                  value="__retry__"
                  onSelect={() => {
                    query.refetch();
                  }}
                  aria-label="Retry loading isolated users"
                >
                  <RefreshCw aria-hidden="true" />
                  <span>Failed to load. Retry</span>
                </CommandItem>
              </CommandGroup>
            )}

            {!query.isError && filtered.length === 0 && !showCreate && (
              <CommandEmpty>
                {query.isFetching
                  ? 'Loading users...'
                  : isReserved
                    ? `'${trimmed}' is a reserved system name and can't be used.`
                    : existingUsers.length === 0
                      ? 'No isolated users yet — type a valid name to create one.'
                      : 'No matches. Type a valid username to create one.'}
              </CommandEmpty>
            )}

            {filtered.length > 0 && (
              <CommandGroup heading="Existing users">
                {filtered.map((u) => (
                  <CommandItem key={`isolated-${u.user}`} value={u.user} onSelect={() => pick(u.user)}>
                    <span className="flex flex-1 items-center gap-2">
                      <span>{u.user}</span>
                      <Badge variant="outline">
                        {u.sites_count} {u.sites_count === 1 ? 'site' : 'sites'}
                      </Badge>
                    </span>
                    <Check className={cn('ml-auto', value === u.user ? 'opacity-100' : 'opacity-0')} aria-hidden="true" />
                  </CommandItem>
                ))}
              </CommandGroup>
            )}

            {showCreate && (
              <CommandGroup heading="Create new">
                <CommandItem value={`__create__:${trimmed}`} onSelect={() => pick(trimmed)} aria-label={`Create new isolated user ${trimmed}`}>
                  <Plus aria-hidden="true" />
                  <span>
                    Create new <span className="font-medium">{trimmed}</span>
                  </span>
                </CommandItem>
              </CommandGroup>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
