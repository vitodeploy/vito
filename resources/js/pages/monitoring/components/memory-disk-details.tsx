import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { kbToGb, mbToGb } from '@/lib/utils';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';

export default function MemoryDiskDetails({ server, filter }: { server: Server; filter?: MetricsFilter }) {
  const metrics = useMetrics(server, filter);
  const history = metrics.data?.history ?? [];
  const latest = history.length > 0 ? history[history.length - 1] : null;

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Memory details</CardTitle>
          <CardDescription className="sr-only">Memory details</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between border-b p-4">
            <span>Used</span>
            <span>{latest ? `${kbToGb(latest.memory_used)} GB` : 'N/A'}</span>
          </div>
          <div className="flex items-center justify-between border-b p-4">
            <span>Free</span>
            <span>{latest ? `${kbToGb(latest.memory_free)} GB` : 'N/A'}</span>
          </div>
          <div className="flex items-center justify-between p-4">
            <span>Total</span>
            <span>{latest ? `${kbToGb(latest.memory_total)} GB` : 'N/A'}</span>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Disk details</CardTitle>
          <CardDescription className="sr-only">Disk details</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between border-b p-4">
            <span>Used</span>
            <span>{latest ? `${mbToGb(latest.disk_used)} GB` : 'N/A'}</span>
          </div>
          <div className="flex items-center justify-between border-b p-4">
            <span>Free</span>
            <span>{latest ? `${mbToGb(latest.disk_free)} GB` : 'N/A'}</span>
          </div>
          <div className="flex items-center justify-between p-4">
            <span>Total</span>
            <span>{latest ? `${mbToGb(latest.disk_total)} GB` : 'N/A'}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
