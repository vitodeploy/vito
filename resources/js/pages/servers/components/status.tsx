import { Server } from '@/types/server';
import { Badge } from '@/components/ui/badge';

export default function ServerStatus({ server }: { server: Server }) {
  return <Badge variant={server.status_color}>{server.status}</Badge>;
}
