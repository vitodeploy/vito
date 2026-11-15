import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent, useState } from 'react';

export type ConfirmPasswordDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirmed: () => void;
};

export default function ConfirmPasswordDialog({ open, onOpenChange, onConfirmed }: ConfirmPasswordDialogProps) {
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string>();
  const [processing, setProcessing] = useState(false);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (processing) {
      return;
    }
    setProcessing(true);
    setError(undefined);
    axios
      .post('/user/confirm-password', { password })
      .then(() => {
        onOpenChange(false);
        onConfirmed();
      })
      .catch((err: unknown) => {
        const data = axios.isAxiosError(err) ? err.response?.data : undefined;
        const message = data?.errors?.password?.[0] ?? data?.message;
        setError(typeof message === 'string' ? message : 'Could not confirm your password.');
        setProcessing(false);
      });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Confirm password</DialogTitle>
          <DialogDescription>For your security, please confirm your password to continue.</DialogDescription>
        </DialogHeader>
        <Form id="confirm-password-dialog-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="confirm-password">Password</Label>
              <Input
                id="confirm-password"
                type="password"
                autoComplete="current-password"
                autoFocus
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
              <InputError message={error} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="confirm-password-dialog-form" type="submit" disabled={processing}>
            {processing && <LoaderCircleIcon className="animate-spin" />}
            Confirm
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
