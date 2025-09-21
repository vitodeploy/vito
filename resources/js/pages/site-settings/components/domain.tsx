import { FormEvent, ReactNode, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';

export default function Domain({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    domain: string;
  }>({
    domain: site.domain,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();

    // Prevent submission if modern deployment is enabled
    if (site.modern_deployment) {
      return;
    }

    form.patch(route('site-settings.update-domain', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update Domain</DialogTitle>
          <DialogDescription>
            Change the domain for your site.
            <br />
            <br />
            {site.modern_deployment && (
              <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-3">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                      <path
                        fillRule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clipRule="evenodd"
                      />
                    </svg>
                  </div>
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">Modern Deployment Detected</h3>
                    <div className="mt-2 text-sm text-red-700">
                      <p>
                        You cannot change the domain while modern deployment is enabled. This would break all deployment symlinks and shared
                        resources.
                      </p>
                      <p className="mt-1">
                        <strong>Solution:</strong> Disable modern deployment first, change the domain, then re-enable it.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}
            <strong>This action will:</strong>
            <ul className="mt-2 mb-2 space-y-1 text-sm">
              <li>
                • Move the site directory from <code className="bg-muted rounded px-1 py-0.5 text-xs">{site.path}</code> to{' '}
                <code className="bg-muted rounded px-1 py-0.5 text-xs">
                  /home/{site.user}/{form.data.domain === site.domain ? '[new-domain]' : form.data.domain || '[new-domain]'}
                </code>
              </li>
              <li>• Move and update virtual host files (preserving custom configurations)</li>
              {site.type === 'load-balancer' && <li>• Update load balancer upstream configuration with new domain-based backend name</li>}
              <li>• Recreate site level workers to pick up the new directory path</li>
            </ul>
            <strong className="text-amber-600">Important:</strong> After changing the domain, you may need to manually check and update:
            <ul className="mt-2 space-y-1 text-sm">
              <li>
                • <strong>SSL Certificates:</strong> Existing SSL certificates are tied to the old domain and will need to be reissued for the new
                domain
              </li>
              <li>
                • <strong>Environment Variables:</strong> Check your .env file for any hardcoded domain references
              </li>
              <li>
                • <strong>Cronjobs:</strong> Any cronjobs that reference the old directory path
              </li>
              <li>
                • <strong>Worker Commands:</strong> Worker commands that use absolute paths or reference the old domain
              </li>
              <li>
                • <strong>Custom Scripts:</strong> Any custom deployment scripts or configurations with hardcoded paths or domains
              </li>
            </ul>
          </DialogDescription>
        </DialogHeader>

        <Form id="domain-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="domain">Domain</Label>
              <Input
                id="domain"
                type="text"
                value={form.data.domain}
                placeholder="example.com"
                onChange={(e) => form.setData('domain', e.target.value)}
              />
              <InputError message={form.errors.domain} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="domain-form" disabled={form.processing || site.modern_deployment}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            {site.modern_deployment ? 'Not available' : 'Update Domain'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
