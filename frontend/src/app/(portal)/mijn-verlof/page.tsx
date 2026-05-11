'use client';

import Link from 'next/link';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import {
  formatDate,
  formatDateTime,
  leaveStatusClass,
  leaveStatusLabel,
  leaveTypeLabel,
} from '@/lib/format';

export default function LeavePage() {
  const qc = useQueryClient();
  const { data, isLoading, isError } = useQuery({ queryKey: ['leave'], queryFn: portal.leave });

  const cancel = useMutation({
    mutationFn: (id: number) => portal.cancelLeave(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['leave'] }),
  });

  if (isLoading) return <div className="text-sm text-slate-500">Laden...</div>;
  if (isError || !data) {
    return (
      <Card>
        <p className="text-sm text-slate-700">Geen dossier gekoppeld aan dit account.</p>
      </Card>
    );
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="flex items-center justify-between">
        <PageHeader title="Mijn verlof" subtitle={`Saldo voor ${data.year}`} />
        <Link
          href="/mijn-verlof/nieuw"
          className="bg-mas-blue-600 hover:bg-mas-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md"
        >
          + Nieuwe aanvraag
        </Link>
      </div>

      <div className="grid sm:grid-cols-3 gap-4">
        <BalanceStat label="Totaal" value={data.balance.yearly_total} suffix="dagen/jaar" />
        <BalanceStat label="Gebruikt" value={data.balance.used} color="amber" />
        <BalanceStat label="Resterend" value={data.balance.remaining} color="green" />
      </div>

      <Card title="Mijn aanvragen">
        {data.requests.length === 0 ? (
          <p className="text-sm text-slate-500">Nog geen verlofaanvragen ingediend.</p>
        ) : (
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Soort</th>
                  <th className="px-5 py-2">Vanaf</th>
                  <th className="px-5 py-2">T/m</th>
                  <th className="px-5 py-2">Dagen</th>
                  <th className="px-5 py-2">Status</th>
                  <th className="px-5 py-2">Beslist door</th>
                  <th className="px-5 py-2"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {data.requests.map((r) => (
                  <tr key={r.id}>
                    <td className="px-5 py-2 font-medium text-slate-900">{leaveTypeLabel[r.type] ?? r.type}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(r.start_date)}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(r.end_date)}</td>
                    <td className="px-5 py-2 text-slate-600">{r.days_count}</td>
                    <td className="px-5 py-2">
                      <span className={`text-[11px] px-2 py-0.5 rounded ${leaveStatusClass[r.status]}`}>
                        {leaveStatusLabel[r.status] ?? r.status}
                      </span>
                    </td>
                    <td className="px-5 py-2 text-slate-600">
                      {r.approver_name ?? '—'}
                      {r.decided_at && (
                        <div className="text-[11px] text-slate-400">{formatDateTime(r.decided_at)}</div>
                      )}
                    </td>
                    <td className="px-5 py-2 text-right">
                      {r.can_cancel && (
                        <button
                          onClick={() => {
                            if (confirm('Aanvraag intrekken?')) cancel.mutate(r.id);
                          }}
                          disabled={cancel.isPending}
                          className="text-xs text-mas-red-600 hover:underline disabled:opacity-50"
                        >
                          Intrekken
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}

function BalanceStat({
  label,
  value,
  suffix,
  color = 'blue',
}: {
  label: string;
  value: number;
  suffix?: string;
  color?: 'blue' | 'amber' | 'green';
}) {
  const c = { blue: 'text-mas-blue-700', amber: 'text-amber-600', green: 'text-green-700' }[color];
  return (
    <div className="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
      <div className="text-[11px] uppercase tracking-wide text-slate-500 font-medium">{label}</div>
      <div className="mt-2 flex items-baseline gap-1.5">
        <div className={`text-3xl font-semibold ${c}`}>{value}</div>
        <div className="text-xs text-slate-500">{suffix ?? 'dagen'}</div>
      </div>
    </div>
  );
}
