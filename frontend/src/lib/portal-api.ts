import { api } from './api';

// ----- Types -----

export type Profile = {
  id: number;
  employee_number: string;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  full_name: string;
  date_of_birth: string | null;
  gender: 'm' | 'v' | 'x' | null;
  marital_status: string | null;
  nationality: string | null;
  national_id: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  status: string;
  joined_at: string | null;
  avatar_url: string | null;
  position: {
    id: number;
    title: string;
    code: string;
    org_unit: { name: string; parent_name: string | null } | null;
  } | null;
};

export type EmploymentRecord = {
  id: number;
  position_title: string | null;
  org_unit: string | null;
  start_date: string | null;
  end_date: string | null;
  status: string;
  reason: string;
  notes: string | null;
};

export type SalaryAssignment = {
  id: number;
  grade_code: string | null;
  schaal: number | null;
  trede: number | null;
  base_amount: number;
  allowances: number;
  total: number;
  currency: string;
  start_date: string | null;
  end_date: string | null;
};

export type Certificate = {
  id: number;
  type_name: string | null;
  type_category: string | null;
  number: string | null;
  issuer: string | null;
  issued_at: string | null;
  expires_at: string | null;
  status: 'no_expiry' | 'expired' | 'expiring_soon' | 'valid';
  days_until_expiry: number | null;
  file_url: string | null;
};

export type LeaveRequest = {
  id: number;
  type: 'vacation' | 'sick' | 'special' | 'unpaid' | 'maternity' | 'study';
  start_date: string;
  end_date: string;
  days_count: number;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled';
  reason: string | null;
  approver_name: string | null;
  decided_at: string | null;
  decision_reason: string | null;
  created_at: string;
  can_cancel: boolean;
};

export type LeaveBalance = {
  yearly_total: number;
  used: number;
  remaining: number;
};

export type AssetAssignment = {
  id: number;
  asset_code: string | null;
  asset_name: string | null;
  category: string | null;
  serial_number: string | null;
  assigned_at: string | null;
  returned_at: string | null;
  condition_at_assignment: string | null;
  is_active: boolean;
};

export type AssetRequest = {
  id: number;
  category: string;
  subject: string;
  reason: string | null;
  needed_by: string | null;
  status: 'pending' | 'approved' | 'rejected' | 'fulfilled' | 'cancelled';
  decider_name: string | null;
  decided_at: string | null;
  decision_reason: string | null;
  created_at: string;
  can_cancel: boolean;
};

export type EmployeeDocument = {
  id: number;
  title: string;
  category: string;
  category_label: string;
  notes: string | null;
  status: 'pending' | 'approved' | 'rejected';
  decider_name: string | null;
  decided_at: string | null;
  decision_notes: string | null;
  file_url: string | null;
  file_name: string | null;
  created_at: string;
  can_delete: boolean;
};

// ----- Fetchers -----

export const portal = {
  profile: async () => (await api.get<{ employee: Profile }>('/me/profile')).data.employee,
  employment: async () => (await api.get<{ records: EmploymentRecord[] }>('/me/employment')).data.records,
  salary: async () => (await api.get<{ current: SalaryAssignment | null; history: SalaryAssignment[] }>('/me/salary')).data,
  certificates: async () => (await api.get<{ certificates: Certificate[] }>('/me/certificates')).data.certificates,
  leave: async () => (await api.get<{ year: number; balance: LeaveBalance; requests: LeaveRequest[] }>('/me/leave')).data,
  submitLeave: async (payload: { type: string; start_date: string; end_date: string; reason?: string }) =>
    (await api.post('/me/leave', payload)).data,
  cancelLeave: async (id: number) => (await api.patch(`/me/leave/${id}/cancel`)).data,
  assets: async () => (await api.get<{ assignments: AssetAssignment[] }>('/me/assets')).data.assignments,
  assetRequests: async () =>
    (await api.get<{ requests: AssetRequest[] }>('/me/asset-requests')).data.requests,
  submitAssetRequest: async (payload: {
    category: string;
    subject: string;
    reason?: string;
    needed_by?: string;
  }) => (await api.post('/me/asset-requests', payload)).data,
  cancelAssetRequest: async (id: number) =>
    (await api.patch(`/me/asset-requests/${id}/cancel`)).data,
  documents: async () =>
    (await api.get<{ categories: Record<string, string>; documents: EmployeeDocument[] }>(
      '/me/documents',
    )).data,
  uploadDocument: async (payload: { title: string; category: string; notes?: string; file: File }) => {
    const fd = new FormData();
    fd.append('title', payload.title);
    fd.append('category', payload.category);
    if (payload.notes) fd.append('notes', payload.notes);
    fd.append('file', payload.file);
    return (await api.post('/me/documents', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })).data;
  },
  deleteDocument: async (id: number) => (await api.delete(`/me/documents/${id}`)).data,
};
