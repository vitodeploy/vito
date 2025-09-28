import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ColumnDef } from '@tanstack/react-table';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { ProjectUser } from '@/types/project-user';
import { FormEvent, ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

function Reject({ invitation, children }: { invitation: ProjectUser; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(`/settings/projects/${invitation.project_id}/leave`, {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {invitation.project_name}</DialogTitle>
          <DialogDescription className="sr-only">Reject joining {invitation.project_name}</DialogDescription>
        </DialogHeader>

        <p className="p-4">Are you sure you want to reject joining this project?</p>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button onClick={submit} variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Reject
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export const columns: ColumnDef<ProjectUser>[] = [
  {
    accessorKey: 'project_name',
    header: 'Project name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'role',
    header: 'Role',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.role}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem
                onSelect={(e) => {
                  e.preventDefault();
                  window.location.href = `/settings/projects/${row.original.project_id}/invitations/accept`;
                }}
              >
                Accept & Join
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <Reject invitation={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
                  Reject
                </DropdownMenuItem>
              </Reject>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
