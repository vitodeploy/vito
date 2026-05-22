import { MetricsFilter } from '@/types/metric';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuPortal,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { CheckIcon, FilterIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Calendar } from '@/components/ui/calendar';
import { DateRange } from 'react-day-picker';
import { formatDateString } from '@/lib/utils';
import { useConfigs } from '@/stores/bootstrap-store';

export default function Filter({ value, onValueChange }: { value?: MetricsFilter; onValueChange?: (filter: MetricsFilter) => void }) {
  const configs = useConfigs()!;
  const form = useForm<MetricsFilter>(
    value || {
      period: '',
      from: '',
      to: '',
    },
  );
  const [range, setRange] = useState<DateRange>();
  const [open, setOpen] = useState(false);

  const setCustomFilter = () => {
    if (!range || !range.from || !range.to) {
      return;
    }
    form.setData({
      period: 'custom',
      from: range.from.toISOString(),
      to: range.to.toISOString(),
    });
    setOpen(false);
    if (onValueChange) {
      onValueChange({
        period: 'custom',
        from: formatDateString(range.from),
        to: formatDateString(range.to),
      });
    }
  };

  const handleValueChange = (newValue: MetricsFilter) => {
    if (newValue.period === 'custom') {
      return;
    }
    form.setData(newValue);
    if (onValueChange) {
      onValueChange(newValue);
    }
    setOpen(false);
  };

  return (
    <DropdownMenu open={open} onOpenChange={setOpen}>
      <DropdownMenuTrigger asChild>
        <Button variant="outline">
          <span className="sr-only">Open menu</span>
          {form.data.period ? <FilterIcon className="text-foreground fill-current" /> : <FilterIcon />}
          <span className="hidden lg:block">Filter</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {configs.metrics_periods.map((period) => {
          return period === 'custom' ? (
            <DropdownMenuSub key={period}>
              <DropdownMenuSubTrigger inset>
                {form.data.period === period && <CheckIcon className="absolute left-3 size-4" />}
                Custom
              </DropdownMenuSubTrigger>
              <DropdownMenuPortal>
                <DropdownMenuSubContent className="p-0">
                  <Calendar mode="range" selected={range} onSelect={setRange} />
                  <div className="p-2">
                    <Button onClick={setCustomFilter} variant="outline" className="w-full" disabled={!range || !range.from || !range.to}>
                      Filter
                    </Button>
                  </div>
                </DropdownMenuSubContent>
              </DropdownMenuPortal>
            </DropdownMenuSub>
          ) : (
            <DropdownMenuCheckboxItem key={period} onSelect={() => handleValueChange({ period })} checked={form.data.period === period}>
              {period.charAt(0).toUpperCase() + period.slice(1)}
            </DropdownMenuCheckboxItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
