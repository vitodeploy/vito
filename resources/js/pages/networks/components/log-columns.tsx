import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { columns } from '@/pages/server-logs/components/columns';
import { ServerLog } from '@/types/server-log';
import { truncate } from '@/lib/utils';

const SERVER_NAME_LIMIT = 30;

const serverColumn: ColumnDef<ServerLog> = {
  accessorKey: 'server_name',
  header: 'Server',
  cell: ({ row }) => {
    const name = row.original.server_name ?? `#${row.original.server_id}`;

    return (
      <Link href={route('servers.show', { server: row.original.server_id })} className="text-foreground" prefetch>
        <span title={name}>{truncate(name, SERVER_NAME_LIMIT)}</span>
      </Link>
    );
  },
};

export const networkLogColumns: ColumnDef<ServerLog>[] = [serverColumn, ...columns];
