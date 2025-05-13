import { type SharedData } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

export function ProjectSwitch() {
  const page = usePage<SharedData>();
  const { auth } = page.props;
  const [selectedProject, setSelectedProject] = useState(auth.currentProject?.id?.toString() ?? '');
  const initials = useInitials();
  const form = useForm();

  const handleProjectChange = (projectId: string) => {
    const selectedProject = auth.projects.find((project) => project.id.toString() === projectId);
    if (selectedProject) {
      setSelectedProject(selectedProject.id.toString());
      form.post(route('projects.switch', { project: projectId }));
    }
  };

  return (
    <div className="flex items-center">
      <Link href={route('servers')}>
        <Button variant="ghost" className="px-2">
          <Avatar className="size-6 rounded-sm">
            <AvatarFallback className="rounded-sm">{initials(auth.currentProject?.name ?? '')}</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">{auth.currentProject?.name}</span>
        </Button>
      </Link>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="px-1!">
            <ChevronsUpDownIcon size={5} />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className="w-56" align="start">
          {auth.projects.map((project) => (
            <DropdownMenuCheckboxItem
              key={project.id.toString()}
              checked={selectedProject === project.id.toString()}
              onCheckedChange={() => handleProjectChange(project.id.toString())}
            >
              {project.name}
            </DropdownMenuCheckboxItem>
          ))}
          <DropdownMenuSeparator />
          <DropdownMenuItem className="gap-0">
            <PlusIcon size={5} />
            <span className="ml-2">Create new project</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
