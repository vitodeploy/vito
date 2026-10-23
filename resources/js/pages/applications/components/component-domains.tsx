import { ArrowRightIcon, ExternalLinkIcon, GlobeIcon, MoreHorizontalIcon, PlusIcon } from 'lucide-react';
import CopyableField from '@/components/copyable-field';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Component, Environment, EnvironmentDomain, EnvironmentDomainOptions } from '@/types/application';
import { EmptyState, Section } from './section';
import Status from './status';

export default function ComponentDomains({
  application,
  environment,
  component,
  domains,
  options,
  editable,
}: {
  application: Application;
  environment: Environment;
  component: Component;
  domains: EnvironmentDomain[];
  options: EnvironmentDomainOptions;
  editable: boolean;
}) {
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/domains`;
  const target = options.components.find((item) => item.id === component.id);
  const add = () => dialog.environmentDomainForm.open({ application, environment, options, componentId: component.id });

  if (!target && domains.length === 0) {
    return (
      <Section title="Domains">
        <EmptyState
          className="rounded-none border-0"
          icon={GlobeIcon}
          title="Domains need the Web capability"
          description="Turn on Web in Settings, wait for it to be ready, then route a domain here."
        />
      </Section>
    );
  }

  return (
    <Section
      title="Domains"
      description={
        target ? (
          <>
            Point DNS to <span className="text-foreground font-mono">{target.server_ip}</span> ({target.server_name}).
          </>
        ) : undefined
      }
      action={
        editable &&
        target && (
          <Button size="sm" variant="outline" onClick={add}>
            <PlusIcon />
            Add domain
          </Button>
        )
      }
    >
      {domains.length === 0 ? (
        <EmptyState
          className="rounded-none border-0"
          icon={GlobeIcon}
          title="No domains yet"
          description="Add a domain to send traffic to this component."
        />
      ) : (
        domains.map((domain) => {
          const redirectTo = domains.find((item) => item.id === domain.redirect_to_domain_id)?.host;
          return (
            <div key={domain.id} className="flex flex-col gap-3 px-4 py-3 text-sm">
              <div className="flex min-h-9 items-center gap-3">
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                  <a
                    href={`${domain.ssl_method === 'disabled' ? 'http' : 'https'}://${domain.host}`}
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center gap-1.5 truncate font-medium hover:underline"
                  >
                    {domain.host}
                    <ExternalLinkIcon className="text-muted-foreground size-3.5 shrink-0" />
                  </a>
                  <span className="text-muted-foreground flex flex-wrap items-center gap-x-2 text-xs">
                    {domain.type === 'redirect' ? (
                      <span className="flex items-center gap-1">
                        Redirects <ArrowRightIcon className="size-3" />
                        {redirectTo ?? 'alias'}
                      </span>
                    ) : (
                      <span>Alias</span>
                    )}
                    <span>·</span>
                    <span>
                      {domain.ssl_method === 'disabled' ? 'No SSL' : domain.ssl_method === 'letsencrypt' ? "Let's Encrypt" : 'Custom certificate'}
                    </span>
                    {domain.force_ssl && (
                      <>
                        <span>·</span>
                        <span>HTTPS only</span>
                      </>
                    )}
                  </span>
                </div>
                <Status status={domain.status} color={domain.status_color} className="text-muted-foreground shrink-0" />
                {editable && (
                  <DropdownMenu modal={false}>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" className="size-8" aria-label={`Actions for ${domain.host}`}>
                        <MoreHorizontalIcon />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      {domain.status === 'pending' && (
                        <DropdownMenuItem
                          onSelect={() =>
                            dialog.confirm.open({
                              title: `Verify ${domain.host}`,
                              description: 'Checks DNS and HTTP on the target server in the background.',
                              confirmLabel: 'Verify now',
                              method: 'post',
                              url: `${baseUrl}/${domain.id}/verify`,
                            })
                          }
                        >
                          Verify now
                        </DropdownMenuItem>
                      )}
                      <DropdownMenuItem
                        disabled={domain.status === 'updating'}
                        onSelect={() => dialog.environmentDomainForm.open({ application, environment, options, domain, componentId: component.id })}
                      >
                        Edit
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        variant="destructive"
                        onSelect={() =>
                          dialog.confirm.open({
                            title: `Remove ${domain.host}`,
                            description: 'Removes the vhost and any certificate no other domain uses. Delete redirects to this domain first.',
                            variant: 'destructive',
                            confirmLabel: 'Remove',
                            method: 'delete',
                            url: `${baseUrl}/${domain.id}`,
                          })
                        }
                      >
                        Remove
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                )}
              </div>
              {domain.status === 'pending' && target && (
                <div className="flex flex-col gap-1.5">
                  <span className="text-muted-foreground text-xs">Add this DNS record, then verify:</span>
                  <div className="grid grid-cols-[3rem_1fr_1fr] items-center gap-2 text-xs">
                    <span className="font-mono">{target.server_ip.includes(':') ? 'AAAA' : 'A'}</span>
                    <CopyableField value={domain.host} />
                    <CopyableField value={target.server_ip} />
                  </div>
                </div>
              )}
              {domain.error && (
                <p role="alert" className="text-destructive text-sm">
                  {domain.error}
                </p>
              )}
            </div>
          );
        })
      )}
    </Section>
  );
}
