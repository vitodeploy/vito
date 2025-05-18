import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { FormEventHandler } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

type ProfileForm = {
  name: string;
  email: string;
};

export default function UpdateProfile() {
  const { auth } = usePage<SharedData>().props;

  const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<Required<ProfileForm>>({
    name: auth.user.name,
    email: auth.user.email,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    patch(route('profile.update'), {
      preserveScroll: true,
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Profile information</CardTitle>
        <CardDescription>Update your profile information and email address.</CardDescription>
      </CardHeader>
      <CardContent className="p-4">
        <Form id="update-profile-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                required
                autoComplete="name"
                placeholder="Full name"
              />
              <InputError message={errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="email">Email address</Label>
              <Input
                id="email"
                type="email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                required
                autoComplete="username"
                placeholder="Email address"
              />
              <InputError message={errors.email} />
            </FormField>
          </FormFields>
        </Form>
      </CardContent>
      <CardFooter className="gap-2">
        <Button form="update-profile-form" disabled={processing}>
          {processing && <LoaderCircleIcon className="animate-spin" />}
          <FormSuccessful successful={recentlySuccessful} />
          Save
        </Button>
      </CardFooter>
    </Card>
  );
}
