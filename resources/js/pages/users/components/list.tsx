import { ColumnDef } from '@tanstack/react-table';
import type { User } from '@/types/user';
import { DataTable } from '@/components/data-table';
import { usePage } from '@inertiajs/react';
import UserActions from '@/pages/users/components/actions';
import DateTime from '@/components/date-time';

const columns: ColumnDef<User>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'email',
    header: 'Email',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    enableSorting: true,
    cell: ({ row }) => <DateTime date={row.original.created_at} />,
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => (
      <div className="flex items-center justify-end">
        <UserActions user={row.original} />
      </div>
    ),
  },
];

type Page = {
  users: {
    data: User[];
  };
};

export default function UsersList() {
  const page = usePage<Page>();

  return <DataTable columns={columns} data={page.props.users.data} />;
}
