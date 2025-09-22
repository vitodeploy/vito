import SettingsLayout from '@/layouts/settings/layout';
import { Head } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import React from 'react';
import ExportVito from '@/pages/vito-settings/components/export';
import ImportVito from '@/pages/vito-settings/components/import';
import { DatabaseIcon } from 'lucide-react';

export default function Users() {
  return (
    <SettingsLayout>
      <Head title="Vito Settings" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Vito Settings" description="Here you can manage general Vito settings" />
        </div>

        <Card>
          <CardContent>
            <CardRow>
              <span>Export all data</span>
              <ExportVito />
            </CardRow>
            <Separator />
            <CardRow>
              <span>Import</span>
              <ImportVito />
            </CardRow>
            <Separator />
            <CardRow>
              <div className="flex flex-col">
                <span>Vito Backups</span>
                <span className="text-muted-foreground text-sm">Automated backups of Vito data</span>
              </div>
              <Button variant="outline" asChild>
                <a href={route('vito-backups.index')}>
                  <DatabaseIcon />
                  Manage Backups
                </a>
              </Button>
            </CardRow>
          </CardContent>
        </Card>
      </Container>
    </SettingsLayout>
  );
}
