import { ColumnDef } from '@tanstack/react-table';
import { DynamicColumnDef } from '@/types/dynamic-table';
import { renderCellDisplays } from './cell-renderers';
import { getCellComponent } from './component-registry';
import React from 'react';

type Row = Record<string, unknown>;

export function buildDynamicColumns<TData extends Row>(
  columnDefs: DynamicColumnDef[],
  actionsRenderer?: (row: TData) => React.ReactNode,
): ColumnDef<TData, unknown>[] {
  const visibleColumns = columnDefs.filter((col) => !col.hidden);

  const columns: ColumnDef<TData, unknown>[] = visibleColumns.map((col) => {
    const hasComponent = col.displays.length === 1 && col.displays[0].type === 'component';

    return {
      id: col.sort_key,
      accessorFn: (row: TData) => row[col.name],
      header: col.header,
      enableSorting: col.sortable,
      enableColumnFilter: true,
      cell: ({ row }) => {
        if (hasComponent) {
          const componentName = (col.displays[0] as { type: 'component'; component: string }).component;
          const Component = getCellComponent(componentName);

          if (Component) {
            return React.createElement(Component, { row: row.original as Row });
          }

          return '-';
        }

        return renderCellDisplays(row.original as Row, col.name, col.displays);
      },
    };
  });

  if (actionsRenderer) {
    columns.push({
      id: 'actions',
      enableSorting: false,
      enableColumnFilter: false,
      cell: ({ row }) => actionsRenderer(row.original),
    });
  }

  return columns;
}
