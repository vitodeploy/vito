import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { VitoBackup } from '@/types';
import { LoaderCircle } from 'lucide-react';

interface DeleteVitoBackupDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  backup: VitoBackup;
}

export default function DeleteVitoBackupDialog({ open, onOpenChange, backup }: DeleteVitoBackupDialogProps) {
  const form = useForm();

  const submit = () => {
    form.delete(route('vito-backups.destroy', { vitoBackup: backup.id }), {
      onSuccess: () => {
        onOpenChange(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Delete Vito Backup</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete the backup "{backup.name}"? This action cannot be undone. All backup files associated with this
            configuration will also be deleted from your storage provider.
          </DialogDescription>
        </DialogHeader>

        <DialogFooter>
          <div className="flex items-center gap-2">
            <Button type="button" variant="destructive" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Delete Backup
            </Button>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
