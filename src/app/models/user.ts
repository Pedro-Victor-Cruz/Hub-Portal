
export interface User {
  id: number;
  email: string;
  name: string;
  status: string;
  is_super_admin: boolean;
  last_login: string | null;
  group: PermissionGroup | null;
  permissions: Permission[];
  client: Client | null;
  available_clients: Client[] | null;
}

export interface Client {
  id: number;
  name: string;
  slug: string;
  domain: string;
  active: boolean;
}

export interface PermissionGroup {
  id: number;
  name: string;
  description: string;
  is_system: boolean;
}

export interface Permission {
  name: string;
  description: string;
  group: string;
  source: 'group' | 'user';
  is_active: boolean;
  group_name: string | null;
  group_id: number | null;
}
