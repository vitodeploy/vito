import React, { useMemo, useRef } from 'react';
import { DataTable } from '@/components/data-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { PaginatedData } from '@/types';
import { useRealtime } from '@/hooks/use-socket-events';
import { buildDynamicColumns } from './build-columns';

interface DynamicTableProps {
  tableData: DynamicTableData;
  actions?: (row: Row) => React.ReactNode;
  className?: string;
  modal?: boolean;
  onRowClick?: (row: Row) => void;
  onPageChange?: (page: number) => void;
  isFetching?: boolean;
  isLoading?: boolean;
}

export function DynamicTable({ tableData, actions, className, modal, onRowClick, onPageChange, isFetching, isLoading }: DynamicTableProps) {
  const actionsRef = useRef(actions);
  actionsRef.current = actions;

  const stableActions = useMemo(() => {
    if (!actions) return undefined;
    return (row: Row) => actionsRef.current?.(row);
  }, [!!actions]);

  const columns = useMemo(() => buildDynamicColumns(tableData.columns, stableActions), [tableData.columns, stableActions]);

  const initialPaginatedData = useMemo<PaginatedData<Row>>(
    () => ({
      data: tableData.data,
      links: tableData.links,
      meta: tableData.meta,
    }),
    [tableData.data, tableData.links, tableData.meta],
  );

  const realtimeEvent = tableData.realtimeEvent;
  const [livePaginatedData] = useRealtime<Row>(initialPaginatedData, realtimeEvent ?? '');

  const paginatedData = realtimeEvent ? livePaginatedData : initialPaginatedData;

  return (
    <DataTable
      columns={columns}
      paginatedData={paginatedData}
      searchable={tableData.searchable}
      sortable={true}
      className={className}
      modal={modal}
      onRowClick={onRowClick}
      onPageChange={onPageChange}
      isFetching={isFetching}
      isLoading={isLoading}
    />
  );
}
