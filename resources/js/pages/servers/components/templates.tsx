import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ServerTemplate, Service } from '@/types/server-template';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ChevronsUpDownIcon, LoaderCircleIcon, SaveIcon, TrashIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Switch } from '@/components/ui/switch';

function Delete({ template, onTemplateDeleted }: { template: ServerTemplate; onTemplateDeleted: (template: ServerTemplate) => void }) {
  const [open, setOpen] = useState(false);
  const form = useForm({});

  const submit = () => {
    form.delete(route('server-templates.destroy', { id: template.id }), {
      onSuccess: () => {
        setOpen(false);
        onTemplateDeleted(template);
      },
    });
  };

  return (
    <Dialog modal={true} open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="icon">
          <TrashIcon />
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Delete template</DialogTitle>
          <DialogDescription className="sr-only">Delete template</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to delete this template?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Save({
  template,
  services,
  onChangesSaved,
}: {
  template?: ServerTemplate | null;
  services: Service[];
  onChangesSaved: (template: ServerTemplate) => void;
}) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    id: string | null;
    name: string;
    services: Service[];
    new: boolean;
  }>({
    id: template?.id.toString() || null,
    name: template?.name || '',
    services: services,
    new: template ? false : true,
  });

  useEffect(() => {
    form.setData('id', template?.id.toString() || null);
    form.setData('name', template?.name || '');
    form.setData('services', services);
    form.setData('new', template ? false : true);
  }, [template]);

  const save = () => {
    if (form.data.new) {
      form.post(route('server-templates.store'), {
        onSuccess: () => {
          form.reset();
          setOpen(false);
          onChangesSaved({
            id: parseInt(form.data.id || '0'),
            name: form.data.name,
            services: form.data.services,
          });
        },
      });
      return;
    }

    if (!form.data.id) {
      form.setError('name', 'Select a template to edit');
      return;
    }

    form.put(route('server-templates.update', { id: form.data.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
        onChangesSaved({
          id: parseInt(form.data.id || '0'),
          name: form.data.name,
          services: form.data.services,
        });
      },
    });
  };

  return (
    <Dialog modal={true} open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="icon">
          <SaveIcon />
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Save template</DialogTitle>
          <DialogDescription className="sr-only">Save as template</DialogDescription>
        </DialogHeader>
        <Form className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Template Name</Label>
              <Input id="name" type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-2">
                <Switch id="new" checked={form.data.new} onCheckedChange={(value) => form.setData('new', value)} />
                <Label htmlFor="new">Save as new template</Label>
                <InputError message={form.errors.new} />
              </div>
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button onClick={save} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function ServerTemplates({
  services,
  onTemplateChanged,
}: {
  services: Service[];
  onTemplateChanged: (template: ServerTemplate | null) => void;
}) {
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState<ServerTemplate | null>(null);

  const query = useQuery<ServerTemplate[]>({
    queryKey: ['server-templates'],
    queryFn: async () => {
      return (await axios.get(route('server-templates.index'))).data?.templates || [];
    },
  });

  useEffect(() => {
    if (value) {
      const updatedValue = {
        ...value,
        services: services,
      };
      setValue(updatedValue);
    }
  }, [services]);

  const changesSaved = (template: ServerTemplate) => {
    setValue(template);
    onTemplateChanged(template);
    query.refetch();
  };

  const templateDeleted = (template: ServerTemplate) => {
    if (value?.id === template.id) {
      setValue(null);
      onTemplateChanged(null);
    }
    query.refetch();
  };

  return (
    <div className="inline-flex items-center gap-2">
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button variant="outline" role="combobox" aria-expanded={open} className="w-[200px] justify-between">
            {query.isFetching && 'Loading...'}
            {!query.isFetching && (value ? value.name : 'Select template...')}
            <ChevronsUpDownIcon className="opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-[200px] p-0">
          <Command>
            <CommandInput placeholder="Search template..." className="h-9" />
            <CommandList>
              <CommandEmpty>No template found.</CommandEmpty>
              <CommandGroup>
                {query.data &&
                  query.data.map((template: ServerTemplate) => (
                    <CommandItem
                      key={`template-${template.id}`}
                      value={template.id.toString()}
                      onSelect={(currentValue: string) => {
                        const template = query.data?.find((s) => s.id.toString() === currentValue);
                        if (template) {
                          setValue(template);
                          onTemplateChanged(template);
                        }
                        setOpen(false);
                      }}
                    >
                      {template.name}
                      <Checkbox className="ml-auto" checked={value?.id.toString() === template.id.toString()} />
                    </CommandItem>
                  ))}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
      <Save template={value} services={services} onChangesSaved={changesSaved} />
      {value && <Delete template={value} onTemplateDeleted={templateDeleted} />}
    </div>
  );
}
