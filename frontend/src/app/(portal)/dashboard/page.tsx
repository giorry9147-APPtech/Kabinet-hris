'use client';

import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { certStatusClass, certStatusLabel, formatCurrency, formatDate } from '@/lib/format';

export default function DashboardPage() {
  const profileQ = useQuery({ queryKey: ['profile'], queryFn: portal.profile });
  const leaveQ = useQuery({ queryKey: ['leave'], queryFn: portal.leave });
  const certsQ = useQuery({ queryKey: ['certificates'], queryFn: portal.certificates });
  const salaryQ = useQuery({ queryKey: ['salary'], queryFn: portal.salary });
  const assetsQ = useQuery({ queryKey: ['assets'], queryFn: portal.assets });

  if (profileQ.isError) {
    return (
      <div className="max-w-2xl">
        <PageHeader title="Welkom" />
        <Card>
          <p className="text-sm text-slate-700">
            Aan jouw account is geen medewerkersdossier gekoppeld. Neem contact op met HR.
          </p>
        </Card>
      </div>
    );
  }

  const profile = profileQ.data;
  const balance = leaveQ.data?.balance;
  const expiringCerts = certsQ.data?.filter((c) => c.status === 'expired' || c.status === 'expiring_soon') ?? [];
  const activeAssets = assetsQ.data?.filter((a) => a.is_active) ?? [];
  const pendingLeave = leaveQ.data?.requests.filter((r) => r.status === 'pending') ?? [];

  return (
    <div className="space-y-6 max-w-5xl">
      <PageHeader
        title={profile ? `Hallo, ${profile.first_name}` : 'Welkom'}
        subtitle={
          profile?.position
            ? `${profile.position.title} — ${profile.position.org_unit?.name ?? ''}`
            : undefined
        }
      />

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Stat
          title="Verlofsaldo"
          value={balance ? `${balance.remaining}` : '—'}
          unit="dagen"
          subtext={balance ? `${balance.used} gebruikt van ${balance.yearly_total}` : undefined}
          color="blue"
        />
        <Stat
          title="Certificaten met aandacht"
          value={`${expiringCerts.length}`}
          unit={expiringCerts.length === 1 ? 'certificaat' : 'certificaten'}
          subtext={expiringCerts.length === 0 ? 'alles in orde' : 'verlopen of < 90 dagen'}
          color={expiringCerts.length > 0 ? 'red' : 'green'}
        />
        <Stat
          title="Assets in gebruik"
          value={`${activeAssets.length}`}
          unit={activeAssets.length === 1 ? 'item' : 'items'}
          color="blue"
        />
        <Stat
          title="Verlof in behandeling"
          value={`${pendingLeave.length}`}
          unit={pendingLeave.length === 1 ? 'aanvraag' : 'aanvragen'}
          color={pendingLeave.length > 0 ? 'amber' : 'gray'}
        />
      </div>

      <div className="grid lg:grid-cols-2 gap-4">
        <Card
          title="Certificaten met aandacht"
          action={
            <Link href="/mijn-certificaten" className="text-xs text-mas-blue-600 hover:underline">
              Alles tonen →
            </Link>
          }
        >
          {expiringCerts.length === 0 ? (
            <p className="text-sm text-slate-500">Geen certificaten die binnenkort verlopen.</p>
          ) : (
            <ul className="space-y-2">
              {expiringCerts.slice(0, 5).map((c) => (
                <li key={c.id} className="flex items-center justify-between text-sm">
                  <div>
                    <div className="font-medium text-slate-900">{c.type_name}</div>
                    <div className="text-xs text-slate-500">{formatDate(c.expires_at)}</div>
                  </div>
                  <span className={`text-[11px] px-2 py-0.5 rounded ${certStatusClass[c.status]}`}>
                    {certStatusLabel[c.status]}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Card>

        <Card
          title="Mijn salaris (huidig)"
          action={
            <Link href="/mijn-salaris" className="text-xs text-mas-blue-600 hover:underline">
              Detail →
            </Link>
          }
        >
          {salaryQ.data?.current ? (
            <div className="space-y-1">
              <div className="text-2xl font-semibold text-mas-blue-700">
                {formatCurrency(
                  Number(salaryQ.data.current.base_amount) + Number(salaryQ.data.current.allowances),
                  salaryQ.data.current.currency,
                )}
              </div>
              <p className="text-sm text-slate-600">
                Basis {formatCurrency(salaryQ.data.current.base_amount, salaryQ.data.current.currency)} +
                toelagen {formatCurrency(salaryQ.data.current.allowances, salaryQ.data.current.currency)}
              </p>
              <p className="text-xs text-slate-500 mt-1">
                Sinds {formatDate(salaryQ.data.current.start_date)}
              </p>
            </div>
          ) : (
            <p className="text-sm text-slate-500">Geen actieve salarisinformatie.</p>
          )}
        </Card>
      </div>
    </div>
  );
}

function Stat({
  title,
  value,
  unit,
  subtext,
  color,
}: {
  title: string;
  value: string;
  unit: string;
  subtext?: string;
  color: 'blue' | 'red' | 'green' | 'amber' | 'gray';
}) {
  const colorMap = {
    blue: 'text-mas-blue-700',
    red: 'text-mas-red-600',
    green: 'text-green-700',
    amber: 'text-amber-600',
    gray: 'text-slate-700',
  };
  return (
    <div className="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
      <div className="text-[11px] uppercase tracking-wide text-slate-500 font-medium">{title}</div>
      <div className="mt-2 flex items-baseline gap-1.5">
        <div className={`text-3xl font-semibold ${colorMap[color]}`}>{value}</div>
        <div className="text-xs text-slate-500">{unit}</div>
      </div>
      {subtext && <div className="text-[11px] text-slate-500 mt-1">{subtext}</div>}
    </div>
  );
}
