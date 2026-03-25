import { ReactNode } from 'react';
import { useTable, type InertiaTableData, type InertiaTableProps, type Row, type CellRenderProps } from '@forjedio/inertia-table-react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  ChevronsUpDownIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  LoaderCircleIcon,
} from 'lucide-react';

interface VitoTableProps extends Omit<InertiaTableProps, 'tableData'> {
  tableData: InertiaTableData;
  children?: ReactNode;
}

function vitoCellRenderer({ row, value, column, displays, defaultRender }: CellRenderProps & { defaultRender: () => ReactNode }): ReactNode {
  if (displays.length === 1 && displays[0].type === 'badge') {
    const display = displays[0];
    const color = display.color_field ? (row[display.color_field] as string) : display.variant;
    return <Badge variant={(color ?? 'default') as 'default'}>{String(value ?? '')}</Badge>;
  }
  return defaultRender();
}

export function VitoTable({ tableData, children, modal, isFetching, ...props }: VitoTableProps) {
  const { columns, classNames, searchTerm, onSearch, sortBy, sortDir, onSort, getSortState, onPageChange, isProcessing } = useTable({
    tableData,
    modal,
    isFetching,
    renderCell: vitoCellRenderer,
    ...props,
  });

  const processing = isProcessing || isFetching;

  return (
    <div>
      {tableData.searchable && (
        <div className="mb-4 flex items-center gap-2">
          <Input placeholder="Search..." className="max-w-sm" value={searchTerm} onChange={(e) => onSearch(e.target.value)} />
          {processing && <LoaderCircleIcon className="text-muted-foreground animate-spin" />}
        </div>
      )}

      <div className={cn('relative overflow-hidden rounded-md border shadow-xs', modal && 'border-none shadow-none')}>
        <Table>
          <TableHeader>
            <TableRow>
              {tableData.columns.filter((c) => !c.hidden).map((colDef) => {
                const sortState = getSortState(colDef.sort_key);

                return (
                  <TableHead key={colDef.name} className={colDef.fit ? 'w-0' : undefined}>
                    {colDef.sortable ? (
                      <button type="button" className="flex cursor-pointer items-center gap-2" onClick={() => onSort(colDef.sort_key)}>
                        {colDef.header}
                        {sortState.active ? (
                          sortState.direction === 'asc' ? (
                            <ChevronUpIcon className="text-muted-foreground inline-block h-4 w-4" />
                          ) : (
                            <ChevronDownIcon className="text-muted-foreground inline-block h-4 w-4" />
                          )
                        ) : (
                          <ChevronsUpDownIcon className="text-muted-foreground inline-block h-4 w-4" />
                        )}
                      </button>
                    ) : (
                      colDef.header
                    )}
                  </TableHead>
                );
              })}
            </TableRow>
          </TableHeader>
          <TableBody>
            {tableData.data.length > 0 ? (
              tableData.data.map((row, rowIndex) => (
                <TableRow
                  key={row.id}
                  onClick={() => props.onRowClick?.(row)}
                  className={cn(props.onRowClick && 'hover:bg-muted/50 cursor-pointer', props.rowClassName?.(row, rowIndex))}
                >
                  {columns.map((col) => (
                    <TableCell key={col.id}>{col.renderCell(row, rowIndex)}</TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center">
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        {tableData.meta && (
          <div className="flex items-center justify-between border-t px-4 py-3">
            <div className="text-muted-foreground flex items-center text-sm">
              {tableData.meta.from && tableData.meta.to && (
                <span>
                  Showing {tableData.meta.from} to {tableData.meta.to}
                  {tableData.meta.total && ` of ${tableData.meta.total}`} results
                </span>
              )}
            </div>

            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => tableData.links.first && onPageChange(1)}
                disabled={!tableData.links.first || processing}
              >
                <ChevronsLeft className="h-4 w-4" />
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange(tableData.meta.current_page - 1)}
                disabled={!tableData.links.prev || processing}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>

              <div className="flex items-center text-sm font-medium">
                Page {tableData.meta.current_page}
                {tableData.meta.last_page && ` of ${tableData.meta.last_page}`}
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange(tableData.meta.current_page + 1)}
                disabled={!tableData.links.next || processing}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() => tableData.meta.last_page && onPageChange(tableData.meta.last_page)}
                disabled={!tableData.links.last || processing}
              >
                <ChevronsRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}
      </div>

      {children}
    </div>
  );
}
