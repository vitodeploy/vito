import { useForm, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { FormEventHandler, ReactNode, useState } from 'react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2Icon, LoaderCircleIcon, XCircleIcon } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';

function Disable(): ReactNode {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('profile.disable-two-factor'), {
      preserveScroll: true,
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="destructive">Disable Two Factor</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Disable two factor</DialogTitle>
          <DialogDescription className="sr-only">Disable two factor</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to enable two factor authentication?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button onClick={submit} variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Disable
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Enable() {
  const form = useForm();

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('profile.enable-two-factor'));
  };

  return (
    <Button onClick={submit} disabled={form.processing}>
      {form.processing && <LoaderCircleIcon className="animate-spin" />}
      Enable Two Factor
    </Button>
  );
}

export default function TwoFactor() {
  const page = usePage<
    SharedData & {
      flash: {
        data?: {
          qr_code?: string;
          qr_code_url?: string;
          recovery_codes?: string[];
        };
      };
    }
  >();

  return (
    <Card>
      <CardHeader>
        <CardTitle>Two factor authentication</CardTitle>
        <CardDescription>Enable or Disable two factor authentication</CardDescription>
      </CardHeader>
      <CardContent className="space-y-2 p-4">
        {page.props.flash.data?.qr_code && (
          <FormFields>
            <FormField>
              <Label htmlFor="qr-code">Scan this QR code with your authenticator app</Label>
              <div className="flex max-h-[400px] items-center">
                <div dangerouslySetInnerHTML={{ __html: page.props.flash.data.qr_code }}></div>
              </div>
            </FormField>
            <FormField>
              <Label htmlFor="qr-code-url">QR Code URL</Label>
              <Input id="qr-code-url" value={page.props.flash.data.qr_code_url} disabled />
            </FormField>
            <FormField>
              <Label htmlFor="recovery-codes">Recovery Codes</Label>
              <Textarea id="recovery-codes" value={page.props.flash.data.recovery_codes?.join('\n') || ''} disabled rows={5} />
            </FormField>
          </FormFields>
        )}
        {page.props.auth.user.two_factor_enabled ? (
          <Alert>
            <AlertDescription>
              <div className="flex items-center gap-2">
                <CheckCircle2Icon className="text-success size-4" />
                <p>Two factor authentication is enabled</p>
              </div>
            </AlertDescription>
          </Alert>
        ) : (
          <Alert>
            <AlertDescription>
              <div className="flex items-center gap-2">
                <XCircleIcon className="text-danger size-4" />
                Two factor authentication is <strong>not</strong> enabled
              </div>
            </AlertDescription>
          </Alert>
        )}
      </CardContent>
      <CardFooter className="gap-2">
        {!page.props.auth.user.two_factor_enabled && <Enable />}
        {page.props.auth.user.two_factor_enabled && <Disable />}
      </CardFooter>
    </Card>
  );
}
