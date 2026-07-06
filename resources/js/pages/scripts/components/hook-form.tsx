import { FormEvent, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useConfigs } from '@/stores/bootstrap-store';
import { ProjectSelect } from '@/components/project-select';
import ServerSelect from '@/pages/servers/components/server-select';
import { Script } from '@/types/script';
import { ScriptEventHook } from '@/types/script-event-hook';
import { Server } from '@/types/server';
import { Project } from '@/types/project';

export default function HookForm({
  open,
  onOpenChange,
  script,
  hook,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  script: Script;
  hook?: ScriptEventHook;
}) {
  const configs = useConfigs();
  const [server, setServer] = useState<Server | undefined>(hook?.server);

  const form = useForm<{
    event: string;
    project_id: string;
    server_id: string;
    user: string;
    enabled: boolean;
  }>({
    event: hook?.event_value ?? '',
    project_id: hook?.project_id?.toString() ?? '',
    server_id: hook?.server_id?.toString() ?? '',
    user: hook?.user ?? '',
    enabled: hook?.enabled ?? true,
  });

  const selectedEvent = configs?.script_event_hooks?.events.find((e) => e.value === form.data.event);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (hook) {
      form.put(route('scripts.hooks.update', { script: script.id, hook: hook.id }), {
        onSuccess: () => onOpenChange(false),
      });
      return;
    }

    form.post(route('scripts.hooks.store', { script: script.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>{hook ? 'Edit' : 'Add'} Event Hook</SheetTitle>
          <SheetDescription className="sr-only">{hook ? 'Edit' : 'Add'} event hook</SheetDescription>
        </SheetHeader>
        <Form id="hook-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="event">Event</Label>
              <Select value={form.data.event} onValueChange={(value) => form.setData('event', value)}>
                <SelectTrigger id="event">
                  <SelectValue placeholder="Select an event..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {configs?.script_event_hooks?.events.map((event) => (
                      <SelectItem key={event.value} value={event.value}>
                        {event.label}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              {selectedEvent && selectedEvent.variables.length > 0 && (
                <p className="text-muted-foreground text-xs">
                  Available variables:{' '}
                  {selectedEvent.variables.map((v, i) => (
                    <span key={v}>
                      <code className="bg-muted rounded px-1">${'{' + v + '}'}</code>
                      {i < selectedEvent.variables.length - 1 ? ', ' : ''}
                    </span>
                  ))}
                </p>
              )}
              <InputError message={form.errors.event} />
            </FormField>

            <FormField>
              <Label htmlFor="project_id">Project</Label>
              <ProjectSelect
                value={form.data.project_id}
                onValueChange={(value: string, project: Project) => {
                  form.setData('project_id', value);
                  if (project.id.toString() !== form.data.project_id) {
                    form.setData('server_id', '');
                    form.setData('user', '');
                    setServer(undefined);
                  }
                }}
              />
              <InputError message={form.errors.project_id} />
            </FormField>

            <FormField>
              <Label htmlFor="server_id">Server</Label>
              <ServerSelect
                value={form.data.server_id}
                onValueChange={(value) => {
                  form.setData('server_id', value ? value.id.toString() : '');
                  form.setData('user', '');
                  setServer(value);
                }}
              />
              <InputError message={form.errors.server_id} />
            </FormField>

            <FormField>
              <Label htmlFor="user">SSH User</Label>
              <Select value={form.data.user} onValueChange={(value) => form.setData('user', value)}>
                <SelectTrigger id="user">
                  <SelectValue placeholder="Select a user..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {server?.ssh_users?.map((u) => (
                      <SelectItem key={`user-${u}`} value={u}>
                        {u}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.user} />
            </FormField>

            <FormField>
              <div className="flex items-center gap-2">
                <Checkbox
                  id="enabled"
                  checked={form.data.enabled}
                  onCheckedChange={(checked) => form.setData('enabled', checked === true)}
                />
                <Label htmlFor="enabled">Enabled</Label>
              </div>
              <InputError message={form.errors.enabled} />
            </FormField>
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="hook-form" type="submit" disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              {hook ? 'Save' : 'Add Hook'}
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
