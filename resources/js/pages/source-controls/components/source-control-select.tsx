import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';
import { SourceControl } from '@/types/source-control';
import ConnectSourceControl from '@/pages/source-controls/components/connect-source-control';
import { Button } from '@/components/ui/button';
import { WifiIcon } from 'lucide-react';

export default function SourceControlSelect({
  value,
  onValueChange,
  ...props
}: {
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<SourceControl[]>({
    queryKey: ['sourceControl'],
    queryFn: async () => {
      return (await axios.get('/settings/source-controls/json')).data;
    },
  });

  return (
    <div className="flex items-center gap-2">
      <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
        <SelectTrigger {...props}>
          <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a provider'} />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            {query.isSuccess &&
              query.data.map((sourceControl: SourceControl) => (
                <SelectItem key={`db-${sourceControl.name}`} value={sourceControl.id.toString()}>
                  {sourceControl.name}
                </SelectItem>
              ))}
          </SelectGroup>
        </SelectContent>
      </Select>
      <ConnectSourceControl onProviderAdded={() => query.refetch()}>
        <Button variant="outline">
          <WifiIcon />
        </Button>
      </ConnectSourceControl>
    </div>
  );
}
