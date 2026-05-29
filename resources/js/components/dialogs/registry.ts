import type { ComponentType } from 'react';
import LogViewerDialog from './log-viewer-dialog';

export type DialogControlProps = { open: boolean; onOpenChange: (open: boolean) => void };

/**
 * Registry of all app-level dialogs that can be opened via `useDialog()`.
 *
 * Authorization contract: every registered consumer is responsible for
 * ensuring its props come from server-authorised sources (Inertia page
 * props, API resources, etc.), not from URL parameters or other
 * user-controlled input. The registry pattern itself carries no authz.
 *
 * To register a new dialog: add one entry here, mapping a typed key to the
 * component. Consumers immediately gain `dialog.<key>.open(props)` with
 * full IntelliSense for the prop shape.
 */
export const dialogs = {
  logViewer: LogViewerDialog,
} as const satisfies Record<string, ComponentType<any>>;

export type DialogRegistry = typeof dialogs;

export type ConsumerProps<C> = C extends ComponentType<infer P> ? Omit<P, keyof DialogControlProps> : never;
