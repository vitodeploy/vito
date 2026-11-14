import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth/layout';

export default function AcceptInvitation({ project, role, token }: { project: { id: number; name: string }; role: string; token: string }) {
  const form = useForm({});

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/settings/projects/${project.id}/invitations/${encodeURIComponent(token)}`);
  };

  return (
    <AuthLayout title={`Join ${project.name}`} description={`You have been invited to join this project as ${role}.`}>
      <Head title="Project invitation" />

      <form onSubmit={submit} className="flex flex-col gap-2">
        <Button className="w-full" disabled={form.processing}>
          {form.processing && <LoaderCircleIcon className="animate-spin" />}
          Accept & Join
        </Button>
        <Button variant="ghost" className="w-full" asChild>
          <Link href="/settings/projects">Not now</Link>
        </Button>
      </form>
    </AuthLayout>
  );
}
