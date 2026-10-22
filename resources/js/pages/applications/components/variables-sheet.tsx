import { useState } from 'react';
import { EyeIcon, EyeOffIcon, KeyRoundIcon, LockIcon, MoreHorizontalIcon, PlusIcon, TriangleAlertIcon, UploadIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment, EnvironmentVariable } from '@/types/application';
import { EmptyState, Section } from './section';
import SidePanel from './side-panel';

function Value({ variable, revealed }: { variable: EnvironmentVariable; revealed: boolean }) {
  if (variable.is_secret) {
    return <span className="text-muted-foreground flex items-center gap-1.5 text-xs"><LockIcon className="size-3" />Secret</span>;
  }
  if (!revealed) return <span className="text-muted-foreground font-mono tracking-widest">••••••••</span>;
  return <span className="font-mono text-sm break-all whitespace-pre-wrap">{variable.value || <span className="text-muted-foreground">empty</span>}</span>;
}

export default function VariablesSheet({ application, environment, variables, warnings, canUpdate, onClose }: {
  application: Application;
  environment: Environment;
  variables: EnvironmentVariable[];
  warnings: string[];
  canUpdate: boolean;
  onClose: () => void;
}) {
  const dialog = useDialog();
  const [revealed, setRevealed] = useState(false);
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/variables`;
  const editable = canUpdate && application.status === 'ready' && environment.status === 'ready';
  const own = variables.filter((variable) => variable.source === 'environment');
  const provided = variables.filter((variable) => variable.source !== 'environment');
  const add = () => dialog.variableForm.open({ application, environment });
  const importEnv = () => dialog.importEnv.open({ application, environment });
  const columns = 'grid min-h-15 grid-cols-[minmax(0,2fr)_minmax(0,3fr)_8rem] items-center gap-4 px-4 py-3 text-sm';

  const content = (
    <>
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
    </>
  );

  return (
    <SidePanel
      icon={KeyRoundIcon}
      title="Variables"
      subtitle="Shared by every component in this environment. Changes apply on the next deploy."
      actions={<>
        {variables.length > 0 && (
          <Button variant="outline" size="sm" aria-pressed={revealed} onClick={() => setRevealed((value) => !value)}>
            {revealed ? <EyeOffIcon /> : <EyeIcon />}<span className="sr-only sm:not-sr-only">{revealed ? 'Hide values' : 'Show values'}</span>
          </Button>
        )}
        {editable && <Button variant="outline" size="sm" onClick={importEnv}><UploadIcon /><span className="sr-only sm:not-sr-only">Import .env</span></Button>}
        {editable && <Button variant="outline" size="sm" onClick={add}><PlusIcon /><span className="sr-only sm:not-sr-only">New variable</span></Button>}
      </>}
      tabs={[{ value: 'variables', label: 'Variables', content }]}
      onClose={onClose}
      sheet
    />
  );
}
