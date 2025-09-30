import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SelectTriggerProps } from '@radix-ui/react-select';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, PlusIcon } from 'lucide-react';
import CreateDatabaseUser from './create-database-user';
import { DatabaseUser } from '@/types/database-user';
import { Badge } from '@/components/ui/badge';

export default function DatabaseUserSelect({
  serverId,
  value,
  onValueChange,
  create = true,
  ...props
}: {
  serverId: number;
  value: string;
  create?: boolean;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<DatabaseUser[]>({
    queryKey: ['database-users', serverId],
    queryFn: async () => {
      return (await axios.get(route('database-users.json', { server: serverId }))).data;
    },
  });

  return (
    <div className="flex items-center gap-2">
      <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
        <SelectTrigger {...props}>
          <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a database user'} />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            {query.isSuccess &&
              query.data.map((databaseUser: DatabaseUser) => (
                <SelectItem key={`db-${databaseUser.username}`} value={databaseUser.id.toString()}>
                  {databaseUser.username}
                  <Badge variant="outline">{databaseUser.permission}</Badge>
                </SelectItem>
              ))}
          </SelectGroup>
        </SelectContent>
      </Select>
      <Button type="button" variant="outline" onClick={() => query.refetch()} disabled={query.isFetching}>
        <LoaderCircleIcon />
      </Button>
      {create && (
        <CreateDatabaseUser server={serverId} onDatabaseUserCreated={() => query.refetch()}>
          <Button variant="outline">
            <PlusIcon />
          </Button>
        </CreateDatabaseUser>
      )}
    </div>
  );
}
