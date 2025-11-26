import { useQuery } from '@tanstack/react-query';
import { Database } from '@/types/database';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SelectTriggerProps } from '@radix-ui/react-select';
import CreateDatabase from './create-database';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-react';

export default function DatabaseSelect({
  serverId,
  value,
  createWithUser,
  defaultCharset,
  defaultCollation,
  onValueChange,
  ...props
}: {
  serverId: number;
  value: string;
  createWithUser?: boolean;
  defaultCharset?: string;
  defaultCollation?: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<Database[]>({
    queryKey: ['databases', serverId],
    queryFn: async () => {
      return (await axios.get(route('databases.json', { server: serverId }))).data;
    },
  });

  return (
    <div className="flex items-center gap-2">
      <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
        <SelectTrigger {...props}>
          <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a database'} />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            {query.isSuccess &&
              query.data.map((database: Database) => (
                <SelectItem key={`db-${database.name}`} value={database.id.toString()}>
                  {database.name}
                </SelectItem>
              ))}
          </SelectGroup>
        </SelectContent>
      </Select>
      <CreateDatabase
        server={serverId}
        withUser={createWithUser}
        defaultCharset={defaultCharset}
        defaultCollation={defaultCollation}
        onDatabaseCreated={() => query.refetch()}
      >
        <Button variant="outline">
          <PlusIcon />
        </Button>
      </CreateDatabase>
    </div>
  );
}
