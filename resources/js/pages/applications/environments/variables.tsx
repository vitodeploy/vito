import { Head, usePage } from '@inertiajs/react';
import { PencilIcon, PlusIcon, TrashIcon, UploadIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import EnvironmentLayout from '@/layouts/environment/layout';
import { Application, Environment, EnvironmentVariable } from '@/types/application';

export default function Variables() {
  const { application, environment, variables, warnings, canUpdate } = usePage<{
    application: Application;
    environment: Environment;
    variables: EnvironmentVariable[];
    warnings: string[];
    canUpdate: boolean;
  }>().props;
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/variables`;
  const editable = canUpdate && application.status === 'ready' && environment.status === 'ready';

  return (
    <EnvironmentLayout>
      <Head title={`Variables - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Variables" description="Shared by all components in this environment. Changes apply on the next deploy." />
          {editable && <div className="flex gap-2">
            <Button variant="outline" onClick={() => dialog.importEnv.open({ application, environment })}><UploadIcon />Import .env</Button>
            <Button onClick={() => dialog.variableForm.open({ application, environment })}><PlusIcon />Add variable</Button>
          </div>}
        </HeaderContainer>
        {warnings.length > 0 && <Alert><AlertDescription><ul className="grid list-inside list-disc gap-2">{warnings.map((warning) => <li key={warning}>{warning}</li>)}</ul></AlertDescription></Alert>}
        <Table>
          <TableHeader><TableRow><TableHead>Key</TableHead><TableHead>Value</TableHead><TableHead>Source</TableHead><TableHead><span className="sr-only">Actions</span></TableHead></TableRow></TableHeader>
          <TableBody>
            {variables.length === 0 && <TableRow><TableCell colSpan={4} className="text-muted-foreground text-center">No environment variables yet.</TableCell></TableRow>}
            {variables.map((variable) => <TableRow key={`${variable.source}-${variable.component_name ?? variable.id}-${variable.key}`}>
              <TableCell className="font-mono">{variable.key}</TableCell>
              <TableCell><pre className="max-h-32 max-w-sm overflow-auto whitespace-pre-wrap break-all text-sm">{variable.is_secret ? '••••••••' : variable.value}</pre></TableCell>
              <TableCell><div className="flex flex-col items-start gap-1"><Badge variant="secondary">{variable.source === 'vito' ? 'Vito' : variable.source}</Badge>{variable.component_name && <span className="text-muted-foreground text-xs">{variable.component_name} · Read-only override</span>}</div></TableCell>
              <TableCell>{editable && variable.source === 'environment' && <div className="flex justify-end gap-2">
                <Button variant="outline" size="sm" aria-label={`Edit ${variable.key}`} onClick={() => dialog.variableForm.open({ application, environment, variable })}><PencilIcon /></Button>
                <Button variant="outline" size="sm" aria-label={`Delete ${variable.key}`} onClick={() => dialog.confirm.open({
                  title: `Delete variable ${variable.key}`,
                  description: 'This variable will be removed on the next deploy.',
                  variant: 'destructive',
                  confirmLabel: 'Delete',
                  method: 'delete',
                  url: `${baseUrl}/${variable.id}`,
                })}><TrashIcon /></Button>
              </div>}</TableCell>
            </TableRow>)}
          </TableBody>
        </Table>
      </Container>
    </EnvironmentLayout>
  );
}
