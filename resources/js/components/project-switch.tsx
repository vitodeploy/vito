import { type SharedData } from '@/types';
import { type Project } from '@/types/project';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import ProjectForm from '@/pages/projects/components/project-form';
import { ProjectSelect } from '@/components/project-select';
import { CommandGroup, CommandItem } from '@/components/ui/command';

export function ProjectSwitch() {
  const page = usePage<SharedData>();
  const { auth } = page.props;
  const [open, setOpen] = useState(false);
  const [projectFormOpen, setProjectFormOpen] = useState(false);
  const [selected, setSelected] = useState<string>(auth.currentProject?.id?.toString() ?? '');
  const initials = useInitials();
  const form = useForm();

  useEffect(() => {
    setSelected(auth.currentProject?.id?.toString() ?? '');
  }, [auth.currentProject?.id]);

  const handleProjectChange = (value: string, project: Project) => {
    setSelected(value);
    setOpen(false);
    form.patch(route('projects.switch', { project: project.id, currentPath: window.location.pathname }));
  };

  const footer = (
    <CommandGroup>
      <ProjectForm defaultOpen={projectFormOpen} onOpenChange={setProjectFormOpen}>
        <CommandItem
          value="create-project"
          onSelect={() => {
            setProjectFormOpen(true);
          }}
          className="gap-0"
        >
          <div className="flex items-center">
            <PlusIcon size={5} />
            <span className="ml-2">Create new project</span>
          </div>
        </CommandItem>
      </ProjectForm>
    </CommandGroup>
  );

  const trigger = (
    <Button variant="ghost" className="px-1!">
      <Avatar className="size-6 rounded-sm">
        <AvatarFallback className="rounded-sm">{initials(auth.currentProject?.name ?? '')}</AvatarFallback>
      </Avatar>
      <span className="hidden lg:flex">{auth.currentProject?.name}</span>
      <ChevronsUpDownIcon size={5} />
    </Button>
  );

  return (
    <div className="flex items-center">
      <ProjectSelect value={selected} onValueChange={handleProjectChange} trigger={trigger} open={open} onOpenChange={setOpen} footer={footer} />
    </div>
  );
}
