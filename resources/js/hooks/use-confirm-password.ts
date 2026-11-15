import axios from 'axios';
import { useCallback } from 'react';
import { useDialog } from '@/hooks/use-dialog';

/**
 * Runs `action` once the user has a recent password confirmation, asking for the password first when needed.
 */
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
