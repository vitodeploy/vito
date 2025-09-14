import * as React from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

export function Combobox({
  items,
  value,
  searchText = 'Search items...',
  noneFoundText = 'No items found.',
  onValueChange,
}: {
  items: { value: string; label: string }[];
  value: string;
  searchText?: string;
  noneFoundText?: string;
  onValueChange: (value: string) => void;
}) {
  const [open, setOpen] = React.useState(false);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button variant="outline" role="combobox" aria-expanded={open} className="flex-1 justify-between">
          <span>{value ? items.find((item) => item.value === value)?.label : ''}</span>
          <ChevronsUpDown className="opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="p-0">
        <Command>
          <CommandInput placeholder={searchText} />
          <CommandList className="pointer-events-auto p-0" onWheel={(e) => e.stopPropagation()}>
            <CommandEmpty>{noneFoundText}</CommandEmpty>
            <CommandGroup>
              {open &&
                items.map((item) => (
                  <CommandItem
                    key={item.value}
                    value={item.value}
                    onSelect={(currentValue) => {
                      value = currentValue;
                      onValueChange(value);
                      setOpen(false);
                    }}
                  >
                    {item.label}
                    <Check className={cn('ml-auto', value === item.value ? 'opacity-100' : 'opacity-0')} />
                  </CommandItem>
                ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
