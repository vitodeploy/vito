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
  external?: boolean;
}

export interface Configs {
  operating_systems: string[];
  colors: string[];
  databases: string[];
  cronjob_intervals: {
    [key: string]: string;
  };
  metrics_periods: string[];

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
  notification_channel: {
    providers: {
      [channel: string]: {
        label: string;
        handler: string;
        form?: DynamicFieldConfig[];
      };
    };
  };
  service: {
    services: {
      [name: string]: {
        label: string;
        type: string;
        handler: string;
        form?: DynamicFieldConfig[];
        versions: string[];
        data?: {
          extensions?: string[];
        };
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
  version: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  configs: Configs;
  project_servers: Server[];
  server_sites?: Site[];
  server?: Server;
  site?: Site;
  public_key_text: string;
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
