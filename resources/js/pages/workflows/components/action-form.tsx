import { Button } from '@/components/ui/button';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WorkflowAction } from '@/types/workflow-action';
import { useForm } from '@inertiajs/react';
import { Editor } from '@monaco-editor/react';
import { FormEvent, ReactNode, useState } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import { useInputFocus } from '@/stores/useInputFocus';

export default function ActionForm({
  action,
  onActionChanged,
  connectedActions,
  type = 'add',
  children,
}: {
  action: WorkflowAction;
  connectedActions?: WorkflowAction[];
  onActionChanged: (action: WorkflowAction) => void;
  type: 'add' | 'edit';
  children: ReactNode;
}) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);
  const form = useForm({
    label: action.label,
    inputs: JSON.stringify(Array.isArray(action.inputs) && action.inputs.length === 0 ? {} : action.inputs || {}, null, 2),
  });
  const { getActualAppearance } = useAppearance();

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    reformatJson();
    const newAction = { ...action };
    newAction.inputs = newAction.inputs || {};
    newAction.label = form.data.label?.toString() || action.label;
    newAction.id = crypto.randomUUID();
    const parsedInputs = JSON.parse(form.data.inputs || '{}');
    newAction.inputs = Array.isArray(parsedInputs) && parsedInputs.length === 0 ? {} : parsedInputs;
    onActionChanged({ ...newAction });
    handleOpenChange(false);
  };

  const reformatJson = () => {
    try {
      const parsed = JSON.parse(form.data.inputs || '{}');
      const normalizedInputs = Array.isArray(parsed) && parsed.length === 0 ? {} : parsed;
      const reformatted = JSON.stringify(normalizedInputs, null, 2);
      form.setData('inputs', reformatted);
    } catch (e) {
      toast.error('Invalid JSON format. Please correct it before reformatting.');
      throw e;
    }
  };

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>Add [{action.label}]</SheetTitle>
          <SheetDescription className="sr-only">Add [{action.label}] action</SheetDescription>
        </SheetHeader>
        <Form id="action-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="label">Action Label</Label>
              <Input
                type="text"
                name="label"
                id="label"
                defaultValue={action.label}
                onChange={(e) => form.setData('label', e.target.value)}
                autoComplete="off"
              />
            </FormField>
            <FormField>
              <Label htmlFor="inputs">Action Inputs (JSON)</Label>
              <Editor
                defaultLanguage="json"
                value={form.data.inputs}
                theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
                className="h-[400px]"
                onChange={(value) => form.setData('inputs', value ?? '')}
                options={{
                  fontSize: 15,
                  minimap: { enabled: false },
                  wordWrap: 'on',
                }}
              />
            </FormField>
            {connectedActions && connectedActions.length > 0 && (
              <Alert>
                <AlertDescription>
                  The following are outputs from the previous action that can be used as dynamic inputs for this action.
                  <br />
                  {connectedActions.map(({ outputs }) => outputs && Object.entries(outputs).map(([key]) => <span>{`{${key}}`}</span>))}
                  <br />
                  Example usage: {`{"server_id": "{server_id}"}`}
                </AlertDescription>
              </Alert>
            )}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="action-form" type="button" onClick={submit}>
              {type === 'add' ? 'Add Action' : 'Save Changes'}
            </Button>
            <SheetClose asChild>
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </SheetClose>
            <Button type="button" variant="outline" onClick={reformatJson}>
              Reformat JSON
            </Button>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
