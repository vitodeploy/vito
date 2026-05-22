import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { useConfigs } from '@/stores/bootstrap-store';

export default function ColorSelect({ onValueChange, ...props }: React.ComponentProps<typeof Select>) {
  const configs = useConfigs();

  return (
    <Select {...props} onValueChange={onValueChange}>
      <SelectTrigger>
        <SelectValue placeholder="Select a color" />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          {(configs?.colors ?? []).map((value) => (
            <SelectItem key={`color-${value}`} value={value}>
              <div className="size-5 rounded-sm" style={{ backgroundColor: `var(--color-${value}-500)` }}></div>
              {value}
            </SelectItem>
          ))}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}
