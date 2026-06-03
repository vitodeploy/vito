import { FormEvent } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Textarea } from '@/components/ui/textarea';

type ActivateServerSslDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  sslId: number;
};

export default function ActivateServerSslDialog({ open, onOpenChange, serverId, sslId }: ActivateServerSslDialogProps) {
  const form = useForm<{ certificate: string; ca: string }>({
    certificate: '',
    ca: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('server-ssls.activate', { server: serverId, ssl: sslId }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="w-full lg:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>Activate SSL</SheetTitle>
          <SheetDescription>Install a signed certificate from your Certificate Authority.</SheetDescription>
        </SheetHeader>
        <Form id="activate-server-ssl-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="certificate">Certificate (PEM)</Label>
              <Textarea
                id="certificate"
                name="certificate"
                placeholder="-----BEGIN CERTIFICATE-----"
                rows={6}
                className="field-sizing-fixed overflow-y-auto"
                value={form.data.certificate}
                onChange={(e) => form.setData('certificate', e.target.value)}
              />
              <InputError message={form.errors.certificate} />
            </FormField>
            <FormField>
              <Label htmlFor="ca">CA Bundle (optional)</Label>
              <Textarea
                id="ca"
                name="ca"
                placeholder="-----BEGIN CERTIFICATE-----"
                rows={6}
                className="field-sizing-fixed overflow-y-auto"
                value={form.data.ca}
                onChange={(e) => form.setData('ca', e.target.value)}
              />
              <InputError message={form.errors.ca} />
            </FormField>
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="activate-server-ssl-form" disabled={form.processing}>
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />} Activate
            </Button>
            <SheetClose asChild>
              <Button variant="outline" disabled={form.processing}>
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
