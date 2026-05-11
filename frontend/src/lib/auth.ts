import { api, TOKEN_KEY } from './api';

export type CurrentUser = {
  id: number;
  name: string;
  email: string;
  is_active: boolean;
  roles: string[];
  permissions: string[];
  employee: {
    id: number;
    first_name: string;
    last_name: string;
    employee_number: string;
  } | null;
};

export function isAdmin(user: CurrentUser): boolean {
  return user.roles.some((r) => ['super_admin', 'hr_manager', 'hr_admin', 'dept_head', 'finance'].includes(r));
}

export async function login(email: string, password: string): Promise<CurrentUser> {
  const { data } = await api.post<{ token: string; user: CurrentUser }>('/auth/login', {
    email,
    password,
    device_name: 'mas-portal-web',
  });
  if (typeof window !== 'undefined') {
    window.localStorage.setItem(TOKEN_KEY, data.token);
  }
  return data.user;
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout');
  } finally {
    if (typeof window !== 'undefined') {
      window.localStorage.removeItem(TOKEN_KEY);
    }
  }
}

export async function fetchMe(): Promise<CurrentUser> {
  const { data } = await api.get<{ user: CurrentUser }>('/auth/me');
  return data.user;
}

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null;
  return window.localStorage.getItem(TOKEN_KEY);
}
