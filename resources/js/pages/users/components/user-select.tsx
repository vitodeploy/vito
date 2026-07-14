import { User } from '@/types/user';
import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import axios from 'axios';

export default function UserSelect({ value, onValueChange }: { value: string; onValueChange: (selectedUser: User) => void }) {
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const [selected, setSelected] = useState<string>(value);

  useEffect(() => {
    setSelected(value);
  }, [value]);

  const {
    data: users = [],
    isFetching,
    refetch,
  } = useQuery<User[]>({
    queryKey: ['users', query],
    queryFn: async () => {
      const response = await axios.get(route('users.json', { query: query }));
      return response.data;
    },
    enabled: false,
  });

  const onOpenChange = (open: boolean) => {
    setOpen(open);
    if (open) {
      refetch();
    }
  };

  useEffect(() => {
    if (open && query !== '') {
      const timeoutId = setTimeout(() => {
        refetch();
      }, 300); // Debounce search

      return () => clearTimeout(timeoutId);
    }
  }, [query, open, refetch]);

  const selectedUser = users.find((user) => user.id === parseInt(selected));

  return (
    <Popover open={open} onOpenChange={onOpenChange}>
      <PopoverTrigger asChild>
        <Button variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between">
          {selectedUser ? selectedUser.name : 'Select user...'}
          <ChevronsUpDownIcon className="opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-(--radix-popover-trigger-width) p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput placeholder="Search user..." value={query} onValueChange={setQuery} />
          <CommandList>
            <CommandEmpty>{isFetching ? 'Searching...' : query === '' ? 'Start typing to search users' : 'No users found.'}</CommandEmpty>
            <CommandGroup>
              {users.map((user) => (
                <CommandItem
                  key={`user-select-${user.id}`}
                  value={user.id.toString()}
                  onSelect={(currentValue) => {
                    const newSelected = currentValue === selected ? '' : currentValue;
                    setSelected(newSelected);
                    setOpen(false);
                    if (newSelected) {
                      const user = users.find((s) => s.id.toString() === newSelected);
                      if (user) {
                        onValueChange(user);
                      }
                    }
                  }}
                  className="truncate"
                >
                  {user.name} ({user.email})
                  <CheckIcon className={cn('ml-auto', selected && parseInt(selected) === user.id ? 'opacity-100' : 'opacity-0')} />
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
