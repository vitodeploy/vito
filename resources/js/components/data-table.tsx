import { ColumnDef, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, LoaderCircleIcon } from 'lucide-react';
import { router } from '@inertiajs/react';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { PaginatedData } from '@/types';
import { Input } from './ui/input';
import { useEffect, useState } from 'react';

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  paginatedData?: PaginatedData<TData>;
  data?: TData[];
  className?: string;
  modal?: boolean;
  onPageChange?: (page: number) => void;
  isFetching?: boolean;
  isLoading?: boolean;
  searchable?: boolean;
}

export function DataTable<TData, TValue>({
  columns,
  paginatedData,
  data,
  className,
  modal,
  onPageChange,
  isFetching,
  isLoading,
  searchable,
}: DataTableProps<TData, TValue>) {
  // Use paginatedData.data if available, otherwise fall back to data prop
  const tableData = paginatedData?.data || data || [];

  const table = useReactTable({
    data: tableData,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  const extraClasses = modal && 'border-none shadow-none';

  const handlePageChange = (url: string) => {
    if (onPageChange) {
      // Use custom page change handler (for axios/API calls)
      const urlObj = new URL(url);
      const page = urlObj.searchParams.get('page');
      if (page) {
        onPageChange(parseInt(page));
        return;
      }

      onPageChange(1);
    } else {
      // Use Inertia router for server-side rendered pages
      router.get(url, {}, { preserveState: true });
    }
  };

  // handle search
  const [isInitialSearch, setIsInitialSearch] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  useEffect(() => {
    const handler = setTimeout(() => {
      if (!isInitialSearch) {
        handleSearch();
      }
    }, 300);

    return () => clearTimeout(handler);
  }, [searchQuery]);
  const handleSearch = () => {
    if (paginatedData) {
      setIsSearching(true);
      const url = new URL(paginatedData.meta.path);
      if (searchQuery.length > 0) {
        url.searchParams.set('search', searchQuery);
      }
      router.get(
        url.toString(),
        {},
        {
          preserveState: true,
          onSuccess: () => {
            setIsSearching(false);
          },
        },
      );
    }
  };

  return (
    <div>
      <div className="mb-4">
        {searchable && (
          <div className="flex items-center gap-2">
            <Input
              placeholder="Search..."
              className="max-w-sm"
              onChange={(e) => {
                setIsInitialSearch(false);
                setSearchQuery(e.target.value);
              }}
            />
            {isSearching && <LoaderCircleIcon className="text-muted-foreground animate-spin" />}
          </div>
        )}
      </div>
      <div className={cn('relative overflow-hidden rounded-md border shadow-xs', className, extraClasses)}>
        {isLoading && (
          <div className="absolute top-0 right-0 left-0 h-[2px] overflow-hidden">
            <div className="animate-loading-bar bg-primary absolute inset-0 w-full" />
          </div>
        )}
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
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

        {paginatedData && (
          <div className="flex items-center justify-between border-t px-4 py-3">
            <div className="text-muted-foreground flex items-center text-sm">
              {paginatedData.meta.from && paginatedData.meta.to && (
                <span>
                  Showing {paginatedData.meta.from} to {paginatedData.meta.to}
                  {paginatedData.meta.total && ` of ${paginatedData.meta.total}`} results
                </span>
              )}
            </div>

            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => paginatedData.links.first && handlePageChange(paginatedData.links.first)}
                disabled={!paginatedData.links.first || isFetching}
              >
                <ChevronsLeft className="h-4 w-4" />
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() => paginatedData.links.prev && handlePageChange(paginatedData.links.prev)}
                disabled={!paginatedData.links.prev || isFetching}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>

              <div className="flex items-center text-sm font-medium">
                Page {paginatedData.meta.current_page}
                {paginatedData.meta.last_page && ` of ${paginatedData.meta.last_page}`}
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={() => paginatedData.links.next && handlePageChange(paginatedData.links.next)}
                disabled={!paginatedData.links.next || isFetching}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>

              <Button
                variant="outline"
                size="sm"
                onClick={() => paginatedData.links.last && handlePageChange(paginatedData.links.last)}
                disabled={!paginatedData.links.last || isFetching}
              >
                <ChevronsRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
