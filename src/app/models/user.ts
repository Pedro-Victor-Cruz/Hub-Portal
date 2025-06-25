
export interface User {
  id: number;
  email: string;
  name: string;
  company_id: number | null;
  status: string;
  is_super_admin: boolean;
  last_login: string | null;
  group: PermissionGroup;
  permissions: Permission[];
}

export interface PermissionGroup {
  id: number;
  name: string;
  description: string;
  is_system: boolean;
  company_id: number | null;
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
