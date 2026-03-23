import React from 'react';
import { usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DynamicCellComponentProps } from '../component-registry';
import { Site } from '@/types/site';

export default function HostedDomainCertificateCell({ row }: DynamicCellComponentProps) {
  const sslMethod = row.ssl_method as string;
  const sslId = row.ssl_id as number | null;
  const { site } = usePage<{ site: Site }>().props;
  const createsSiteSSLs = site.webserver_creates_site_ssls;
  const webserverName = site.webserver.charAt(0).toUpperCase() + site.webserver.slice(1);

  if (sslMethod === 'letsencrypt' && !createsSiteSSLs) {
    return <Badge variant="outline">{webserverName} Managed SSL</Badge>;
  }

  if (sslMethod === 'letsencrypt' && createsSiteSSLs && sslId) {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <Badge variant="outline" className="cursor-default">
              Site Certificate
            </Badge>
          </TooltipTrigger>
          <TooltipContent>ID: {sslId}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (!sslId) {
    return <span>-</span>;
  }

  const sslDomains = (row.ssl_domains as string[]) ?? [];

  return (
    <div className="flex flex-wrap gap-1">
      <Badge variant="info">{((row.ssl_type as string) ?? '').toUpperCase()}</Badge>
      <Badge variant="info">#{sslId}</Badge>
      <TooltipProvider>
        {sslDomains.map((domain) => {
          const truncated = domain.length > 20;
          const label = truncated ? domain.slice(0, 20) + '...' : domain;
          return truncated ? (
            <Tooltip key={domain}>
              <TooltipTrigger asChild>
                <Badge variant="outline" className="cursor-default">
                  {label}
                </Badge>
              </TooltipTrigger>
              <TooltipContent>{domain}</TooltipContent>
            </Tooltip>
          ) : (
            <Badge key={domain} variant="outline">
              {label}
            </Badge>
          );
        })}
      </TooltipProvider>
    </div>
  );
}
