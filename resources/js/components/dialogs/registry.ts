import type { ComponentType } from 'react';
import LogViewerDialog from './log-viewer-dialog';
import ConfirmationDialog from './confirmation-dialog';
import StorageProviderEditDialog from '@/pages/storage-providers/components/edit-dialog';
import PluginLogsDialog from './plugin-logs-dialog';
import WorkerLogsDialog from './worker-logs-dialog';
import CronJobForm from '@/pages/cronjobs/components/form';
import WorkerForm from '@/pages/workers/components/form';
import ActivateServerSslDialog from './activate-server-ssl-dialog';
import SourceControlEditDialog from '@/pages/source-controls/components/edit-dialog';
import DataRetentionDialog from '@/pages/monitoring/components/data-retention-dialog';
import EditDatabaseUserDialog from '@/pages/database-users/components/edit-database-user';
import LinkDatabaseUserDialog from '@/pages/database-users/components/link-dialog';
import PhpExtensionsDialog from './php-extensions-dialog';
import PhpIniDialog from './php-ini-dialog';
import ServerProviderEditDialog from '@/pages/server-providers/components/edit-dialog';
import DnsProviderEditDialog from '@/pages/dns-providers/components/edit-dialog';
import NotificationChannelEditDialog from '@/pages/notification-channels/components/edit-dialog';
import ServiceConfigFileDialog from '@/pages/services/components/config-file-dialog';
import CreateHostedDomain from '@/pages/hosted-domains/components/create-hosted-domain';
import EditHostedDomain from '@/pages/hosted-domains/components/edit-hosted-domain';
import FirewallRuleForm from '@/pages/firewall/components/form';
import RecordForm from '@/pages/domains/components/record-form';
import ScriptForm from '@/pages/scripts/components/form';
import EditCommand from '@/pages/commands/components/edit-command';
import EditBackup from '@/pages/backups/components/edit-backup';
import RestoreBackup from '@/pages/backups/components/restore-backup';

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
  confirm: ConfirmationDialog,
  storageProviderEdit: StorageProviderEditDialog,
  pluginLogs: PluginLogsDialog,
  workerLogs: WorkerLogsDialog,
  cronjobForm: CronJobForm,
  workerForm: WorkerForm,
  activateServerSsl: ActivateServerSslDialog,
  sourceControlEdit: SourceControlEditDialog,
  dataRetention: DataRetentionDialog,
  databaseUserEdit: EditDatabaseUserDialog,
  databaseUserLink: LinkDatabaseUserDialog,
  phpExtensions: PhpExtensionsDialog,
  phpIni: PhpIniDialog,
  serverProviderEdit: ServerProviderEditDialog,
  dnsProviderEdit: DnsProviderEditDialog,
  notificationChannelEdit: NotificationChannelEditDialog,
  serviceConfigFile: ServiceConfigFileDialog,
  createHostedDomain: CreateHostedDomain,
  editHostedDomain: EditHostedDomain,
  firewallForm: FirewallRuleForm,
  dnsRecordForm: RecordForm,
  scriptForm: ScriptForm,
  commandEdit: EditCommand,
  backupEdit: EditBackup,
  backupRestore: RestoreBackup,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
} as const satisfies Record<string, ComponentType<any>>;

export type DialogRegistry = typeof dialogs;

export type ConsumerProps<C> = C extends ComponentType<infer P> ? Omit<P, keyof DialogControlProps> : never;
