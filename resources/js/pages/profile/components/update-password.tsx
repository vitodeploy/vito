import InputError from '@/components/ui/input-error';
import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { SharedData } from '@/types';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function UpdatePassword() {
  const page = usePage<SharedData>();
  const passwordInput = useRef<HTMLInputElement>(null);
  const currentPasswordInput = useRef<HTMLInputElement>(null);

  const form = useForm<{
    current_password: string;
    password: string;
    password_confirmation: string;
  }>({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const updatePassword = (e: FormEvent) => {
    e.preventDefault();

    form.put('/user/password', {
      preserveScroll: true,
      errorBag: 'updatePassword',
      onSuccess: () => form.reset(),
      onError: (errors) => {
        if (errors.password) {
          form.reset('password', 'password_confirmation');
          passwordInput.current?.focus();
        }

        if (errors.current_password) {
          form.reset('current_password');
          currentPasswordInput.current?.focus();
        }
      },
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Update password</CardTitle>
        <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
      </CardHeader>
      <CardContent className="p-4">
        {page.props.auth.user.two_factor_enabled && (
          <Alert className="mb-4">
            <TriangleAlertIcon size={5} />
            <AlertDescription>
              Resetting your password will disable two-factor authentication. You will need to set it up again after changing your password.
            </AlertDescription>
          </Alert>
        )}
        <form id="update-password-form" onSubmit={updatePassword}>
          <div className="grid gap-6">
            <div className="grid gap-2">
              <Label htmlFor="current_password">Current password</Label>
              <Input
                id="current_password"
                ref={currentPasswordInput}
                value={form.data.current_password}
                onChange={(e) => form.setData('current_password', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="current-password"
                placeholder="Current password"
              />
              <InputError message={form.errors.current_password} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="password">New password</Label>
              <Input
                id="password"
                ref={passwordInput}
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="New password"
              />
              <InputError message={form.errors.password} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="password_confirmation">Confirm password</Label>
              <Input
                id="password_confirmation"
                value={form.data.password_confirmation}
                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="Confirm password"
              />
              <InputError message={form.errors.password_confirmation} />
            </div>
          </div>
        </form>
      </CardContent>
      <CardFooter className="gap-2">
        <Button form="update-password-form" disabled={form.processing}>
          {form.processing && <LoaderCircleIcon className="animate-spin" />}
          <FormSuccessful successful={form.recentlySuccessful} />
          Save password
        </Button>
      </CardFooter>
    </Card>
  );
}
