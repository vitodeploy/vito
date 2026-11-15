import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { FormEvent } from 'react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

export default function UpdateProfile() {
  const page = usePage<
    SharedData & {
      must_verify_email: boolean;
    }
  >();

  const form = useForm<{
    name: string;
    email: string;
    current_password: string;
  }>({
    name: page.props.auth.user.name,
    email: page.props.auth.user.email,
    current_password: '',
  });

  const emailChanged = form.data.email !== page.props.auth.user.email;

  const submit = (e: FormEvent) => {
    e.preventDefault();

    form.put('/user/profile-information', {
      preserveScroll: true,
      errorBag: 'updateProfileInformation',
      onSuccess: () => form.reset('current_password'),
      onError: (errors) => errors.current_password && form.reset('current_password'),
    });
  };
  return (
    <Card>
      <CardHeader>
        <CardTitle>Profile information</CardTitle>
        <CardDescription>Update your profile information and email address.</CardDescription>
      </CardHeader>
      <CardContent className="p-4">
        <form id="update-profile-form" onSubmit={submit}>
          <div className="grid gap-6">
            <div className="grid gap-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                autoComplete="name"
                placeholder="Full name"
              />
              <InputError message={form.errors.name} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="email">Email address</Label>
              <Input
                id="email"
                type="email"
                value={form.data.email}
                onChange={(e) => form.setData('email', e.target.value)}
                autoComplete="username"
                placeholder="Email address"
              />
              <InputError message={form.errors.email} />
            </div>
            {emailChanged && (
              <div className="grid gap-2">
                <Label htmlFor="profile_current_password">Current password</Label>
                <Input
                  id="profile_current_password"
                  type="password"
                  value={form.data.current_password}
                  onChange={(e) => form.setData('current_password', e.target.value)}
                  autoComplete="current-password"
                  placeholder="Confirm your password to change the email"
                />
                <InputError message={form.errors.current_password} />
              </div>
            )}
            {page.props.must_verify_email && page.props.auth.user.email_verified_at === null && (
              <div>
                <p className="text-muted-foreground -mt-4 text-sm">
                  Your email address is unverified.{' '}
                  <Link href="/email/verification-notification" method="post" as="button" className="text-foreground underline">
                    Click here to resend the verification email.
                  </Link>
                </p>
                {page.props.status === 'verification-link-sent' && (
                  <div className="text-success mt-2 text-sm font-medium">A new verification link has been sent to your email address.</div>
                )}
              </div>
            )}
          </div>
        </form>
      </CardContent>
      <CardFooter className="gap-2">
        <Button form="update-profile-form" disabled={form.processing}>
          {form.processing && <LoaderCircleIcon className="animate-spin" />}
          <FormSuccessful successful={form.recentlySuccessful} />
          Save
        </Button>
      </CardFooter>
    </Card>
  );
}
