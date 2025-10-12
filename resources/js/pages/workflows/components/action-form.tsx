import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import DynamicField from '@/components/ui/dynamic-field';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import { WorkflowAction } from '@/types/workflow-action';
import { useForm } from '@inertiajs/react';
import { InfoIcon } from 'lucide-react';
import { ReactNode, useState, useEffect } from 'react';

export default function ActionForm({
  action,
  connectedActions,
  onActionChanged,
  type = 'add',
  children,
}: {
  action: WorkflowAction;
  connectedActions?: WorkflowAction[];
  onActionChanged: (action: WorkflowAction) => void;
  type: 'add' | 'edit';
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);
  const [isStatic, setIsStatic] = useState<{
    [key: string]: boolean;
  }>({});

  const form = useForm(action.data || {});

  useEffect(() => {
    if (action.form) {
      const newStaticState: { [key: string]: boolean } = {};

      action.form.forEach((field: DynamicFieldConfig) => {
        const value = form.data[field.name] || '';
        const hasCurlyBraces = typeof value === 'string' && value.includes('{') && value.includes('}');
        newStaticState[field.name] = !hasCurlyBraces;
      });

      setIsStatic((prev) => {
        const hasChanges = Object.keys(newStaticState).some((key) => newStaticState[key] !== prev[key]);
        return hasChanges ? { ...prev, ...newStaticState } : prev;
      });
    }
  }, [open]);

  const submit = () => {
    const newAction = { ...action };
    newAction.data = newAction.data || {};
    newAction.label = form.data.label?.toString() || action.label;
    newAction.id = crypto.randomUUID();
    newAction.form?.forEach((field: DynamicFieldConfig) => {
      /*@ts-expect-error dynamic types*/
      newAction.data[field.name] = form.data[field.name] || '';
    });
    onActionChanged({ ...newAction });
    setOpen(false);
  };

  const toggleStatic = (field: DynamicFieldConfig, type: string) => {
    setIsStatic((prev) => ({
      ...prev,
      [field.name]: type === 'static',
    }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Add [{action.label}]</DialogTitle>
          <DialogDescription className="sr-only">Add [{action.label}] action</DialogDescription>
        </DialogHeader>
        <Form id="action-form" onSubmit={submit} className="p-4">
          <Alert>
            <InfoIcon />
            <AlertDescription>
              Static fields must be filled when adding the action to the workflow.
              <br /> Dynamic fields will be filled from the previous action's output.
            </AlertDescription>
          </Alert>
          <Alert>
            <InfoIcon />
            <AlertDescription>
              Dynamic fields must be inside {`{}`}. For example if output of the previous action has server_id, you can use that output as input of a
              field like {`{server_id}`}
            </AlertDescription>
          </Alert>
          <FormFields>
            <FormField>
              <Label htmlFor="label">Action Label</Label>
              <Input
                type="text"
                name="label"
                id="label"
                defaultValue={action.label}
                /*@ts-expect-error dynamic types*/
                onChange={(e) => form.setData('label', e.target.value)}
                autoComplete="off"
              />
            </FormField>
            {action.form?.map((field: DynamicFieldConfig) => (
              <div className="flex w-full items-center justify-around gap-2" key={`field-${field.name}`}>
                <div className="w-full">
                  {isStatic[field.name] ? (
                    <DynamicField
                      value={form.data[field.name]}
                      /*@ts-expect-error dynamic types*/
                      onChange={(value) => form.setData(field.name, value)}
                      config={field}
                      /*@ts-expect-error dynamic types*/
                      error={form.errors[field.name]}
                    />
                  ) : (
                    <FormField>
                      <Label htmlFor={`field-${field.name}`} className="capitalize">
                        {field.label}
                      </Label>
                      <Input
                        type="text"
                        name={field.name}
                        id={`field-${field.name}`}
                        /*@ts-expect-error dynamic types*/
                        value={form.data[field.name] || ''}
                        /*@ts-expect-error dynamic types*/
                        onChange={(e) => form.setData(field.name, e.target.value)}
                        placeholder={`{${field.name}}`}
                        /*@ts-expect-error dynamic types*/
                        error={form.errors[field.name]}
                        autoComplete="off"
                        list={`${field.name}-examples`}
                      />
                      <datalist id={`${field.name}-examples`}>
                        {connectedActions &&
                          connectedActions.map(
                            ({ outputs }) =>
                              outputs && Object.entries(outputs).map(([key]) => <option key={`option-${field.name}-${key}`} value={`{${key}}`} />),
                          )}
                      </datalist>
                      {field.description && <p className="text-muted-foreground text-xs">{field.description}</p>}
                    </FormField>
                  )}
                </div>
                <div>
                  <Label className="opacity-0">Required</Label>
                  <div className="bg-card text-card-foreground border-input mx-auto inline-flex h-9 w-fit items-center justify-center rounded-lg border px-2">
                    <button
                      onClick={() => toggleStatic(field, 'static')}
                      type="button"
                      className={cn('flex h-6 items-center rounded-md px-2', isStatic[field.name] ? 'bg-accent text-accent-foreground shadow' : '')}
                    >
                      Static
                    </button>
                    <button
                      onClick={() => toggleStatic(field, 'dynamic')}
                      type="button"
                      className={cn('flex h-6 items-center rounded-md px-2', !isStatic[field.name] ? 'bg-accent text-accent-foreground shadow' : '')}
                    >
                      Dynamic
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="action-form" type="button" onClick={submit}>
            {type === 'add' ? 'Add Action' : 'Save Changes'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
