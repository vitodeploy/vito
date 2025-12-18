import { FormEvent, ReactNode, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { registerBashLanguage } from '@/lib/editor';
import { Editor, useMonaco } from '@monaco-editor/react';
import { useAppearance } from '@/hooks/use-appearance';
import { Script } from '@/types/script';
import { useInputFocus } from '@/stores/useInputFocus';

export default function ScriptForm({ script, children }: { script?: Script; children: ReactNode }) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);

  const [open, setOpen] = useState(false);

  const form = useForm<{
    name: string;
    content: string;
  }>({
    name: script?.name ?? '',
    content: script?.content ?? '',
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (script) {
      form.put(route('scripts.update', { script: script.id }), {
        onSuccess: () => {
          handleOpenChange(false);
        },
      });
      return;
    }

    form.post(route('scripts'), {
      onSuccess: () => {
        handleOpenChange(false);
      },
    });
  };

  registerBashLanguage(useMonaco());

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>{script ? 'Edit' : 'Create'} script</SheetTitle>
          <SheetDescription className="sr-only">{script ? 'Edit' : 'Create'} script</SheetDescription>
        </SheetHeader>
        <Form id="script-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>

            <FormField>
              <Label htmlFor="script">Script</Label>
              <div className="overflow-hidden rounded-md border">
                <Editor
                  defaultLanguage="bash"
                  defaultValue={form.data.content}
                  theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
                  className="h-[500px]"
                  onChange={(value) => form.setData('content', value ?? '')}
                  options={{
                    fontSize: 15,
                    minimap: {
                      enabled: false,
                    },
                    lineNumbers: 'off',
                    padding: {
                      top: 10,
                      bottom: 10,
                    },
                  }}
                />
              </div>
              <p className="text-muted-foreground text-sm">
                You can use variables like {'${VARIABLE_NAME}'} in the script. The variables will be asked when executing the script
              </p>
              <InputError message={form.errors.content} />
            </FormField>
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="script-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              {script ? 'Save' : 'Create'}
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
