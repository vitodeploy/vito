import { Server } from '@/types/server';
import { Site } from '@/types/site';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import React, { useState } from 'react';
import { TableSkeleton } from '@/components/table-skeleton';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';

export default function Logs({ server, site }: { server: Server; site?: Site }) {
  const [currentPage, setCurrentPage] = useState(1);

  const query = useQuery({
    queryKey: ['serverLogs', server.id, site?.id, currentPage],
    queryFn: async () => {
      return (
        await axios.get(route('logs.json', { server: server.id, site: site?.id }), {
          params: { page: currentPage },
        })
      ).data;
    },
    placeholderData: (prev) => prev,
    refetchInterval: 5000,
  });

  return (
    <>
      {query.isLoading ? (
        <TableSkeleton rows={5} cells={3} />
      ) : (
        <DataTable
          columns={columns}
          paginatedData={query.data}
          onPageChange={setCurrentPage}
          isFetching={query.isFetching}
          isLoading={query.isLoading}
        />
      )}
    </>
  );
}
