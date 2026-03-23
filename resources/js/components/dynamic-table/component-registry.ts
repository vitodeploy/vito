import React from 'react';
import HostedDomainCell from './cells/hosted-domain-cell';
import HostedDomainCertificateCell from './cells/hosted-domain-certificate-cell';
import HostedDomainExpiresCell from './cells/hosted-domain-expires-cell';
import SslDomainsCell from './cells/ssl-domains-cell';
import SslExpiresCell from './cells/ssl-expires-cell';
import ApiKeyProjectsCell from './cells/api-key-projects-cell';

export type DynamicCellComponentProps<TData = Record<string, unknown>> = {
  row: TData;
};

const componentRegistry: Record<string, React.ComponentType<DynamicCellComponentProps>> = {
  HostedDomainCell,
  HostedDomainCertificateCell,
  HostedDomainExpiresCell,
  SslDomainsCell,
  SslExpiresCell,
  ApiKeyProjectsCell,
};

export function registerCellComponent(name: string, component: React.ComponentType<DynamicCellComponentProps>): void {
  componentRegistry[name] = component;
}

export function getCellComponent(name: string): React.ComponentType<DynamicCellComponentProps> | null {
  return componentRegistry[name] ?? null;
}
