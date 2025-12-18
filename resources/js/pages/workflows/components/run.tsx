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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useAppearance } from '@/hooks/use-appearance';
import { Workflow } from '@/types/workflow';
import { useForm } from '@inertiajs/react';
import { Editor } from '@monaco-editor/react';
import { LoaderCircleIcon } from 'lucide-react';
import { ReactNode, useState } from 'react';
import { toast } from 'sonner';
import { useInputFocus } from '@/stores/useInputFocus';

export default function Run({ workflow, children }: { workflow: Workflow; children: ReactNode }) {
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);

  const form = useForm<{
    inputs: Record<string, string>;
    verbose: boolean;
  }>({
    inputs: workflow.run_inputs || {},
    verbose: false,
  });
  const { getActualAppearance } = useAppearance();

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = () => {
    validateInputs();
    form.post(route('workflow-runs.store', { workflow: workflow.id }));
  };

  const validateInputs = () => {
    try {
      const reformatted = JSON.stringify(form.data.inputs, null, 2);
      JSON.parse(reformatted);
    } catch (e) {
      toast.error('Invalid JSON format. Please correct it before reformatting.');
      throw e;
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Run workflow [{workflow.name}]</DialogTitle>
          <DialogDescription className="sr-only">Run workflow [{workflow.name}]</DialogDescription>
        </DialogHeader>
        <Form id="run-workflow-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="inputs">Action Inputs (JSON)</Label>
              <Editor
                defaultLanguage="json"
                value={form.data.inputs ? JSON.stringify(form.data.inputs, null, 2) : '{}'}
                theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
                className="h-[400px]"
                onChange={(value) => form.setData('inputs', JSON.parse(value || '{}'))}
                options={{
                  fontSize: 15,
                  minimap: { enabled: false },
                }}
              />
            </FormField>
            <FormField>
              <Label htmlFor="verbose">Verbose Output</Label>
              <Switch id="verbose" checked={form.data.verbose} onCheckedChange={(checked) => form.setData('verbose', checked)} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="run-workflow-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Run
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
