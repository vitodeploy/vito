import { User } from '@/types/user';
import { useEffect, useState } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import axios from 'axios';

export default function UserSelect({ onChange }: { onChange: (selectedUser: User) => void }) {
  const [query, setQuery] = useState('');
  const [users, setUsers] = useState<User[]>([]);
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState<string>();

  const fetchUsers = async () => {
    const response = await axios.get(route('users.json', { query: query }));

    if (response.status === 200) {
      setUsers(response.data as User[]);
      return;
    }

    setUsers([]);
  };

  useEffect(() => {
    fetchUsers();
  }, [query]);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between">
          {value ? users.find((user) => user.id === parseInt(value))?.name : 'Select user...'}
          <ChevronsUpDownIcon className="opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput placeholder="Search user..." value={query} onValueChange={setQuery} />
          <CommandList>
            <CommandEmpty>{query === '' ? 'Start typing to load results' : 'No results found.'}</CommandEmpty>
            <CommandGroup>
              {users.map((user) => (
                <CommandItem
                  key={`user-select-${user.id}`}
                  value={user.id.toString()}
                  onSelect={(currentValue) => {
                    setValue(currentValue === value ? '' : currentValue);
                    setOpen(false);
                    onChange(users.find((u) => u.id.toString() === currentValue) as User);
                  }}
                  className="truncate"
                >
                  {user.name} ({user.email})
                  <CheckIcon className={cn('ml-auto', value && parseInt(value) === user.id ? 'opacity-100' : 'opacity-0')} />
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
