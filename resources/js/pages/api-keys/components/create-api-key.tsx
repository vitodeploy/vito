import { ClipboardCheckIcon, ClipboardIcon, LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { useForm } from '@inertiajs/react';
import React, { FormEventHandler, ReactNode, useRef, useState } from 'react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type ApiKeyForm = {
  name: string;
  permission: string;
};

export default function CreateApiKey({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [token, setToken] = useState<string | undefined>();
  const tokenInputRef = useRef<HTMLInputElement>(null);
  const [copySuccess, setCopySuccess] = useState(false);
  const copyToClipboard = () => {
    tokenInputRef.current?.select();
    navigator.clipboard.writeText(token || '').then(() => {
      setCopySuccess(true);
      setTimeout(() => {
        setCopySuccess(false);
      }, 2000);
    });
  };

  const form = useForm<Required<ApiKeyForm>>({
    name: '',
    permission: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('api-keys.store'), {
      onSuccess: (page) => {
        const flash = page.props.flash as { data?: { token?: string } };
        setToken(flash.data?.token);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create an API key</DialogTitle>
          <DialogDescription className="sr-only">Create a new api key</DialogDescription>
        </DialogHeader>
        <Form id="create-tag-form" onSubmit={submit} className="p-4">
          {token ? (
            <FormFields>
              <FormField>
                <Label htmlFor="token" className="flex items-center gap-1">
                  Token {copySuccess ? <ClipboardCheckIcon className="text-success! size-4" /> : <ClipboardIcon className="size-4" />}
                </Label>
                <Input ref={tokenInputRef} id="token" onClick={copyToClipboard} type="text" value={token || ''} className="cursor-pointer" />
              </FormField>
            </FormFields>
          ) : (
            <FormFields>
              <FormField>
                <Label htmlFor="name">Name</Label>
                <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <InputError message={form.errors.name} />
              </FormField>
              <FormField>
                <Label htmlFor="name">Name</Label>
                <Select name="permission" value={form.data.permission} onValueChange={(value) => form.setData('permission', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a permission" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem key="permission-read" value="read">
                        read
                      </SelectItem>
                      <SelectItem key="permission-write" value="write">
                        read & write
                      </SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.permission} />
              </FormField>
            </FormFields>
          )}
        </Form>
        {!token && (
          <DialogFooter>
            <DialogClose asChild>
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </DialogClose>
            <Button type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Create
            </Button>
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
