import { FormEvent, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form, FormField } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/ui/input-error';
import { useAppearance } from '@/hooks/use-appearance';
import { registerBashLanguage } from '@/lib/editor';
import { useInputFocus } from '@/stores/useInputFocus';
import { DeploymentScript } from '@/types/deployment-script';

export default function DeploymentScriptEditor({ open, onOpenChange, url, stage, label, script, inheritedScript, componentOverride }: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  url: string;
  stage: string;
  label: string;
  script?: DeploymentScript;
  inheritedScript?: DeploymentScript;
  componentOverride: boolean;
}) {
  const form = useForm({ stage, content: script?.content ?? inheritedScript?.content ?? '', inherit: componentOverride && !script });
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const monaco = useMonaco();
  useEffect(() => { registerBashLanguage(monaco); }, [monaco]);
  useEffect(() => {
    setFocused(open);
    return () => setFocused(false);
  }, [open, setFocused]);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(url, { preserveScroll: true, onSuccess: () => onOpenChange(false) });
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-5xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>{label}</SheetTitle>
          <SheetDescription>Plain shell in the release directory with environment variables, COMMIT_ID, RELEASE_PATH and BRANCH. Leave empty to skip.</SheetDescription>
        </SheetHeader>
        <Form id="environment-deployment-script" onSubmit={submit} className="min-h-0 flex-1 px-4">
          {componentOverride && <FormField>
            <div className="flex items-center gap-2">
              <Switch id="inherit-script" checked={form.data.inherit} onCheckedChange={(value) => form.setData('inherit', value)} />
              <Label htmlFor="inherit-script">Use environment script</Label>
            </div>
          </FormField>}
          <Editor defaultLanguage="bash" theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
            value={form.data.inherit ? inheritedScript?.content ?? '' : form.data.content}
            onChange={(value) => form.setData('content', value ?? '')} className="min-h-64 flex-1"
            options={{ readOnly: form.data.inherit, ariaLabel: `${label} script`, fontSize: 15 }} />
          <InputError message={form.errors.content ?? form.errors.stage ?? form.errors.inherit} />
        </Form>
        <SheetFooter>
          <Button form="environment-deployment-script" type="submit" disabled={form.processing}>Save script</Button>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
