import { registerIcons } from '@forjedio/inertia-table-react';
import { CrownIcon, CopyIcon, SignpostIcon, DatabaseIcon } from 'lucide-react';

registerIcons({
  crown: CrownIcon,
  copy: CopyIcon,
  signpost: SignpostIcon,
  database: DatabaseIcon,
} as unknown as Parameters<typeof registerIcons>[0]);
