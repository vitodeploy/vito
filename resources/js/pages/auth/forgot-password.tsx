import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2Icon, LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { SharedData } from '@/types';

export default function ForgotPassword() {
  const page = usePage<SharedData>();
  const form = useForm<{ email: string }>({
    email: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();

    form.post('/forgot-password');
  };

  return (
    <AuthLayout title="Forgot password" description="Enter your email to receive a password reset link">
      <Head title="Forgot password" />

      {page.props.flash?.status && (
        <Alert className="mb-4">
          <AlertDescription className="flex items-center gap-2">
            <CheckCircle2Icon className="text-success size-4" />
            {page.props.flash?.status}
          </AlertDescription>
        </Alert>
      )}

      <form id="forget-password-form" onSubmit={submit}>
        <div className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor="email">Email address</Label>
            <Input
              id="email"
              type="email"
              required
              name="email"
              value={form.data.email}
              autoFocus
              onChange={(e) => form.setData('email', e.target.value)}
              placeholder="email@example.com"
            />
            <InputError message={form.errors.email} />
          </div>

          <div className="flex items-center justify-start">
            <Button className="w-full" disabled={form.processing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Email password reset link
            </Button>
          </div>
        </div>

        <div className="text-muted-foreground mt-4 space-x-1 text-center text-sm">
          <span>Or, return to</span>
          <TextLink href="/login">log in</TextLink>
        </div>
      </form>
    </AuthLayout>
  );
}
