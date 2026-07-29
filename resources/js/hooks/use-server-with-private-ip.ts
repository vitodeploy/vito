import { useState } from 'react';
import { InertiaFormProps } from '@inertiajs/react';
import { NetworkServerOption } from '@/types/network';
import { PrivateIp } from '@/pages/networks/components/private-ip-select';

export type ServerWithPrivateIpForm = {
  servers: number[];
  ip_addresses: Record<number, number>;
};

export function useServerWithPrivateIp<T extends ServerWithPrivateIpForm>(form: InertiaFormProps<T>, initialServers: NetworkServerOption[]) {
  const [servers, setServers] = useState<NetworkServerOption[]>(initialServers);

  const selectedServerId = form.data.servers[0] ?? null;
  const selectedServer = selectedServerId ? (servers.find((s) => s.id === selectedServerId) ?? null) : null;

  const selectServer = (id: number) => {
    form.setData((prev) => ({ ...prev, servers: [id], ip_addresses: {} }));
  };

  const selectIp = (ipId: number) => {
    form.setData((prev) => ({ ...prev, ip_addresses: selectedServerId ? { [selectedServerId]: ipId } : {} }));
  };

  const applyRefreshedIps = (serverId: number, ips: PrivateIp[]) => {
    setServers((prev) => prev.map((s) => (s.id === serverId ? { ...s, private_ips: ips } : s)));
  };

  return {
    servers,
    selectedServerId,
    selectedServer,
    selectServer,
    selectIp,
    applyRefreshedIps,
    selectedIp: selectedServerId ? form.data.ip_addresses[selectedServerId] : undefined,
    ipError: selectedServerId ? (form.errors[`ip_addresses.${selectedServerId}` as keyof typeof form.errors] as string | undefined) : undefined,
  };
}
