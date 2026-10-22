import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { EyeIcon, EyeOffIcon, KeyRoundIcon, LockIcon, MoreHorizontalIcon, PlusIcon, TriangleAlertIcon, UploadIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';
import ApplicationLayout from '@/layouts/application/layout';
import { EmptyState, Section } from '@/pages/applications/components/section';
import { Application, Environment, EnvironmentVariable } from '@/types/application';

function Value({ variable, revealed }: { variable: EnvironmentVariable; revealed: boolean }) {
  if (variable.is_secret) {
    return <span className="text-muted-foreground flex items-center gap-1.5 text-xs"><LockIcon className="size-3" />Secret</span>;
  }
  if (!revealed) return <span className="text-muted-foreground font-mono tracking-widest">••••••••</span>;
  return <span className="font-mono text-sm break-all whitespace-pre-wrap">{variable.value || <span className="text-muted-foreground">empty</span>}</span>;
}

export default function Variables() {
  const { application, environment, variables, warnings, canUpdate } = usePage<{
    application: Application;
    environment: Environment;
    variables: EnvironmentVariable[];
    warnings: string[];
    canUpdate: boolean;
  }>().props;
  const dialog = useDialog();
  const [revealed, setRevealed] = useState(false);
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/variables`;
  const editable = canUpdate && application.status === 'ready' && environment.status === 'ready';
  const own = variables.filter((variable) => variable.source === 'environment');
  const provided = variables.filter((variable) => variable.source !== 'environment');
  const add = () => dialog.variableForm.open({ application, environment });
  const importEnv = () => dialog.importEnv.open({ application, environment });
  const columns = 'grid min-h-15 grid-cols-[minmax(0,2fr)_minmax(0,3fr)_8rem] items-center gap-4 px-4 py-3 text-sm';

  return (
    <ApplicationLayout>
      <Head title={`Variables · ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Variables" description="Shared by every component in this environment. Changes apply on the next deploy." />
          <div className="flex shrink-0 items-center gap-2">
            {variables.length > 0 && (
              <Button variant="outline" aria-pressed={revealed} onClick={() => setRevealed((value) => !value)}>
                {revealed ? <EyeOffIcon /> : <EyeIcon />}<span className="sr-only sm:not-sr-only">{revealed ? 'Hide values' : 'Show values'}</span>
              </Button>
            )}
            {editable && <Button variant="outline" onClick={importEnv}><UploadIcon /><span className="sr-only sm:not-sr-only">Import .env</span></Button>}
            {editable && <Button variant="outline" onClick={add}><PlusIcon />New variable</Button>}
          </div>
        </HeaderContainer>
        {warnings.map((warning, index) => (
          <Alert key={`${index}-${warning}`}><TriangleAlertIcon /><AlertDescription>{warning}</AlertDescription></Alert>
        ))}
        <Section title="Your variables" description="A variable you define replaces a resource variable with the same key.">
          {own.length === 0 ? (
            <EmptyState
              className="rounded-none border-0"
              icon={KeyRoundIcon}
              title="No variables yet"
              description="Add them one by one, or paste your .env file."
              action={editable && <Button size="sm" variant="outline" onClick={importEnv}><UploadIcon />Import .env</Button>}
            />
          ) : own.map((variable) => (
            <div key={variable.id} className={columns}>
              <span className="truncate font-mono font-medium" title={variable.key}>{variable.key}</span>
              <Value variable={variable} revealed={revealed} />
              {editable ? (
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="size-8 justify-self-end" aria-label={`Actions for ${variable.key}`}><MoreHorizontalIcon /></Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem onSelect={() => dialog.variableForm.open({ application, environment, variable })}>Edit</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive" onSelect={() => dialog.confirm.open({
                      title: `Delete ${variable.key}`, description: 'It is removed from every component on the next deploy.',
                      variant: 'destructive', confirmLabel: 'Delete', method: 'delete', url: `${baseUrl}/${variable.id}`,
                    })}>Delete</DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              ) : <span />}
            </div>
          ))}
        </Section>
        {provided.length > 0 && (
          <Section title="Provided by Vito and resources" description="Read-only. Vito's own variables are always applied.">
            {provided.map((variable) => (
              <div key={`${variable.source}-${variable.component_name}-${variable.key}`} className={columns}>
                <span className="truncate font-mono" title={variable.key}>{variable.key}</span>
                <Value variable={variable} revealed={revealed} />
                <span className="text-muted-foreground truncate text-right text-xs" title={variable.source === 'resource' ? 'Your own variable with the same key replaces this one' : 'Managed by Vito and always applied'}>
                  {variable.component_name} · {variable.source === 'resource' ? 'resource' : 'Vito'}
                </span>
              </div>
            ))}
          </Section>
        )}
      </Container>
    </ApplicationLayout>
  );
}
