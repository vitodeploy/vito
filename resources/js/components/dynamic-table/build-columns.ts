import { ColumnDef } from '@tanstack/react-table';
import { DynamicColumnDef, Row } from '@/types/dynamic-table';
import { renderCellDisplays } from './cell-renderers';
import { getCellComponent } from './component-registry';
import React from 'react';

export function buildDynamicColumns(columnDefs: DynamicColumnDef[], actionsRenderer?: (row: Row) => React.ReactNode): ColumnDef<Row, unknown>[] {
  const visibleColumns = columnDefs.filter((col) => !col.hidden);

  const columns: ColumnDef<Row, unknown>[] = visibleColumns.map((col) => {
    const hasComponent = col.displays.length === 1 && col.displays[0].type === 'component';

    return {
      id: col.sort_key,
      accessorFn: (row: Row) => row[col.name],
      header: col.header,
      enableSorting: col.sortable,
      enableColumnFilter: true,
      cell: ({ row }) => {
        if (hasComponent) {
          const componentName = (col.displays[0] as { type: 'component'; component: string }).component;
          const Component = getCellComponent(componentName);

          if (Component) {
            return React.createElement(Component, { row: row.original });
          }

          return '-';
        }

        return renderCellDisplays(row.original, col.name, col.displays);
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
