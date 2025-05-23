import { useQuery } from '@tanstack/react-query';
import { Database } from '@/types/database';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';

export default function DatabaseSelect({
  serverId,
  value,
  onValueChange,
  ...props
}: {
  serverId: number;
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const databasesQuery = useQuery<Database[]>({
    queryKey: ['databases', serverId],
    queryFn: async () => {
      return (await axios.get(route('databases.json', { server: serverId }))).data;
    },
  });

  return (
    <Select value={value} onValueChange={onValueChange} disabled={databasesQuery.isFetching}>
      <SelectTrigger {...props}>
        <SelectValue placeholder={databasesQuery.isFetching ? 'Loading...' : 'Select a database'} />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          {databasesQuery.isSuccess &&
            databasesQuery.data.map((database: Database) => (
              <SelectItem key={`db-${database.name}`} value={database.id.toString()}>
                {database.name}
              </SelectItem>
            ))}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}
