import * as React from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

export function Combobox({
  items,
  value,
  id,
  searchText = 'Search items...',
  noneFoundText = 'No items found.',
  placeholder = '',
  onValueChange,
}: {
  items: { value: string; label: string; keywords?: string[] }[];
  value: string;
  id?: string;
  searchText?: string;
  noneFoundText?: string;
  placeholder?: string;
  onValueChange: (value: string) => void;
}) {
  const [open, setOpen] = React.useState(false);
  const selectedLabel = value ? items.find((item) => item.value === value)?.label : '';

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button id={id} variant="outline" role="combobox" aria-expanded={open} className="flex-1 justify-between">
          <span className={selectedLabel ? '' : 'text-muted-foreground'}>{selectedLabel || placeholder}</span>
          <ChevronsUpDown className="opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="p-0">
        <Command>
          <CommandInput placeholder={searchText} />
          <CommandList className="p-0">
            <CommandEmpty>{noneFoundText}</CommandEmpty>
            <CommandGroup>
              {open &&
                items.map((item) => (
                  <CommandItem
                    key={item.value}
                    value={item.value}
                    keywords={item.keywords}
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
