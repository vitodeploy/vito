import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';
import { ServerProvider } from '@/types/server-provider';
import ConnectServerProvider from '@/pages/server-providers/components/connect-server-provider';
import { Button } from '@/components/ui/button';
import { WifiIcon } from 'lucide-react';

export default function ServerProviderSelect({
  value,
  onValueChange,
  ...props
}: {
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<ServerProvider[]>({
    queryKey: ['serverProvider'],
    queryFn: async () => {
      return (await axios.get(route('server-providers.json'))).data;
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
              query.data.map((serverProvider: ServerProvider) => (
                <SelectItem key={`db-${serverProvider.name}`} value={serverProvider.id.toString()}>
                  {serverProvider.name}
                </SelectItem>
              ))}
          </SelectGroup>
        </SelectContent>
      </Select>
      <ConnectServerProvider onProviderAdded={() => query.refetch()}>
        <Button variant="outline">
          <WifiIcon />
        </Button>
      </ConnectServerProvider>
    </div>
  );
}
