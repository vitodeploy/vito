import { Repo } from '@/types/repo';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { DownloadIcon, LoaderCircleIcon } from 'lucide-react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Plugin } from '@/types/plugin';

export default function Install({ repo }: { repo?: Repo }) {
  const [open, setOpen] = useState(false);
  const page = usePage<{
    plugins: Plugin[];
  }>();

  const form = useForm({
    url: repo?.html_url || '',
  });

  const submit = () => {
    form.post(route('plugins.install'), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button disabled={repo && page.props.plugins.filter((plugin) => plugin.name === repo.full_name).length > 0}>
          <DownloadIcon />
          {repo && page.props.plugins.filter((plugin) => plugin.name === repo.full_name).length > 0 ? 'Installed' : 'Install'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Install plugin</DialogTitle>
          <DialogDescription className="sr-only">Install plugin {repo?.full_name}</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="install-plugin-form" onSubmit={submit}>
          {repo ? (
            <p>
              Are you sure you want to install the plugin{' '}
              <strong className="text-primary hover:underline">
                <a href={repo.html_url} target="_blank">
                  {repo.full_name}
                </a>
              </strong>
              ? This will clone the repository and set it up as a Vito plugin.
            </p>
          ) : (
            <FormFields>
              <FormField>
                <Alert>
                  <AlertDescription>
                    <p>You can use this form to install a plugin or use the following command on your Vito instance</p>
                    <pre className="bg-muted rounded-md px-2 py-1">
                      <code>php artisan plugins:install &lt;repository-url&gt;</code>
                    </pre>
                  </AlertDescription>
                </Alert>
              </FormField>
              <FormField>
                <Label htmlFor="url">Repository URL</Label>
                <Input
                  id="url"
                  type="text"
                  name="url"
                  autoComplete="url"
                  value={form.data.url}
                  onChange={(e) => form.setData('url', e.target.value)}
                />
                <InputError message={form.errors.url} />
              </FormField>
            </FormFields>
          )}
        </Form>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Install
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
