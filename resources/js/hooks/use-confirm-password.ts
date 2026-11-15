import axios from 'axios';
import { useCallback } from 'react';
import { useDialog } from '@/hooks/use-dialog';

export function useConfirmPassword(): (action: () => void) => void {
  const dialog = useDialog();

  return useCallback(
    (action: () => void) => {
      axios.get<{ confirmed: boolean }>('/user/confirmed-password-status').then(
        (response) => (response.data.confirmed ? action() : dialog.confirmPassword.open({ onConfirmed: action })),
        () => dialog.confirmPassword.open({ onConfirmed: action }),
      );
    },
    [dialog],
  );
}
