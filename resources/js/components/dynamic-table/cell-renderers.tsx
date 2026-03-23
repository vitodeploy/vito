import React from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import CopyableBadge from '@/components/copyable-badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { CellDisplay } from '@/types/dynamic-table';
import { iconRegistry } from './icon-registry';

type Row = Record<string, unknown>;

function resolveValue(row: Row, columnName: string, key?: string): unknown {
  if (key) {
    return row[key] ?? null;
  }
  return row[columnName] ?? null;
}

function resolveRouteParams(row: Row, params: Record<string, string>): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(params).map(([paramKey, field]) => {
      if (field.startsWith(':')) {
        return [paramKey, row[field.slice(1)]];
      }
      return [paramKey, field];
    }),
  );
}

function renderText(value: unknown): React.ReactNode {
  if (value === null || value === undefined || value === '') {
    return '-';
  }
  return String(value);
}

function renderBadgeElement(row: Row, columnName: string, display: Extract<CellDisplay, { type: 'badge' }>): React.ReactNode {
  const value = resolveValue(row, columnName, display.key);

  if (value === null || value === undefined) {
    return '-';
  }

  const variant = display.color_field ? (row[display.color_field] as string) : display.variant;
  const tooltipText = display.tooltip_key ? (row[display.tooltip_key] as string) : null;

  const badge = <Badge variant={variant as 'default'}>{String(value)}</Badge>;

  if (tooltipText) {
    return (
      <TooltipProvider delayDuration={0}>
        <Tooltip>
          <TooltipTrigger asChild>{badge}</TooltipTrigger>
          <TooltipContent>{tooltipText}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  return badge;
}

function renderDateElement(row: Row, columnName: string, display: Extract<CellDisplay, { type: 'date' }>): React.ReactNode {
  const value = resolveValue(row, columnName, display.key);

  if (value === null || value === undefined) {
    return '-';
  }

  return <DateTime date={String(value)} />;
}

function renderLinkElement(row: Row, columnName: string, display: Extract<CellDisplay, { type: 'link' }>): React.ReactNode {
  const value = resolveValue(row, columnName, display.key);

  if (value === null || value === undefined) {
    return '-';
  }

  const resolvedParams = resolveRouteParams(row, display.params);

  return (
    <Link className="hover:underline" href={route(display.route, resolvedParams)} prefetch>
      {String(value)}
    </Link>
  );
}

function renderCopyableElement(row: Row, columnName: string, display: Extract<CellDisplay, { type: 'copyable' }>): React.ReactNode {
  const value = resolveValue(row, columnName, display.key);

  if (value === null || value === undefined) {
    return '-';
  }

  return <CopyableBadge text={String(value)} />;
}

function renderIconElement(row: Row, display: Extract<CellDisplay, { type: 'icon' }>): React.ReactNode {
  const iconName = row[display.key] as string | null;

  if (!iconName) {
    return null;
  }

  const IconComponent = iconRegistry[iconName];

  if (!IconComponent) {
    return null;
  }

  return <IconComponent className="text-muted-foreground h-4 w-4 shrink-0" />;
}

export function renderCellDisplays(row: Row, columnName: string, displays: CellDisplay[]): React.ReactNode {
  if (!displays || displays.length === 0) {
    return renderText(row[columnName]);
  }

  const elements = displays.map((display, index) => {
    switch (display.type) {
      case 'text':
        return <React.Fragment key={index}>{renderText(resolveValue(row, columnName, display.key))}</React.Fragment>;
      case 'badge':
        return <React.Fragment key={index}>{renderBadgeElement(row, columnName, display)}</React.Fragment>;
      case 'date':
        return <React.Fragment key={index}>{renderDateElement(row, columnName, display)}</React.Fragment>;
      case 'link':
        return <React.Fragment key={index}>{renderLinkElement(row, columnName, display)}</React.Fragment>;
      case 'copyable':
        return <React.Fragment key={index}>{renderCopyableElement(row, columnName, display)}</React.Fragment>;
      case 'icon':
        return <React.Fragment key={index}>{renderIconElement(row, display)}</React.Fragment>;
      case 'component':
        return null;
      default:
        return <React.Fragment key={index}>{renderText(row[columnName])}</React.Fragment>;
    }
  });

  if (elements.length === 1) {
    return elements[0];
  }

  return <div className="flex items-center gap-2">{elements}</div>;
}
