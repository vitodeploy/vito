import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input-error';
import AuthLayout from '@/layouts/auth/layout';
import { Project } from '@/types/project';

export default function AcceptInvitation({ project, role, email, url }: { project: Pick<Project, 'id' | 'name'>; role: string; email: string; url: string }) {
  const form = useForm<{ invitation?: string }>({});

  const accept = (e: FormEvent) => {
    e.preventDefault();
    form.post(url);
  };

  return (
    <AuthLayout title={`Join ${project.name}`} description={`You have been invited to join this project as ${role}. You will join as ${email}.`}>
      <Head title="Project invitation" />

      <form onSubmit={accept} className="flex flex-col gap-2">
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing && <LoaderCircleIcon className="animate-spin" />}
          Accept & Join
        </Button>
        <Button type="button" variant="outline" className="w-full" disabled={form.processing} onClick={() => form.delete(url)}>
          Decline
        </Button>
        <Button variant="ghost" className="w-full" asChild>
          <Link href="/settings/projects">Not now</Link>
        </Button>
        <InputError message={form.errors.invitation} />
      </form>
    </AuthLayout>
  );
}
