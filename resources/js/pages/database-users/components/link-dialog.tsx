import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { DatabaseUser } from '@/types/database-user';
import { Database } from '@/types/database';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { MultiSelect } from '@/components/multi-select';

export default function LinkDatabaseUserDialog({
  open,
  onOpenChange,
  databaseUser,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  databaseUser: DatabaseUser;
}) {
  const page = usePage<{
    databases: Database[];
  }>();
  const form = useForm<{
    databases: string[];
  }>({
    databases: databaseUser.databases,
  });

  const databases = page.props.databases.map((database) => ({
    value: database.name,
    label: database.name,
  }));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('database-users.link', { server: databaseUser.server_id, databaseUser: databaseUser.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Link database user [{databaseUser.username}]</DialogTitle>
          <DialogDescription className="sr-only">Link database user</DialogDescription>
        </DialogHeader>
        <Form id="link-database-user" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="databases">Databases</Label>
              <MultiSelect
                options={databases}
                onValueChange={(value) => form.setData('databases', value)}
                defaultValue={form.data.databases}
                placeholder="Select database"
                maxCount={5}
              />
              <InputError className="mt-2" message={form.errors.databases} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="link-database-user" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
