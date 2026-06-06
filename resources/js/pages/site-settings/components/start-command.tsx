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
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { Input } from '@/components/ui/input';
import { RadioGroup } from '@/components/ui/radio-group';
import RadioCard from '@/components/radio-card';

type ApplyChoice = '' | 'config' | 'restart';

export default function StartCommand({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const groupLabelId = useId();
  const hasWorker = site.bootstrap_worker_id != null;

  const form = useForm<{ start_command: string; apply: ApplyChoice }>({
    start_command: site.start_command ?? '',
    apply: '',
  });

  const handleOpenChange = (next: boolean) => {
    setOpen(next);
    if (!next) {
      form.reset();
      form.clearErrors();
    }
  };

  const choiceMissing = hasWorker && form.data.apply === '';

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (choiceMissing) {
      return;
    }
    form.transform((data) => ({
      start_command: data.start_command,
      ...(hasWorker ? { restart: data.apply === 'restart' } : {}),
    }));
    form.patch(route('site-settings.update-start-command', { server: site.server_id, site: site.id }), {
      onSuccess: () => handleOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update Start Command</DialogTitle>
          <DialogDescription>The shell command supervisor uses to start your application worker.</DialogDescription>
        </DialogHeader>

        <Form id="start-command-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="start_command">Start Command</Label>
              <Input
                id="start_command"
                type="text"
                value={form.data.start_command}
                placeholder="e.g., npm start"
                onChange={(e) => form.setData('start_command', e.target.value)}
              />
              {!hasWorker && (
                <p className="text-muted-foreground text-xs">The application worker will be created with this command on the next deploy.</p>
              )}
              <InputError message={form.errors.start_command} />
            </FormField>

            {hasWorker && (
              <FormField>
                <Label id={groupLabelId}>How should we apply this change?</Label>
                <RadioGroup
                  value={form.data.apply}
                  onValueChange={(value) => form.setData('apply', value as ApplyChoice)}
                  aria-labelledby={groupLabelId}
                  className="gap-2"
                >
                  <RadioCard
                    value="config"
                    selected={form.data.apply === 'config'}
                    onSelect={(value) => form.setData('apply', value as ApplyChoice)}
                    title="Update config only"
                    description="The worker keeps running its current command. The change takes effect on the next restart or deploy."
                  />
                  <RadioCard
                    value="restart"
                    selected={form.data.apply === 'restart'}
                    onSelect={(value) => form.setData('apply', value as ApplyChoice)}
                    title="Update and restart now"
                    description="Rewrites the config and restarts the worker immediately so the new command takes effect right away."
                  />
                </RadioGroup>
              </FormField>
            )}
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="start-command-form" disabled={form.processing || choiceMissing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
