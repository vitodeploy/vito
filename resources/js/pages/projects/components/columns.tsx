'use client';

import { ColumnDef } from '@tanstack/react-table';
import type { Project } from '@/types/project';
import ProjectActions from '@/pages/projects/components/actions';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import { Badge } from '@/components/ui/badge';

const CurrentProject = ({ project }: { project: Project }) => {
  const page = usePage<SharedData>();
  return <>{project.id === page.props.auth.currentProject?.id && <Badge variant="default">current</Badge>}</>;
};

export const columns: ColumnDef<Project>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        <div className="flex items-center space-x-1">
          <span>{row.original.name}</span> <CurrentProject project={row.original} />
        </div>
      );
    },
  },
  {
    accessorKey: 'created_at_by_timezone',
    header: 'Created at',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <ProjectActions project={row.original} />
        </div>
      );
    },
  },
];
