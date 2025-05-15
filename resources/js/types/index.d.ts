import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';
import type { Server } from '@/types/server';
import { Project } from '@/types/project';
import { User } from '@/types/user';

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
  activePath?: string;
  icon?: LucideIcon | null;
  isActive?: boolean;
}

export interface Configs {
  server_providers: string[];
  server_providers_custom_fields: {
    [provider: string]: string[];
  };
  operating_systems: string[];
  service_versions: {
    [service: string]: string[];
  };
  webservers: string[];
  databases: string[];
  php_versions: string[];

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
  server?: Server;
  publicKeyText: string;

  [key: string]: unknown;
}
