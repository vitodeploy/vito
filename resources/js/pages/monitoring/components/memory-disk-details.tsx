import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { DataTable } from '@/components/data-table';
import { ColumnDef } from '@tanstack/react-table';
import { kbToGb, mbToGb } from '@/lib/utils';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';

type DetailRow = { label: string; value: string };

export default function MemoryDiskDetails({ server, filter }: { server: Server; filter?: MetricsFilter }) {
  const metrics = useMetrics(server, filter);
  const history = metrics.data?.history ?? [];
  const latest = history.length > 0 ? history[history.length - 1] : null;

  const memoryRows: DetailRow[] = [
    { label: 'Used', value: latest ? `${kbToGb(latest.memory_used)} GB` : 'N/A' },
    { label: 'Free', value: latest ? `${kbToGb(latest.memory_free)} GB` : 'N/A' },
    { label: 'Total', value: latest ? `${kbToGb(latest.memory_total)} GB` : 'N/A' },
  ];

  const diskRows: DetailRow[] = [
    { label: 'Used', value: latest ? `${mbToGb(latest.disk_used)} GB` : 'N/A' },
    { label: 'Free', value: latest ? `${mbToGb(latest.disk_free)} GB` : 'N/A' },
    { label: 'Total', value: latest ? `${mbToGb(latest.disk_total)} GB` : 'N/A' },
  ];

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <DetailsTable title="Memory details" rows={memoryRows} />
      <DetailsTable title="Disk details" rows={diskRows} />
    </div>
  );
}

function DetailsTable({ title, rows }: { title: string; rows: DetailRow[] }) {
  const columns: ColumnDef<DetailRow>[] = [
    {
      accessorKey: 'label',
      header: title,
    },
    {
      accessorKey: 'value',
      header: '',
      cell: ({ row }) => <div className="text-right">{row.original.value}</div>,
    },
  ];

  return <DataTable columns={columns} data={rows} />;
}
