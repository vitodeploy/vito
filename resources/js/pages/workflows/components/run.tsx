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
import { Form, FormFields } from '@/components/ui/form';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import { Workflow } from '@/types/workflow';
import { useForm } from '@inertiajs/react';
import { ReactNode, useEffect, useState } from 'react';

export default function Run({ workflow, children }: { workflow: Workflow; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm(workflow.run_inputs);

  const submit = () => {
    //
  };

  useEffect(() => {
    form.setData(workflow.run_inputs || {});
  }, [open]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Run workflow [{workflow.name}]</DialogTitle>
          <DialogDescription className="sr-only">Run workflow [{workflow.name}]</DialogDescription>
        </DialogHeader>
        <Form id="run-workflow-form" onSubmit={submit} className="p-4">
          <FormFields>
            {workflow.run_form?.map((field: DynamicFieldConfig) => (
              <div className="flex w-full items-center justify-around gap-2" key={`field-${field.name}`}>
                <div className="w-full">
                  <DynamicField
                    value={form.data[field.name]}
                    /*@ts-expect-error dynamic types*/
                    onChange={(value) => form.setData(field.name, value)}
                    config={field}
                    /*@ts-expect-error dynamic types*/
                    error={form.errors[field.name]}
                  />
                </div>
              </div>
            ))}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button>Run</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
