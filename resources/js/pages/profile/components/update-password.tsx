import InputError from '@/components/ui/input-error';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

export default function UpdatePassword() {
  const passwordInput = useRef<HTMLInputElement>(null);
  const currentPasswordInput = useRef<HTMLInputElement>(null);

  const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const updatePassword: FormEventHandler = (e) => {
    e.preventDefault();

    put(route('profile.password'), {
      preserveScroll: true,
      onSuccess: () => reset(),
      onError: (errors) => {
        if (errors.password) {
          reset('password', 'password_confirmation');
          passwordInput.current?.focus();
        }

        if (errors.current_password) {
          reset('current_password');
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
        <Form id="update-password-form" onSubmit={updatePassword}>
          <FormFields>
            <FormField>
              <Label htmlFor="current_password">Current password</Label>
              <Input
                id="current_password"
                ref={currentPasswordInput}
                value={data.current_password}
                onChange={(e) => setData('current_password', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="current-password"
                placeholder="Current password"
              />
              <InputError message={errors.current_password} />
            </FormField>
            <FormField>
              <Label htmlFor="password">New password</Label>
              <Input
                id="password"
                ref={passwordInput}
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="New password"
              />
              <InputError message={errors.password} />
            </FormField>
            <FormField>
              <Label htmlFor="password_confirmation">Confirm password</Label>
              <Input
                id="password_confirmation"
                value={data.password_confirmation}
                onChange={(e) => setData('password_confirmation', e.target.value)}
                type="password"
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="Confirm password"
              />
              <InputError message={errors.password_confirmation} />
            </FormField>
          </FormFields>
        </Form>
      </CardContent>
      <CardFooter className="gap-2">
        <Button form="update-password-form" disabled={processing}>
          {processing && <LoaderCircleIcon className="animate-spin" />}
          <FormSuccessful successful={recentlySuccessful} />
          Save password
        </Button>
      </CardFooter>
    </Card>
  );
}
