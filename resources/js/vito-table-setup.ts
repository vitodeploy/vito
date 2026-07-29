import { registerCellComponent, registerIcons } from '@forjedio/inertia-table-react';
import { CrownIcon, CopyIcon, SignpostIcon, DatabaseIcon } from 'lucide-react';
import { DatabaseUserDatabases } from '@/components/database-user-databases';
import { ServiceNetworkedBadge } from '@/pages/services/components/networked-badge';

registerIcons({
  crown: CrownIcon,
  copy: CopyIcon,
  signpost: SignpostIcon,
  database: DatabaseIcon,
} as unknown as Parameters<typeof registerIcons>[0]);

registerCellComponent('DatabaseUserDatabases', DatabaseUserDatabases);
registerCellComponent('ServiceNetworkedBadge', ServiceNetworkedBadge);
