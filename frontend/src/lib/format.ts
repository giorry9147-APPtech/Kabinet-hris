export function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('nl-NL', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('nl-NL', { dateStyle: 'short', timeStyle: 'short' });
}

export function formatCurrency(value: number | string | null | undefined, currency = 'SRD'): string {
  if (value === null || value === undefined) return '—';
  const n = typeof value === 'string' ? Number(value) : value;
  if (Number.isNaN(n)) return '—';
  return `${currency} ${n.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export const leaveTypeLabel: Record<string, string> = {
  vacation: 'Vakantie',
  sick: 'Ziekte',
  special: 'Bijzonder verlof',
  unpaid: 'Onbetaald',
  maternity: 'Zwangerschap/bevalling',
  study: 'Studieverlof',
};

export const leaveStatusLabel: Record<string, string> = {
  pending: 'In behandeling',
  approved: 'Goedgekeurd',
  rejected: 'Afgewezen',
  cancelled: 'Ingetrokken',
};

export const leaveStatusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-green-100 text-green-800',
  rejected: 'bg-mas-red-100 text-mas-red-700',
  cancelled: 'bg-slate-200 text-slate-700',
};

export const certStatusClass: Record<string, string> = {
  expired: 'bg-mas-red-100 text-mas-red-700',
  expiring_soon: 'bg-amber-100 text-amber-800',
  valid: 'bg-green-100 text-green-800',
  no_expiry: 'bg-slate-200 text-slate-700',
};

export const certStatusLabel: Record<string, string> = {
  expired: 'Verlopen',
  expiring_soon: 'Vervalt < 90d',
  valid: 'Geldig',
  no_expiry: 'Geen vervaldatum',
};
