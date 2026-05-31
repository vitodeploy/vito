import { Server } from '@/types/server';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type DataRetentionDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  server: Server;
  dataRetention: string;
};

export default function DataRetentionDialog({ open, onOpenChange, server, dataRetention }: DataRetentionDialogProps) {
  const form = useForm({
    data_retention: String(dataRetention || '30'),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('monitoring.update', server.id), {
      onSuccess: () => onOpenChange(false),
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Data retention</DialogTitle>
          <DialogDescription className="sr-only">Data retention</DialogDescription>
        </DialogHeader>
        <Form id="data-retention-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="data_retention">Data retention (days)</Label>
              <Select value={form.data.data_retention} onValueChange={(value) => form.setData('data_retention', value)}>
                <SelectTrigger id="data_retention">
                  <SelectValue placeholder="Select a period" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="7">7 Days</SelectItem>
                    <SelectItem value="14">14 Days</SelectItem>
                    <SelectItem value="30">30 Days</SelectItem>
                    <SelectItem value="60">60 Days</SelectItem>
                    <SelectItem value="90">90 Days</SelectItem>
                    <SelectItem value="180">180 Days</SelectItem>
                    <SelectItem value="365">365 Days</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="data-retention-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
