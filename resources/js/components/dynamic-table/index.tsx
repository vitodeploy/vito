import React, { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { PaginatedData } from '@/types';
import { buildDynamicColumns } from './build-columns';

type Row = Record<string, unknown>;

interface DynamicTableProps<TData extends Row> {
  tableData: DynamicTableData<TData>;
  actions?: (row: TData) => React.ReactNode;
  className?: string;
  modal?: boolean;
  onRowClick?: (row: TData) => void;
  onPageChange?: (page: number) => void;
  isFetching?: boolean;
  isLoading?: boolean;
}

export function DynamicTable<TData extends Row>({
  tableData,
  actions,
  className,
  modal,
  onRowClick,
  onPageChange,
  isFetching,
  isLoading,
}: DynamicTableProps<TData>) {
  const columns = useMemo(() => buildDynamicColumns<TData>(tableData.columns, actions), [tableData.columns, actions]);

  return (
    <DataTable
      columns={columns}
      paginatedData={tableData.data as PaginatedData<TData>}
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
