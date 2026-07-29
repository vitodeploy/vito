import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/ui/input-error';

export default function AddNetworkPeer({
  open,
  onOpenChange,
  networkId,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
}) {
  const [byo, setByo] = useState(false);
  const form = useForm<{ name: string; public_key: string }>({
    name: '',
    public_key: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ name: data.name, public_key: byo ? data.public_key : '' }));
    form.post(route('networks.peers.store', { network: networkId }), {
      onSuccess: () => {
        form.reset();
        setByo(false);
        onOpenChange(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Add peer</DialogTitle>
          <DialogDescription className="sr-only">Add a device to this network</DialogDescription>
        </DialogHeader>
        <Form id="add-network-peer-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="e.g. my-laptop" />
              <InputError message={form.errors.name} />
            </FormField>

            <FormField>
              <div className="flex items-center gap-2">
                <Checkbox id="byo" checked={byo} onCheckedChange={(checked) => setByo(checked === true)} />
                <Label htmlFor="byo">I'll provide my own public key</Label>
              </div>
            </FormField>

            {byo && (
              <FormField>
                <Label htmlFor="public_key">Public key</Label>
                <Textarea
                  id="public_key"
                  value={form.data.public_key}
                  onChange={(e) => form.setData('public_key', e.target.value)}
                  placeholder="Base64-encoded WireGuard public key"
                />
                <p className="text-muted-foreground text-xs">Vito will never hold this peer's private key. The device keeps it.</p>
                <InputError message={form.errors.public_key} />
              </FormField>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="add-network-peer-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Add peer
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
