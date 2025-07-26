import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/layout';

export default function ResetPassword() {
  const page = usePage<{
    email: string;
    token: string;
  }>();

  const form = useForm<{
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }>({
    token: page.props.token,
    email: page.props.email,
    password: '',
    password_confirmation: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/reset-password', {
      onFinish: () => form.reset('password', 'password_confirmation'),
    });
  };

  return (
    <AuthLayout title="Reset password" description="Please enter your new password below">
      <Head title="Reset password" />

      <form id="reset-password-form" onSubmit={submit}>
        <div className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              name="email"
              autoComplete="email"
              value={form.data.email}
              className="mt-1 block w-full"
              readOnly
              onChange={(e) => form.setData('email', e.target.value)}
            />
            <InputError message={form.errors.email} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              required
              name="password"
              autoComplete="new-password"
              value={form.data.password}
              className="mt-1 block w-full"
              autoFocus
              onChange={(e) => form.setData('password', e.target.value)}
              placeholder="Password"
            />
            <InputError message={form.errors.password} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password_confirmation">Confirm password</Label>
            <Input
              id="password_confirmation"
              type="password"
              required
              name="password_confirmation"
              autoComplete="new-password"
              value={form.data.password_confirmation}
              className="mt-1 block w-full"
              onChange={(e) => form.setData('password_confirmation', e.target.value)}
              placeholder="Confirm password"
            />
            <InputError message={form.errors.password_confirmation} />
          </div>

          <Button type="submit" className="mt-4 w-full" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Reset password
          </Button>
        </div>
      </form>
    </AuthLayout>
  );
}
