import { useForm, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { FormEvent, ReactNode, useState } from 'react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import InputError from '@/components/ui/input-error';
import axios from 'axios';

function Enable({ show = true }: { show?: boolean }): ReactNode {
  const page = usePage<
    SharedData & {
      two_factor_must_confirm: boolean;
    }
  >();
  const enableForm = useForm();
  const confirmForm = useForm({
    code: '',
  });
  const [open, setOpen] = useState(false);
  const [qrCode, setQrCode] = useState<string>('');
  const [qrCodeUrl, setQrCodeUrl] = useState<string>('');

  const submit = (e: FormEvent) => {
    e.preventDefault();
    enableForm.post('/user/two-factor-authentication', {
      onSuccess: () => {
        axios.get('/user/two-factor-qr-code').then((response) => {
          setQrCode(response.data.svg);
          setQrCodeUrl(response.data.url);
        });
        setOpen(true);
      },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const confirm = (e: FormEvent) => {
    e.preventDefault();
    confirmForm.post('/user/confirmed-two-factor-authentication', {
      onSuccess: () => setOpen(false),
      preserveScroll: true,
      errorBag: 'confirmTwoFactorAuthentication',
    });
  };

  return (
    <>
      {show && (
        <Button onClick={submit} disabled={enableForm.processing}>
          {enableForm.processing && <LoaderCircleIcon className="animate-spin" />}
          Enable Two Factor
        </Button>
      )}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Enable Two Factor</DialogTitle>
            <DialogDescription>Enabling two factor authentication</DialogDescription>
          </DialogHeader>
          <form id="confirm-form" onSubmit={confirm} className="p-4">
            <div className="grid gap-6">
              <div className="grid gap-2">
                <Label htmlFor="qr-code">Scan this QR code with your authenticator app</Label>
                <div className="my-3 flex max-h-[400px] items-center justify-center">
                  <div dangerouslySetInnerHTML={{ __html: qrCode }}></div>
                </div>
              </div>
              <div className="grid gap-2">
                <Label htmlFor="qr-code-url">QR Code URL</Label>
                <Input id="qr-code-url" value={qrCodeUrl} disabled />
              </div>
              {page.props.two_factor_must_confirm && (
                <div className="grid gap-2">
                  <Label htmlFor="code">Confirmation Code</Label>
                  <Input
                    id="code"
                    type="text"
                    name="code"
                    placeholder="Enter the confirmation code"
                    autoFocus
                    value={confirmForm.data.code}
                    onChange={(e) => confirmForm.setData('code', e.target.value)}
                  />
                  <InputError message={confirmForm.errors.code} />
                </div>
              )}
            </div>
          </form>
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Close</Button>
            </DialogClose>
            {page.props.two_factor_must_confirm && (
              <Button form="confirm-form" disabled={confirmForm.processing}>
                {confirmForm.processing && <LoaderCircleIcon className="animate-spin" />}
                Confirm
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}

function Disable(): ReactNode {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete('/user/two-factor-authentication', {
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
          <DialogDescription>Disable two factor</DialogDescription>
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

function Regenerate(): ReactNode {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post('/user/two-factor-recovery-codes', {
      preserveScroll: true,
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>Regenerate recovery codes</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Regenerate recovery codes</DialogTitle>
          <DialogDescription>Regenerate recovery codes</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to regenerate the recovery codes?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Regenerate
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function TwoFactor() {
  const page = usePage<
    SharedData & {
      two_factor_enabled: boolean;
      two_factor_recovery_codes: string[];
      two_factor_confirmed_at: string;
      two_factor_must_confirm: boolean;
    }
  >();

  const isEnabled = (): boolean => {
    if (page.props.two_factor_must_confirm) {
      return page.props.two_factor_enabled && page.props.two_factor_confirmed_at != null;
    }

    return page.props.two_factor_enabled;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Two factor authentication</CardTitle>
        <CardDescription>Enable or Disable two factor authentication</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 p-4">
        {isEnabled() ? (
          <>
            <Alert>
              <AlertDescription>
                <div className="flex items-center gap-2">
                  <CheckCircle2Icon className="text-success size-4" />
                  <p>Two factor authentication is enabled</p>
                </div>
              </AlertDescription>
            </Alert>
            {page.props.two_factor_recovery_codes.length > 0 && (
              <div className="grid gap-6">
                <div className="grid gap-2">
                  <Label htmlFor="recovery-codes">Recovery Codes</Label>
                  <Textarea id="recovery-codes" value={page.props.two_factor_recovery_codes?.join('\n') || ''} disabled rows={5} />
                </div>
              </div>
            )}
          </>
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
        <Enable show={!isEnabled()} />
        {isEnabled() && (
          <>
            <Regenerate />
            <Disable />
          </>
        )}
      </CardFooter>
    </Card>
  );
}
