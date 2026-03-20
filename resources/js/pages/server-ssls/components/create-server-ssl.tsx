import { FormEvent, ReactNode, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Server } from '@/types/server';
import { Domain } from '@/types/domain';
import { SharedData } from '@/types';

type CreateForm = {
  type: string;
  common_name: string;
  organization: string;
  organizational_unit: string;
  city: string;
  state: string;
  country: string;
  email: string;
  key_size: string;
  passphrase: string;
  certificate: string;
  private_key: string;
  ca: string;
  domain_id: string;
};

export default function CreateServerSsl({ server, domains, children }: { server: Server; domains: Domain[]; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();

  const form = useForm<CreateForm>({
    type: '',
    common_name: '',
    organization: '',
    organizational_unit: '',
    city: '',
    state: '',
    country: '',
    email: '',
    key_size: '2048',
    passphrase: '',
    certificate: '',
    private_key: '',
    ca: '',
    domain_id: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('server-ssls.store', { server: server.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Sheet
      open={open}
      onOpenChange={(value) => {
        if (value) {
          form.reset();
          form.clearErrors();
        }
        setOpen(value);
      }}
    >
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="w-full lg:max-w-lg">
        <SheetHeader>
          <SheetTitle>Create SSL</SheetTitle>
          <SheetDescription>Add a new server-level SSL certificate.</SheetDescription>
        </SheetHeader>
        <Form id="create-server-ssl-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select
                onValueChange={(value) => {
                  if (value === 'letsencrypt' && !form.data.email) {
                    form.setData((prev) => ({ ...prev, type: value, email: page.props.auth.user.email }));
                  } else {
                    form.setData('type', value);
                  }
                }}
                value={form.data.type}
              >
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="csr">CSR (Certificate Signing Request)</SelectItem>
                  <SelectItem value="custom">Custom Certificate</SelectItem>
                  <SelectItem value="letsencrypt">Wildcard Certificate (LetsEncrypt)</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            {form.data.type === 'custom' && (
              <>
                <FormField>
                  <Label htmlFor="certificate">Certificate (PEM)</Label>
                  <Textarea
                    id="certificate"
                    name="certificate"
                    placeholder="-----BEGIN CERTIFICATE-----"
                    rows={6}
                    className="field-sizing-fixed overflow-y-auto"
                    value={form.data.certificate}
                    onChange={(e) => form.setData('certificate', e.target.value)}
                  />
                  <InputError message={form.errors.certificate} />
                </FormField>
                <FormField>
                  <Label htmlFor="private_key">Private Key (PEM)</Label>
                  <Textarea
                    id="private_key"
                    name="private_key"
                    placeholder="-----BEGIN RSA PRIVATE KEY-----"
                    rows={6}
                    className="field-sizing-fixed overflow-y-auto"
                    value={form.data.private_key}
                    onChange={(e) => form.setData('private_key', e.target.value)}
                  />
                  <InputError message={form.errors.private_key} />
                </FormField>
                <FormField>
                  <Label htmlFor="ca">CA Bundle (optional)</Label>
                  <Textarea
                    id="ca"
                    name="ca"
                    placeholder="-----BEGIN CERTIFICATE-----"
                    rows={6}
                    className="field-sizing-fixed overflow-y-auto"
                    value={form.data.ca}
                    onChange={(e) => form.setData('ca', e.target.value)}
                  />
                  <InputError message={form.errors.ca} />
                </FormField>
              </>
            )}
            {form.data.type === 'letsencrypt' && (
              <>
                <FormField>
                  <Label htmlFor="domain_id">Domain</Label>
                  <p className="text-muted-foreground text-xs">
                    Only domains managed by Vito with a DNS provider are available, as wildcard SSL requires DNS verification.
                  </p>
                  <Select onValueChange={(value) => form.setData('domain_id', value)} value={form.data.domain_id}>
                    <SelectTrigger id="domain_id">
                      <SelectValue placeholder="Select domain" />
                    </SelectTrigger>
                    <SelectContent>
                      {domains.map((domain) => (
                        <SelectItem key={domain.id} value={String(domain.id)}>
                          {domain.domain}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.domain_id} />
                </FormField>
                <FormField>
                  <Label htmlFor="wildcard_email">Email</Label>
                  <Input
                    type="email"
                    id="wildcard_email"
                    name="email"
                    placeholder="admin@example.com"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                  />
                  <InputError message={form.errors.email} />
                </FormField>
              </>
            )}
            {form.data.type === 'csr' && (
              <>
                <FormField>
                  <Label htmlFor="common_name">Common Name (Domain)</Label>
                  <Input
                    type="text"
                    id="common_name"
                    name="common_name"
                    placeholder="example.com"
                    value={form.data.common_name}
                    onChange={(e) => form.setData('common_name', e.target.value)}
                  />
                  <InputError message={form.errors.common_name} />
                </FormField>
                <FormField>
                  <Label htmlFor="organization">Organization</Label>
                  <Input
                    type="text"
                    id="organization"
                    name="organization"
                    placeholder="Acme Inc"
                    value={form.data.organization}
                    onChange={(e) => form.setData('organization', e.target.value)}
                  />
                  <InputError message={form.errors.organization} />
                </FormField>
                <FormField>
                  <Label htmlFor="organizational_unit">Organizational Unit (optional)</Label>
                  <Input
                    type="text"
                    id="organizational_unit"
                    name="organizational_unit"
                    placeholder="IT Department"
                    value={form.data.organizational_unit}
                    onChange={(e) => form.setData('organizational_unit', e.target.value)}
                  />
                  <InputError message={form.errors.organizational_unit} />
                </FormField>
                <FormField>
                  <Label htmlFor="city">City / Locality</Label>
                  <Input
                    type="text"
                    id="city"
                    name="city"
                    placeholder="San Francisco"
                    value={form.data.city}
                    onChange={(e) => form.setData('city', e.target.value)}
                  />
                  <InputError message={form.errors.city} />
                </FormField>
                <FormField>
                  <Label htmlFor="state">State / Province</Label>
                  <Input
                    type="text"
                    id="state"
                    name="state"
                    placeholder="California"
                    value={form.data.state}
                    onChange={(e) => form.setData('state', e.target.value)}
                  />
                  <InputError message={form.errors.state} />
                </FormField>
                <FormField>
                  <Label htmlFor="country">Country (2-letter code)</Label>
                  <Input
                    type="text"
                    id="country"
                    name="country"
                    placeholder="US"
                    maxLength={2}
                    className="uppercase"
                    value={form.data.country}
                    onChange={(e) => form.setData('country', e.target.value.toUpperCase())}
                  />
                  <InputError message={form.errors.country} />
                </FormField>
                <FormField>
                  <Label htmlFor="email">Email (optional)</Label>
                  <Input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="admin@example.com"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                  />
                  <InputError message={form.errors.email} />
                </FormField>
                <FormField>
                  <Label htmlFor="key_size">Key Size</Label>
                  <Select onValueChange={(value) => form.setData('key_size', value)} value={form.data.key_size}>
                    <SelectTrigger id="key_size">
                      <SelectValue placeholder="Select key size" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="2048">2048 bits</SelectItem>
                      <SelectItem value="4096">4096 bits</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.key_size} />
                </FormField>
                <FormField>
                  <Label htmlFor="passphrase">Passphrase (optional)</Label>
                  <Input
                    type="password"
                    id="passphrase"
                    name="passphrase"
                    placeholder="Leave empty for no passphrase"
                    value={form.data.passphrase}
                    onChange={(e) => form.setData('passphrase', e.target.value)}
                  />
                  <InputError message={form.errors.passphrase} />
                </FormField>
              </>
            )}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-server-ssl-form" disabled={form.processing}>
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />} Create
            </Button>
            <SheetClose asChild>
              <Button variant="outline" disabled={form.processing}>
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
