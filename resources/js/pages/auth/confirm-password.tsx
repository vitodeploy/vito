import { Head, useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/layout';

export default function ConfirmPassword() {
  const form = useForm<{ password: string }>({
    password: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();

    form.post('/user/confirm-password', {
      onFinish: () => form.reset('password'),
    });
  };

  return (
    <AuthLayout title="Confirm your password" description="This is a secure area of the application. Please confirm your password before continuing.">
      <Head title="Confirm password" />

      <form id="confirm-password-form" onSubmit={submit}>
        <div className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              name="password"
              required
              placeholder="Password"
              autoComplete="current-password"
              value={form.data.password}
              autoFocus
              onChange={(e) => form.setData('password', e.target.value)}
            />
            <InputError message={form.errors.password} />
          </div>

          <div className="flex items-center">
            <Button className="w-full" disabled={form.processing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Confirm password
            </Button>
          </div>
        </div>
      </form>
    </AuthLayout>
  );
}
