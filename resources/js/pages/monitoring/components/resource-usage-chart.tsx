import * as React from 'react';
import { Area, AreaChart, XAxis } from 'recharts';

import { Card, CardContent } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Metric } from '@/types/metric';
import { Button } from '@/components/ui/button';
import { Formatter } from 'recharts/types/component/DefaultTooltipContent';

interface Props {
  title: string;
  color: string;
  dataKey: 'load' | 'memory_used' | 'disk_used';
  label: string;
  chartData: Metric[];
  formatter?: Formatter<number | string, string>;
}

export function ResourceUsageChart({ title, color, dataKey, label, chartData, formatter }: Props) {
  const chartConfig = {
    [dataKey]: {
      label: label,
      color: color,
    },
  } satisfies ChartConfig;

  const getCurrentValue = () => {
    if (chartData.length === 0) return 'N/A';

    const value = chartData[chartData.length - 1][dataKey];
    if (formatter) {
      return formatter(value, dataKey);
    }

    return typeof value === 'number' ? value.toLocaleString() : String(value);
  };

  return (
    <Card>
      <CardContent className="overflow-hidden p-0">
        <div className="flex items-start justify-between p-4">
          <div className="space-y-2 py-[7px]">
            <h2 className="text-muted-foreground text-sm">{title}</h2>
            <span className="text-3xl font-bold">{getCurrentValue()}</span>
          </div>
          <Button variant="ghost">View</Button>
        </div>
        <ChartContainer config={chartConfig} className="aspect-auto h-[100px] w-full overflow-hidden rounded-b-xl">
          <AreaChart data={chartData} margin={{ left: 0, right: 0, top: 0, bottom: 0 }}>
            <defs>
              <linearGradient id={`fill-${dataKey}`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor={color} stopOpacity={0.8} />
                <stop offset="95%" stopColor={color} stopOpacity={0.1} />
              </linearGradient>
            </defs>
            <XAxis
              hide
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
              cursor={false}
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
            <Area dataKey={dataKey} type="natural" fill={`url(#fill-${dataKey})`} stroke={color} />
          </AreaChart>
        </ChartContainer>
      </CardContent>
    </Card>
  );
}
