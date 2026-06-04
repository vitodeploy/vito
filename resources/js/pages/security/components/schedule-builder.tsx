import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const WEEKDAYS = [
  { value: '1', label: 'Monday' },
  { value: '2', label: 'Tuesday' },
  { value: '3', label: 'Wednesday' },
  { value: '4', label: 'Thursday' },
  { value: '5', label: 'Friday' },
  { value: '6', label: 'Saturday' },
  { value: '0', label: 'Sunday' },
];

type Parsed = { frequency: 'daily' | 'weekly'; time: string; weekday: string };

export function parseSchedule(cron?: string | null): Parsed {
  const parts = (cron ?? '').trim().split(/\s+/);
  if (parts.length === 5 && /^\d+$/.test(parts[0]) && /^\d+$/.test(parts[1])) {
    const [m, h, , , dow] = parts;
    const time = `${h.padStart(2, '0')}:${m.padStart(2, '0')}`;
    return { frequency: dow === '*' ? 'daily' : 'weekly', time, weekday: dow === '*' ? '1' : dow };
  }
  return { frequency: 'daily', time: '02:00', weekday: '1' };
}

export function buildSchedule({ frequency, time, weekday }: Parsed): string {
  const [hh, mm] = time.split(':');
  const h = Math.min(Math.max(parseInt(hh, 10) || 0, 0), 23);
  const m = Math.min(Math.max(parseInt(mm, 10) || 0, 0), 59);
  return `${m} ${h} * * ${frequency === 'weekly' ? weekday : '*'}`;
}

export default function ScheduleBuilder({ value, onChange }: { value: Parsed; onChange: (next: Parsed) => void }) {
  return (
    <div className="grid gap-4 sm:grid-cols-3">
      <div className="flex flex-col gap-2">
        <Label htmlFor="frequency">Frequency</Label>
        <Select value={value.frequency} onValueChange={(frequency) => onChange({ ...value, frequency: frequency as Parsed['frequency'] })}>
          <SelectTrigger id="frequency" className="w-full">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectItem value="daily">Daily</SelectItem>
              <SelectItem value="weekly">Weekly</SelectItem>
            </SelectGroup>
          </SelectContent>
        </Select>
      </div>

      {value.frequency === 'weekly' && (
        <div className="flex flex-col gap-2">
          <Label htmlFor="weekday">Day</Label>
          <Select value={value.weekday} onValueChange={(weekday) => onChange({ ...value, weekday })}>
            <SelectTrigger id="weekday" className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {WEEKDAYS.map((day) => (
                  <SelectItem key={day.value} value={day.value}>
                    {day.label}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
      )}

      <div className="flex flex-col gap-2">
        <Label htmlFor="time">Time (UTC)</Label>
        <Input
          id="time"
          type="time"
          className="dark:[&::-webkit-calendar-picker-indicator]:invert"
          value={value.time}
          onChange={(e) => onChange({ ...value, time: e.target.value })}
        />
      </div>
    </div>
  );
}
