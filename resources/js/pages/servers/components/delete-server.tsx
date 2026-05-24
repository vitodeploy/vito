import { Server } from '@/types/server';
import { FormEvent, ReactNode, useId, useState } from 'react';
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
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useConfigs } from '@/stores/bootstrap-store';
import { LoaderCircleIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type DeleteFromProviderChoice = '' | 'yes' | 'no';

export default function DeleteServer({ server, children }: { server: Server; children: ReactNode }) {
  const configs = useConfigs();
  const isCustom = server.provider === 'custom';
  const providerLabel = configs?.server_provider.providers[server.provider]?.label ?? server.provider;
  const groupLabelId = useId();
  const [open, setOpen] = useState(false);

  const form = useForm<{ name: string; delete_from_provider: DeleteFromProviderChoice }>({
    name: '',
    delete_from_provider: '',
  });

  const handleOpenChange = (next: boolean) => {
    setOpen(next);
    if (!next) {
      form.reset();
      form.clearErrors();
    }
  };

  const choiceMissing = !isCustom && form.data.delete_from_provider === '';

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (choiceMissing) {
      return;
    }
    form.transform((data) => ({
      name: data.name,
      ...(isCustom ? {} : { delete_from_provider: data.delete_from_provider === 'yes' }),
    }));
    form.delete(route('servers.destroy', server.id), {
      onSuccess: () => setOpen(false),
    });
  };

  const submitDisabled = form.processing || form.data.name !== server.name || choiceMissing;

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {server.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete server and its resources.</DialogDescription>
        </DialogHeader>

        <p className="p-4">
          Are you sure you want to delete this server: <strong>{server.name}</strong>? All resources associated with this server will be deleted and
          this action cannot be undone.
        </p>

        <Form id="delete-server-form" onSubmit={submit} className="p-4">
          <FormFields>
            {!isCustom && (
              <FormField>
                <Label id={groupLabelId}>How should we handle the server at {providerLabel}?</Label>
                <RadioGroup
                  value={form.data.delete_from_provider}
                  onValueChange={(value) => form.setData('delete_from_provider', value as DeleteFromProviderChoice)}
                  aria-labelledby={groupLabelId}
                  className="gap-2"
                >
                  <RadioCard
                    value="no"
                    selected={form.data.delete_from_provider === 'no'}
                    title="Remove from Vito only"
                    description={`Keep the server running at ${providerLabel}.`}
                  />
                  <RadioCard
                    value="yes"
                    selected={form.data.delete_from_provider === 'yes'}
                    title={`Delete from Vito and ${providerLabel}`}
                    description="Permanently destroy the server at the provider. This cannot be undone."
                  />
                </RadioGroup>
                <InputError message={form.errors.delete_from_provider} />
              </FormField>
            )}

            <FormField>
              <Label htmlFor="server-name">Server name</Label>
              <Input id="server-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="delete-server-form" variant="destructive" disabled={submitDisabled}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Delete server
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function RadioCard({ value, selected, title, description }: { value: string; selected: boolean; title: string; description: string }) {
  return (
    <label
      className={cn(
        'hover:bg-accent flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors',
        selected && 'border-primary bg-accent',
      )}
    >
      <RadioGroupItem value={value} className="mt-0.5" />
      <span className="flex flex-col gap-1">
        <span className="text-sm font-medium">{title}</span>
        <span className="text-muted-foreground text-sm">{description}</span>
      </span>
    </label>
  );
}
