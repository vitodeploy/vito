import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';
import type { Server } from '@/types/server';
import { Project } from '@/types/project';
import { User } from '@/types/user';
import { Site, SiteType } from '@/types/site';
import { DynamicFieldConfig } from './dynamic-field-config';

export interface Auth {
  user: User;
  projects: Project[];
  currentProject?: Project;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

export interface NavItem {
  title: string;
  href: string;
  onlyActivePath?: string;
  icon?: LucideIcon | null;
  isActive?: boolean;
  isDisabled?: boolean;
  children?: NavItem[];
  hidden?: boolean;
}

export interface Configs {
  notification_channels_providers: string[];
  notification_channels_providers_custom_fields: {
    [provider: string]: string[];
  };
  operating_systems: string[];
  service_versions: {
    [service: string]: string[];
  };
  service_types: {
    [service: string]: string;
  };
  colors: string[];
  webservers: string[];
  databases: string[];
  php_versions: string[];
  cronjob_intervals: {
    [key: string]: string;
  };
  metrics_periods: string[];
  php_extensions: string[];

  server_provider: {
    providers: {
      [provider: string]: {
        label: string;
        handler: string;
        form?: DynamicFieldConfig[];
      };
    };
  };
  storage_provider: {
    providers: {
      [provider: string]: {
        label: string;
        handler: string;
        form?: DynamicFieldConfig[];
      };
    };
  };
  source_control: {
    providers: {
      [provider: string]: {
        label: string;
        handler: string;
        form?: DynamicFieldConfig[];
      };
    };
  };
  site: {
    types: {
      [type: string]: SiteType;
    };
  };

  [key: string]: unknown;
}

export interface SharedData {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
  configs: Configs;
  projectServers: Server[];
  serverSites?: Site[];
  server?: Server;
  site?: Site;
  publicKeyText: string;
  flash?: {
    success: string;
    error: string;
    info: string;
    warning: string;
    data: unknown;
  };

  [key: string]: unknown;
}

export interface PaginatedData<TData> {
  data: TData[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginationMeta {
  current_page: number;
  current_page_url: string;
  from: number | null;
  path: string;
  per_page: number;
  to: number | null;
  total?: number;
  last_page?: number;
}
