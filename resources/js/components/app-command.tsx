import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { CommandIcon, SearchIcon } from 'lucide-react';
import CreateServer from '@/pages/servers/components/create-server';

export default function AppCommand() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen((open) => !open);
      }
    };

    document.addEventListener('keydown', down);
    return () => document.removeEventListener('keydown', down);
  }, []);

  return (
    <div>
      <Button className="hidden px-1! lg:flex" variant="outline" size="sm" onClick={() => setOpen(true)}>
        <span className="sr-only">Open command menu</span>
        <SearchIcon className="ml-1 size-3" />
        Search...
        <span className="bg-accent flex h-6 items-center justify-center rounded-sm border px-2 text-xs">
          <CommandIcon className="mr-1 size-3" /> K
        </span>
      </Button>
      <Button className="lg:hidden" variant="outline" size="sm" onClick={() => setOpen(true)}>
        <CommandIcon className="mr-1 size-3" /> K
      </Button>
      <CommandDialog open={open} onOpenChange={setOpen}>
        <CommandInput placeholder="Type a command or search..." />
        <CommandList>
          <CommandEmpty>No results found.</CommandEmpty>
          <CommandGroup heading="Suggestions">
            <CreateServer>
              <CommandItem>Create server</CommandItem>
            </CreateServer>
            <CommandItem>Create project</CommandItem>
          </CommandGroup>
        </CommandList>
      </CommandDialog>
    </div>
  );
}
