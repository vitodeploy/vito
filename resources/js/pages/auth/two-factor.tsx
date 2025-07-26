import { Head, useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth/layout';
import TextLink from '@/components/text-link';

export default function TwoFactor() {
  const form = useForm<{ code: string; recovery_code: string }>({
    code: '',
    recovery_code: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();

    form.post('/two-factor-challenge', {
      onFinish: () => form.reset(),
    });
  };

  return (
    <AuthLayout title="Two factor challenge" description="Please enter the two-factor authentication code to continue.">
      <Head title="Confirm password" />

      <form id="two-factor-form" onSubmit={submit}>
        <div className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor="code">Code</Label>
            <Input
              id="code"
              type="text"
              name="code"
              placeholder="Two factor code"
              value={form.data.code}
              autoFocus
              tabIndex={1}
              onChange={(e) => form.setData('code', e.target.value)}
            />

            <InputError message={form.errors.code} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="recovery_code">Recovery Code</Label>
            <Input
              id="recovery_code"
              type="text"
              name="recovery_code"
              placeholder="Or enter your recovery code"
              tabIndex={3}
              value={form.data.recovery_code}
              onChange={(e) => form.setData('recovery_code', e.target.value)}
            />

            <InputError message={form.errors.recovery_code} />
          </div>

          <Button type="submit" className="mt-2 w-full" tabIndex={5} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Confirm
          </Button>
        </div>

        <div className="text-muted-foreground mt-4 text-center text-sm">
          <TextLink href="/login" tabIndex={6}>
            Back to login
          </TextLink>
        </div>
      </form>
    </AuthLayout>
  );
}
