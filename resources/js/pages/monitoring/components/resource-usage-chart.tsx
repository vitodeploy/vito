import * as React from 'react';
import { useId } from 'react';
import { Area, AreaChart, XAxis, YAxis } from 'recharts';

import { Card, CardContent } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Metric } from '@/types/metric';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface Props {
  title: string;
  color: string;
  dataKey: keyof Metric;
  label: string;
  chartData: Metric[];
  link?: string;
  formatter?: (value: unknown, name: unknown) => string | number;
  single?: boolean;
  height?: 'small' | 'medium' | 'large';
  showXAxis?: boolean;
  valueFormatter?: (value: unknown) => string | number;
}

export function ResourceUsageChart({ title, color, dataKey, label, chartData, link, formatter, single, height, showXAxis, valueFormatter }: Props) {
  const gradientId = useId();
  const resolvedHeight = height ?? (single ? 'large' : 'small');
  const heightClass = resolvedHeight === 'large' ? 'h-[400px]' : resolvedHeight === 'medium' ? 'h-[200px]' : 'h-[100px]';
  const xAxisVisible = showXAxis ?? single ?? false;
  const chartConfig = {
    [dataKey]: {
      label: label,
      color: color,
    },
  } satisfies ChartConfig;

  const validData = chartData.filter((row) => row[dataKey] != null);
  const latest = validData.length > 0 ? validData[validData.length - 1][dataKey] : null;

  return (
    <Card>
      <CardContent className="overflow-hidden p-0">
        <div className="flex items-start justify-between p-4">
          <div className="space-y-2 py-[7px]">
            <h2 className="text-muted-foreground text-sm">{title}</h2>
            <span className="text-3xl font-bold">
              {latest != null
                ? valueFormatter
                  ? valueFormatter(latest)
                  : formatter
                    ? formatter(latest, dataKey)
                    : (latest as number | string).toLocaleString()
                : 'N/A'}
            </span>
          </div>
          {!single && link && (
            <Button variant="ghost" onClick={() => router.visit(link)}>
              View
            </Button>
          )}
        </div>
        {validData.length === 0 ? (
          <div className={cn('text-muted-foreground flex aspect-auto w-full items-center justify-center rounded-b-xl border-t text-sm', heightClass)}>
            No data
          </div>
        ) : (
          <ChartContainer config={chartConfig} className={cn('aspect-auto w-full overflow-hidden rounded-b-xl', heightClass)}>
            <AreaChart accessibilityLayer data={validData} margin={{ left: 0, right: 0, top: 0, bottom: 0 }}>
              <defs>
                <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={color} stopOpacity={0.8} />
                  <stop offset="95%" stopColor={color} stopOpacity={0.1} />
                </linearGradient>
              </defs>
              <YAxis hide />
              <XAxis
                hide={!xAxisVisible}
                dataKey="date"
                tickLine={false}
                axisLine={false}
                tickMargin={8}
                minTickGap={32}
                tickFormatter={(value) => {
                  const date = new Date(value);
                  return date.toLocaleDateString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    month: 'short',
                    day: 'numeric',
                  });
                }}
              />
              <ChartTooltip
                cursor={true}
                content={
                  <ChartTooltipContent
                    labelFormatter={(value) => {
                      return new Date(value).toLocaleDateString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        month: 'short',
                        day: 'numeric',
                      });
                    }}
                    formatter={formatter}
                    indicator="dot"
                  />
                }
              />
              <Area dataKey={dataKey} type="monotone" fill={`url(#${gradientId})`} stroke={color} />
            </AreaChart>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  );
}
