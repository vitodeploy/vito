import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';
import { SshKey } from '@/types/ssh-key';
import AddSshKey from '@/pages/ssh-keys/components/add-ssh-key';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-react';

export default function SshKeySelect({
  value,
  onValueChange,
  ...props
}: {
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<SshKey[]>({
    queryKey: ['sshKey'],
    queryFn: async () => {
      return (await axios.get('/settings/ssh-keys/json')).data;
    },
  });

  return (
    <div className="flex items-center gap-2">
      <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
        <SelectTrigger {...props}>
          <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a key'} />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            {query.isSuccess &&
              query.data.map((sshKey: SshKey) => (
                <SelectItem key={`db-${sshKey.name}`} value={sshKey.id.toString()}>
                  {sshKey.name}
                </SelectItem>
              ))}
          </SelectGroup>
        </SelectContent>
      </Select>
      <AddSshKey onKeyAdded={() => query.refetch()}>
        <Button variant="outline">
          <PlusIcon />
        </Button>
      </AddSshKey>
    </div>
  );
}
