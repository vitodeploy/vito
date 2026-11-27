import { FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Form, FormField } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useInputFocus } from '@/stores/useInputFocus';
import { WorkflowAction, WorkflowInputs, WorkflowVariable } from '@/types/workflow-action';
import InputsForm, { cleanInputs } from './inputs-form';

export default function ActionForm({
  open,
  onOpenChange,
  action,
  variables = [],
  mode,
  onSubmit,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  action: WorkflowAction;
  variables?: WorkflowVariable[];
  mode: 'add' | 'edit';
  onSubmit: (action: WorkflowAction) => void;
}) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const [label, setLabel] = useState(action.label);
  const [inputs, setInputs] = useState<WorkflowInputs>(Array.isArray(action.inputs) ? {} : (action.inputs ?? {}));
  const [invalid, setInvalid] = useState(false);

  useEffect(() => {
    setFocused(open);
    return () => setFocused(false);
  }, [open, setFocused]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (invalid) {
      toast.error('Inputs contain invalid JSON.');
      return;
    }
    onSubmit({
      id: mode === 'add' ? crypto.randomUUID() : action.id,
      label: label.trim() || action.label,
      handler: action.handler,
      starting: action.starting ?? false,
      outputs: action.outputs,
      inputs: cleanInputs(inputs, action.fields),
    });
    onOpenChange(false);
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-3xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>{mode === 'add' ? `Add ${action.label}` : `Edit ${action.label}`}</SheetTitle>
          <SheetDescription>{action.description || 'Configure the inputs of this action.'}</SheetDescription>
        </SheetHeader>
        <Form id="workflow-action-form" onSubmit={submit} className="overflow-y-auto p-4">
          <FormField>
            <Label htmlFor="workflow-action-label">Label</Label>
            <Input id="workflow-action-label" value={label} onChange={(e) => setLabel(e.target.value)} autoComplete="off" />
          </FormField>
          <InputsForm fields={action.fields ?? []} inputs={inputs} onChange={setInputs} onInvalidChange={setInvalid} variables={variables} />
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="workflow-action-form" type="submit">
              {mode === 'add' ? 'Add Action' : 'Save Changes'}
            </Button>
            <SheetClose asChild>
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
