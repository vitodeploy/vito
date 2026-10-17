import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import EnvironmentLayout from '@/layouts/environment/layout';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment } from '@/types/application';

export default function ShowEnvironment() {
  const { application, environment, can } = usePage<{
    application: Application;
    environment: Environment;
    can: { update: boolean; delete: boolean };
  }>().props;
  const dialog = useDialog();

  return (
    <EnvironmentLayout>
      <Head title={`${environment.name} - ${application.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title={environment.name} description={application.name} />
            <Badge variant="warning">Beta</Badge>
            <Badge variant={environment.status_color}>{environment.status}</Badge>
          </div>
          <div className="flex items-center gap-2">
            {can.update && <Button variant="outline" onClick={() => dialog.editEnvironment.open({ application, environment })}><PencilIcon />Edit</Button>}
            {can.delete && <Button variant="destructive" onClick={() => dialog.deleteApplicationResource.open({ application, environment })}><Trash2Icon />Delete</Button>}
          </div>
        </HeaderContainer>
        <Card>
          <CardContent className="p-4">
            <dl className="grid gap-4 sm:grid-cols-3">
              <div><dt className="text-muted-foreground text-sm">Slug</dt><dd>{environment.slug}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Branch</dt><dd>{environment.branch}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Runtime version</dt><dd>{environment.runtime_version ?? 'Not required'}</dd></div>
            </dl>
          </CardContent>
        </Card>
        <p className="text-muted-foreground text-sm">Components and deployment controls will be available in a future update.</p>
      </Container>
    </EnvironmentLayout>
  );
}
