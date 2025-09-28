import { DataTable } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import RemoveUser from '@/pages/projects/components/remove-user';
import Invite from '@/pages/projects/components/invite';
import { Project } from '@/types/project';
import { ProjectUser } from '@/types/project-user';
import { ColumnDef } from '@tanstack/react-table';
import { TrashIcon } from 'lucide-react';
import { ReactNode, useState } from 'react';

const columns: ColumnDef<ProjectUser>[] = [
  {
    accessorKey: 'email',
    header: 'Email',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    id: 'role',
    header: 'Role',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.role}</Badge>;
    },
  },
  {
    id: 'status',
    header: 'Status',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.type === 'user' ? 'registered' : 'invited'}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <RemoveUser projectId={row.original.project_id} user={row.original}>
            <Button variant="outline" size="sm" className="size-7">
              <TrashIcon className="size-3" />
            </Button>
          </RemoveUser>
        </div>
      );
    },
  },
];

export default function Users({ project, children }: { project: Project; children?: ReactNode }) {
  const [open, setOpen] = useState(false);
  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-2xl">
        <SheetHeader>
          <SheetTitle>Project users</SheetTitle>
          <SheetDescription className="sr-only">Here you can manage project users</SheetDescription>
        </SheetHeader>
        <div className="p-4">
          <DataTable columns={columns} data={[...(project.owner ? [project.owner] : []), ...(project.users || [])]} />
        </div>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <SheetClose asChild>
              <Button variant="outline">Close</Button>
            </SheetClose>
            <Invite project={project}>
              <Button>Invite</Button>
            </Invite>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
