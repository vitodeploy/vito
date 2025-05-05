import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import { Toaster } from '@/components/ui/sonner';

export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
  return (
    <AuthLayoutTemplate title={title} description={description} {...props}>
      {children}
      <Toaster />
    </AuthLayoutTemplate>
  );
}
