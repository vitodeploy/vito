import InputError from '@/components/ui/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import AuthLayout from '@/layouts/auth/layout';
import { useInitials } from '@/hooks/use-initials';
import { type User } from '@/types/user';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon, LockKeyholeIcon, UserPlusIcon, UserRoundIcon } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

type DesktopUser = Pick<User, 'id' | 'name' | 'email' | 'is_admin' | 'created_at' | 'updated_at'>;

type DesktopLoginProps = {
  users: DesktopUser[];
  setup_required: boolean;
  locked: boolean;
  locked_user_id: number | null;
};

export default function DesktopLogin() {
  const { users, setup_required, locked, locked_user_id } = usePage<DesktopLoginProps>().props;
  const initials = useInitials();
  const [selectedUserId, setSelectedUserId] = useState<number | null>(locked_user_id);
  const loginForm = useForm<{ password: string }>({
    password: '',
  });
  const setupForm = useForm<{
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  const selectedUser = useMemo(() => users.find((user) => user.id === selectedUserId) ?? null, [selectedUserId, users]);
  const title = setup_required ? 'Set up Vito' : locked ? 'Unlock Vito' : 'Choose a user';
  const description = setup_required
    ? 'Create the first local desktop administrator.'
    : locked
      ? 'Enter the password for the selected user.'
      : 'Select a local desktop user to continue.';

  const chooseUser = (user: DesktopUser) => {
    if (!locked) {
      router.post(route('desktop.login.store', { user: user.id }));
      return;
    }

    loginForm.clearErrors();
    loginForm.reset('password');
    setSelectedUserId(user.id);
  };

  const setup = (e: FormEvent) => {
    e.preventDefault();

    setupForm.post(route('desktop.setup'), {
      onFinish: () => setupForm.reset('password', 'password_confirmation'),
    });
  };

  const unlock = (e: FormEvent) => {
    e.preventDefault();

    if (!selectedUser) {
      return;
    }

    loginForm.post(route('desktop.login.store', { user: selectedUser.id }), {
      onFinish: () => loginForm.reset('password'),
    });
  };

  return (
    <AuthLayout title={title} description={description}>
      <Head title={setup_required ? 'Set up desktop' : locked ? 'Unlock' : 'Choose user'} />

      <div className="grid gap-5">
        {setup_required ? (
          <form onSubmit={setup} className="grid gap-4">
            <div className="grid gap-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                type="text"
                required
                autoFocus
                autoComplete="name"
                value={setupForm.data.name}
                onChange={(e) => setupForm.setData('name', e.target.value)}
                placeholder="Name"
              />
              <InputError message={setupForm.errors.name} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                required
                autoComplete="email"
                value={setupForm.data.email}
                onChange={(e) => setupForm.setData('email', e.target.value)}
                placeholder="Email"
              />
              <InputError message={setupForm.errors.email} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                required
                autoComplete="new-password"
                value={setupForm.data.password}
                onChange={(e) => setupForm.setData('password', e.target.value)}
                placeholder="Password"
              />
              <InputError message={setupForm.errors.password} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="password_confirmation">Confirm password</Label>
              <Input
                id="password_confirmation"
                type="password"
                required
                autoComplete="new-password"
                value={setupForm.data.password_confirmation}
                onChange={(e) => setupForm.setData('password_confirmation', e.target.value)}
                placeholder="Confirm password"
              />
              <InputError message={setupForm.errors.password_confirmation} />
            </div>

            <Button type="submit" className="w-full" disabled={setupForm.processing}>
              {setupForm.processing ? <LoaderCircleIcon className="animate-spin" /> : <UserPlusIcon />}
              Create admin
            </Button>
          </form>
        ) : users.length === 0 ? (
          <div className="border-border bg-muted/30 rounded-md border p-4 text-center text-sm">
            <p className="font-medium">No users found.</p>
            <p className="text-muted-foreground mt-1">Create a user before continuing.</p>
          </div>
        ) : (
          <div className="grid gap-2">
            {users.map((user) => {
              const selected = selectedUserId === user.id;

              return (
                <button
                  key={user.id}
                  type="button"
                  onClick={() => chooseUser(user)}
                  className={cn(
                    'border-input bg-background hover:bg-accent hover:text-accent-foreground flex w-full items-center gap-3 rounded-md border p-3 text-left transition-colors',
                    selected && 'border-ring ring-ring/40 ring-2',
                  )}
                >
                  <span className="bg-accent text-accent-foreground border-ring flex h-9 w-9 shrink-0 items-center justify-center rounded-md border text-sm font-medium">
                    {initials(user.name) || <UserRoundIcon className="size-4" />}
                  </span>
                  <span className="grid min-w-0 flex-1 text-sm leading-tight">
                    <span className="truncate font-medium">{user.name}</span>
                    <span className="text-muted-foreground truncate text-xs">{user.email}</span>
                  </span>
                  {locked && selected && <LockKeyholeIcon className="text-muted-foreground size-4" />}
                </button>
              );
            })}
          </div>
        )}

        {locked && selectedUser && (
          <form onSubmit={unlock} className="grid gap-4">
            <div className="grid gap-2">
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                required
                autoFocus
                autoComplete="current-password"
                value={loginForm.data.password}
                onChange={(e) => loginForm.setData('password', e.target.value)}
                placeholder="Password"
              />
              <InputError message={loginForm.errors.password} />
            </div>

            <Button type="submit" className="w-full" disabled={loginForm.processing}>
              {loginForm.processing && <LoaderCircleIcon className="animate-spin" />}
              Unlock
            </Button>
          </form>
        )}
      </div>
    </AuthLayout>
  );
}
