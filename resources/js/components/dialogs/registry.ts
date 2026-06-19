import type { ComponentType } from 'react';
import LogViewerDialog from './log-viewer-dialog';
import ConfirmationDialog from './confirmation-dialog';
import StorageProviderEditDialog from '@/pages/storage-providers/components/edit-dialog';
import PluginLogsDialog from '@/pages/plugins/components/logs-dialog';
import WorkerLogsDialog from '@/pages/workers/components/logs-dialog';
import WorkerEnvDialog from '@/pages/workers/components/env-dialog';
import CronJobForm from '@/pages/cronjobs/components/form';
import WorkerForm from '@/pages/workers/components/form';
import ActivateServerSslDialog from '@/pages/server-ssls/components/activate-dialog';
import SourceControlEditDialog from '@/pages/source-controls/components/edit-dialog';
import DataRetentionDialog from '@/pages/monitoring/components/data-retention-dialog';
import EditDatabaseUserDialog from '@/pages/database-users/components/edit-database-user';
import LinkDatabaseUserDialog from '@/pages/database-users/components/link-dialog';
import PhpExtensionsDialog from '@/pages/php/components/extensions-dialog';
import PhpIniDialog from '@/pages/php/components/ini-dialog';
import ServerProviderEditDialog from '@/pages/server-providers/components/edit-dialog';
import DnsProviderEditDialog from '@/pages/dns-providers/components/edit-dialog';
import NotificationChannelEditDialog from '@/pages/notification-channels/components/edit-dialog';
import ServiceConfigFileDialog from '@/pages/services/components/config-file-dialog';
import CreateHostedDomain from '@/pages/hosted-domains/components/create-hosted-domain';
import EditHostedDomain from '@/pages/hosted-domains/components/edit-hosted-domain';
import FirewallRuleForm from '@/pages/firewall/components/form';
import ServerIpForm from '@/pages/server-network/components/form';
import RecordForm from '@/pages/domains/components/record-form';
import ScriptForm from '@/pages/scripts/components/form';
import EditCommand from '@/pages/commands/components/edit-command';
import CreateBackup from '@/pages/backups/components/create-backup';
import EditBackup from '@/pages/backups/components/edit-backup';
import RestoreBackup from '@/pages/backups/components/restore-backup';
import SiteFeatureAction from '@/pages/site-features/components/feature-action';
import ServerFeatureAction from '@/pages/server-features/components/feature-action';
import Fail2banForm from '@/pages/security/components/fail2ban-form';

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
  workerEnv: WorkerEnvDialog,
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
  serverIpForm: ServerIpForm,
  dnsRecordForm: RecordForm,
  scriptForm: ScriptForm,
  commandEdit: EditCommand,
  backupCreate: CreateBackup,
  backupEdit: EditBackup,
  backupRestore: RestoreBackup,
  siteFeatureAction: SiteFeatureAction,
  serverFeatureAction: ServerFeatureAction,
  fail2banForm: Fail2banForm,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
} as const satisfies Record<string, ComponentType<any>>;

export type DialogRegistry = typeof dialogs;

export type ConsumerProps<C> = C extends ComponentType<infer P> ? Omit<P, keyof DialogControlProps> : never;
