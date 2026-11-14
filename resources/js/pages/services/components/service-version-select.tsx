import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';
import { Link } from '@inertiajs/react';

export default function ServiceVersionSelect({
  serverId,
  service,
  value,
  onValueChange,
  ...props
}: {
  serverId: number;
  service: string;
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<string[]>({
    queryKey: ['service'],
    queryFn: async () => {
      return (await axios.get(`/servers/${serverId}/services/${encodeURIComponent(service)}/versions`)).data;
    },
  });

  const hasVersions = query.isSuccess && query.data.length > 0;
  const isEmpty = query.isSuccess && query.data.length === 0;

  return (
    <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
      <SelectTrigger {...props}>
        <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a version'} />
      </SelectTrigger>
      <SelectContent>
        {isEmpty ? (
          <div className="text-muted-foreground px-3 py-4 text-center text-sm">
            <p>No {service} is installed on this server.</p>
            <Link href={`/servers/${serverId}/services`} className="text-primary mt-1 inline-block hover:underline">
              Go to Services to install {service}
            </Link>
          </div>
        ) : (
          <SelectGroup>
            {hasVersions &&
              query.data.map((version: string) => (
                <SelectItem key={`service-v-${version}`} value={version}>
                  {version}
                </SelectItem>
              ))}
          </SelectGroup>
        )}
      </SelectContent>
    </Select>
  );
}
