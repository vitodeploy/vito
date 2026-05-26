import { Area, AreaChart, XAxis, YAxis } from 'recharts';

import { Card, CardContent } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { cn } from '@/lib/utils';

interface Props {
  title: string;
  value?: string;
  color: string;
  dataKey: string;
  labelKey: string;
  data: Array<Record<string, string | number>>;
  formatLabel?: (value: string) => string;
  formatValue?: (value: unknown) => string | number;
  height?: 'sm' | 'md';
}

export function StatsChart({ title, value, color, dataKey, labelKey, data, formatLabel, formatValue, height = 'md' }: Props) {
  const chartConfig = {
    [dataKey]: { label: title, color },
  } satisfies ChartConfig;

  const heightClass = height === 'sm' ? 'h-[120px]' : 'h-[200px]';

  return (
    <Card>
      <CardContent className="overflow-hidden p-0">
        <div className="flex items-start justify-between p-4">
          <div className="space-y-2 py-[7px]">
            <h2 className="text-muted-foreground text-sm">{title}</h2>
            {value !== undefined && <span className="text-3xl font-bold">{value}</span>}
          </div>
        </div>
        {data.length === 0 ? (
          <div className={cn('text-muted-foreground flex w-full items-center justify-center rounded-b-xl border-t text-sm', heightClass)}>No data</div>
        ) : (
          <ChartContainer config={chartConfig} className={cn('aspect-auto w-full overflow-hidden rounded-b-xl', heightClass)}>
            <AreaChart accessibilityLayer data={data} margin={{ left: 0, right: 0, top: 0, bottom: 0 }}>
              <defs>
                <linearGradient id={`fill-${dataKey}`} x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={color} stopOpacity={0.8} />
                  <stop offset="95%" stopColor={color} stopOpacity={0.1} />
                </linearGradient>
              </defs>
              <YAxis hide />
              <XAxis
                dataKey={labelKey}
                tickLine={false}
                axisLine={false}
                tickMargin={8}
                minTickGap={32}
                tickFormatter={(v) => (formatLabel ? formatLabel(String(v)) : String(v))}
              />
              <ChartTooltip
                cursor={true}
                content={
                  <ChartTooltipContent labelFormatter={(v) => (formatLabel ? formatLabel(String(v)) : String(v))} formatter={formatValue} indicator="dot" />
                }
              />
              <Area dataKey={dataKey} type="monotone" fill={`url(#fill-${dataKey})`} stroke={color} />
            </AreaChart>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  );
}
