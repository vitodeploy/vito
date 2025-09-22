import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { DownloadIcon, MoreHorizontalIcon, TrashIcon } from 'lucide-react';
import { VitoBackup } from '@/types';
import { router } from '@inertiajs/react';

interface BackupFilesDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  backup: VitoBackup;
}

export default function BackupFilesDialog({ open, onOpenChange, backup }: BackupFilesDialogProps) {
  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const getFileStatusColor = (status: string) => {
    const colors = {
      created: 'bg-green-100 text-green-800',
      creating: 'bg-yellow-100 text-yellow-800',
      failed: 'bg-red-100 text-red-800',
      deleting: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Backup Files - {backup.name}</DialogTitle>
          <DialogDescription>Manage individual backup files for this backup configuration.</DialogDescription>
        </DialogHeader>

        <div className="max-h-96 overflow-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Size</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Created</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {backup.files && backup.files.length > 0 ? (
                backup.files.map((file) => (
                  <TableRow key={file.id}>
                    <TableCell className="font-medium">{file.name}</TableCell>
                    <TableCell>{formatFileSize(file.size)}</TableCell>
                    <TableCell>
                      <Badge className={getFileStatusColor(file.status)}>{file.status}</Badge>
                    </TableCell>
                    <TableCell>
                      {new Date(file.created_at).toLocaleDateString()} {new Date(file.created_at).toLocaleTimeString()}
                    </TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button size="sm" variant="ghost" className="h-8 w-8 p-0">
                            <MoreHorizontalIcon className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={() => {
                              window.open(route('vito-backup-files.download', { vitoBackupFile: file.id }), '_blank');
                            }}
                          >
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            Download
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => {
                              if (confirm('Are you sure you want to delete this backup file?')) {
                                router.delete(route('vito-backup-files.destroy', { vitoBackupFile: file.id }), {
                                  onSuccess: () => {
                                    // Reload the current page data using Inertia
                                    router.reload();
                                    // Close the modal
                                    onOpenChange(false);
                                  },
                                });
                              }
                            }}
                            className="text-red-600"
                          >
                            <TrashIcon className="mr-2 h-4 w-4" />
                            Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={5} className="text-muted-foreground text-center">
                    No backup files found.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      </DialogContent>
    </Dialog>
  );
}
