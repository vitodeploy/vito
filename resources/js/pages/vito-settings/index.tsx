import SettingsLayout from '@/layouts/settings/layout';
import { Head } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import React from 'react';
import ExportVito from '@/pages/vito-settings/components/export';
import ImportVito from '@/pages/vito-settings/components/import';

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
          </CardContent>
        </Card>
      </Container>
    </SettingsLayout>
  );
}
