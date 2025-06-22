import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { CommandIcon, SearchIcon } from 'lucide-react';
import CreateServer from '@/pages/servers/components/create-server';
import ProjectForm from '@/pages/projects/components/project-form';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/react';

type SearchResult = {
  id: number;
  parent_id?: number;
  label: string;
  type: 'server' | 'project' | 'site';
};

export default function AppCommand() {
  const [open, setOpen] = useState(false);
  const [openServer, setOpenServer] = useState(false);
  const [openProject, setOpenProject] = useState(false);
  const [queryText, setQueryText] = useState('');
  const [selected, setSelected] = useState<string>('create-server');

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

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (!open) {
      setOpenServer(false);
      setOpenProject(false);
    }
  };

  const query = useQuery<SearchResult[]>({
    queryKey: ['search'],
    queryFn: async () => {
      const response = await axios.get(route('search', { query: queryText }));
      return response.data.data;
    },
    retry: false,
    enabled: false,
    refetchInterval: false,
    refetchIntervalInBackground: false,
  });

  useEffect(() => {
    if (query.data && query.data.length > 0) {
      setSelected(`result-0`);
    } else {
      setSelected('create-server');
    }
  }, [query.data]);

  useEffect(() => {
    if (queryText !== '' && queryText.length >= 3) {
      const timeoutId = setTimeout(() => {
        query.refetch();
      }, 300);

      return () => clearTimeout(timeoutId);
    }
  }, [queryText]);

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
      <CommandDialog open={open} onOpenChange={handleOpenChange} shouldFilter={false} value={selected}>
        <CommandInput placeholder="Type a command or search..." onValueChange={setQueryText} />
        <CommandList>
          <CommandEmpty>No results found.</CommandEmpty>
          {query.isFetching && <p className="text-muted-foreground p-4 text-sm">Searching...</p>}
          {query.data && query.data?.length > 0 && (
            <CommandGroup heading="Search Results">
              {query.data.map((result, index) => (
                <CommandItem
                  key={`search-result-${result.id}`}
                  className="flex items-center justify-between"
                  value={`result-${index}`}
                  onSelect={() => {
                    if (result.type === 'server') {
                      router.post(route('servers.switch', { server: result.id }));
                    } else if (result.type === 'project') {
                      router.patch(
                        route('projects.switch', {
                          project: result.id,
                          currentPath: window.location.pathname,
                        }),
                      );
                    } else if (result.type === 'site') {
                      router.post(route('sites.switch', { server: result.parent_id, site: result.id }));
                    }
                    setOpen(false);
                  }}
                >
                  {result.label}
                  <Badge variant="outline">{result.type}</Badge>
                </CommandItem>
              ))}
            </CommandGroup>
          )}
          <CommandGroup heading="Commands">
            <CreateServer defaultOpen={openServer} onOpenChange={setOpenServer}>
              <CommandItem value="create-server" key="cmd-create-server" onSelect={() => setOpenServer(true)}>
                Create server
              </CommandItem>
            </CreateServer>
            <ProjectForm defaultOpen={openProject} onOpenChange={setOpenProject}>
              <CommandItem value="create-project" key="cmd-create-project" onSelect={() => setOpenProject(true)}>
                Create project
              </CommandItem>
            </ProjectForm>
          </CommandGroup>
        </CommandList>
      </CommandDialog>
    </div>
  );
}
