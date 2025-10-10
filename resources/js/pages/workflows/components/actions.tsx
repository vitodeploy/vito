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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import { WorkflowAction } from '@/types/workflow-action';
import { useForm } from '@inertiajs/react';
import { InfoIcon, MessageCircleQuestionIcon } from 'lucide-react';
import { ReactNode, useState } from 'react';

interface Props {
  actions: {
    [key: string]: WorkflowAction;
  };
  onActionAdded: (action: WorkflowAction) => void;
}

function Add({ action, onActionAdded, children }: { action: WorkflowAction; onActionAdded: (action: WorkflowAction) => void; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [isStatic, setIsStatic] = useState<{
    [key: string]: boolean;
  }>({});

  const form = useForm({});

  const submit = () => {
    action.data = action.data || {};
    action.id = crypto.randomUUID();
    action.form?.forEach((field: DynamicFieldConfig) => {
      /*@ts-expect-error dynamic types*/
      action.data[field.name] = form.data[field.name] || '';
    });
    onActionAdded(action);
    setOpen(false);
  };

  const toggleStatic = (field: DynamicFieldConfig) => {
    setIsStatic((prev) => ({
      ...prev,
      [field.name]: !prev[field.name],
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
                onChange={(e) => (action.label = e.target.value)}
                autoComplete="off"
              />
            </FormField>
            {action.form?.map((field: DynamicFieldConfig) => (
              <div className="flex w-full items-center justify-around gap-2" key={`field-${field.name}`}>
                <div className="w-full">
                  {isStatic[field.name] ? (
                    <DynamicField
                      /*@ts-expect-error dynamic types*/
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
                      />
                      {field.description && <p className="text-muted-foreground text-xs">{field.description}</p>}
                    </FormField>
                  )}
                </div>
                <div>
                  <Label className="opacity-0">Required</Label>
                  <div className="bg-card text-card-foreground border-input mx-auto inline-flex h-9 w-fit items-center justify-center rounded-lg border px-2">
                    <button
                      onClick={() => toggleStatic(field)}
                      type="button"
                      className={cn('flex h-6 items-center rounded-md px-2', isStatic[field.name] ? 'bg-accent text-accent-foreground shadow' : '')}
                    >
                      Static
                    </button>
                    <button
                      onClick={() => toggleStatic(field)}
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
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function Actions({ actions, onActionAdded }: Props) {
  return (
    <div className="bg-background absolute top-0 right-0 z-10 m-2 h-[300px] w-[200px] rounded-lg border p-3">
      <div className="flex flex-col gap-2">
        <div className="flex items-center justify-between gap-1 border-b pb-2">
          <h3 className="text-muted-foreground">Actions</h3>
          <Tooltip>
            <TooltipTrigger>
              <MessageCircleQuestionIcon className="size-4" />
            </TooltipTrigger>
            <TooltipContent side="bottom" className="mt-2 mr-4 w-[180px]">
              <div>Click on each action to add them to the workflow</div>
            </TooltipContent>
          </Tooltip>
        </div>
        {Object.entries(actions).map(([key, action]) => (
          <Add key={`add-action-${key}`} action={action} onActionAdded={onActionAdded}>
            <div key={`action-${key}`} className="hover:bg-accent cursor-pointer rounded border px-2 py-1">
              <p className="font-normal">{action.label}</p>
              {action.description && <p className="text-muted-foreground text-sm">{action.description}</p>}
            </div>
          </Add>
        ))}
      </div>
    </div>
  );
}
