import { PaginationLinks, PaginationMeta } from '@/types';

export type CellDisplay =
  | { type: 'text'; key?: string }
  | { type: 'badge'; key?: string; color_field?: string; variant?: string; tooltip_key?: string }
  | { type: 'date'; key?: string }
  | { type: 'link'; route: string; params: Record<string, string>; key?: string }
  | { type: 'copyable'; key?: string }
  | { type: 'icon'; key: string }
  | { type: 'component'; component: string };

export interface DynamicColumnDef {
  name: string;
  header: string;
  sortable: boolean;
  sort_key: string;
  hidden: boolean;
  displays: CellDisplay[];
}

export type Row = Record<string, unknown> & { id: number };

export interface DynamicTableData {
  columns: DynamicColumnDef[];
  data: Row[];
  links: PaginationLinks;
  meta: PaginationMeta;
  searchable: boolean;
}
