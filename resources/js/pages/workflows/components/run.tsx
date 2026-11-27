import { FormEvent, useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/ui/input-error';
import { useInputFocus } from '@/stores/useInputFocus';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import { WorkflowInputs } from '@/types/workflow-action';
import InputsForm, { cleanInputs } from './inputs-form';

export default function Run({
  open,
  onOpenChange,
  workflowId,
  workflowName,
  inputs,
  fields = [],
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  workflowId: number;
  workflowName: string;
  inputs: WorkflowInputs;
  fields?: DynamicFieldConfig[];
}) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const [runInputs, setRunInputs] = useState<WorkflowInputs>(!inputs || Array.isArray(inputs) ? {} : inputs);
  const [invalid, setInvalid] = useState(false);
  const form = useForm<{ verbose: boolean; inputs?: WorkflowInputs }>({ verbose: false });

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
    form.transform((data) => ({ ...data, inputs: cleanInputs(runInputs, fields) }));
    form.post(`/workflows/${workflowId}/runs`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-3xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Run {workflowName}</DialogTitle>
          <DialogDescription>Review the starting action's inputs, then run the workflow.</DialogDescription>
        </DialogHeader>
        <Form id="run-workflow-form" onSubmit={submit} className="max-h-[70vh] overflow-y-auto p-4">
          <InputsForm fields={fields} inputs={runInputs} onChange={setRunInputs} onInvalidChange={setInvalid} />
          <InputError message={form.errors.inputs} />
          <FormField>
            <div className="flex items-center gap-2">
              <Switch id="verbose" checked={form.data.verbose} onCheckedChange={(checked) => form.setData('verbose', checked)} />
              <Label htmlFor="verbose">Verbose output</Label>
            </div>
          </FormField>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="run-workflow-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Run
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
