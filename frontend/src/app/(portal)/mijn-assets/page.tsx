'use client';

import Link from 'next/link';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { formatDate, formatDateTime } from '@/lib/format';

const statusLabel: Record<string, string> = {
  pending: 'In behandeling',
  approved: 'Goedgekeurd',
  rejected: 'Afgewezen',
  fulfilled: 'Geleverd',
  cancelled: 'Ingetrokken',
};

const statusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  fulfilled: 'bg-blue-100 text-blue-800',
  cancelled: 'bg-slate-100 text-slate-700',
};

export default function AssetsPage() {
  const qc = useQueryClient();
  const { data, isLoading, isError } = useQuery({ queryKey: ['assets'], queryFn: portal.assets });
  const requestsQ = useQuery({ queryKey: ['asset-requests'], queryFn: portal.assetRequests });

  const cancel = useMutation({
    mutationFn: (id: number) => portal.cancelAssetRequest(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['asset-requests'] }),
  });

  if (isLoading) return <div className="text-sm text-slate-500">Laden...</div>;
  if (isError) {
    return (
      <Card>
        <p className="text-sm text-slate-700">Geen dossier gekoppeld aan dit account.</p>
      </Card>
    );
  }

  const all = data ?? [];
  const active = all.filter((a) => a.is_active);
  const past = all.filter((a) => !a.is_active);
  const requests = requestsQ.data ?? [];

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="flex items-start justify-between gap-4">
        <PageHeader
          title="Mijn assets"
          subtitle={
            active.length === 0
              ? 'Geen items in gebruik'
              : `${active.length} item${active.length === 1 ? '' : 's'} in gebruik`
          }
        />
        <Link
          href="/mijn-assets/nieuw"
          className="bg-mas-blue-600 hover:bg-mas-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md whitespace-nowrap"
        >
          + Nieuwe aanvraag
        </Link>
      </div>

      <Card title="Op dit moment in gebruik">
        {active.length === 0 ? (
          <p className="text-sm text-slate-500">Geen actieve toewijzingen.</p>
        ) : (
          <div className="grid sm:grid-cols-2 gap-3">
            {active.map((a) => (
              <div key={a.id} className="border border-slate-200 rounded-md p-4 bg-slate-50">
                <div className="flex items-start justify-between mb-2">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">{a.category ?? '—'}</div>
                    <div className="font-semibold text-mas-blue-700">{a.asset_name}</div>
                    <div className="text-[11px] text-slate-500 font-mono mt-0.5">{a.asset_code}</div>
                  </div>
                  <span className="text-[11px] px-2 py-0.5 rounded bg-green-100 text-green-800 shrink-0">
                    in gebruik
                  </span>
                </div>
                <dl className="text-xs text-slate-600 space-y-1 mt-3">
                  {a.serial_number && (
                    <div className="flex justify-between gap-3">
                      <dt className="text-slate-500">Serienummer</dt>
                      <dd className="font-mono">{a.serial_number}</dd>
                    </div>
                  )}
                  <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Sinds</dt>
                    <dd>{formatDate(a.assigned_at)}</dd>
                  </div>
                  {a.condition_at_assignment && (
                    <div className="flex justify-between gap-3">
                      <dt className="text-slate-500">Staat</dt>
                      <dd>{a.condition_at_assignment}</dd>
                    </div>
                  )}
                </dl>
              </div>
            ))}
          </div>
        )}
      </Card>

      <Card title="Mijn aanvragen">
        {requestsQ.isLoading ? (
          <p className="text-sm text-slate-500">Aanvragen laden...</p>
        ) : requests.length === 0 ? (
          <p className="text-sm text-slate-500">Nog geen aanvragen ingediend.</p>
        ) : (
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Categorie</th>
                  <th className="px-5 py-2">Onderwerp</th>
                  <th className="px-5 py-2">Nodig per</th>
                  <th className="px-5 py-2">Status</th>
                  <th className="px-5 py-2">Beslist door</th>
                  <th className="px-5 py-2"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {requests.map((r) => (
                  <tr key={r.id}>
                    <td className="px-5 py-2 text-slate-700">{r.category}</td>
                    <td className="px-5 py-2 font-medium text-slate-900">{r.subject}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(r.needed_by)}</td>
                    <td className="px-5 py-2">
                      <span className={`text-[11px] px-2 py-0.5 rounded ${statusClass[r.status] ?? ''}`}>
                        {statusLabel[r.status] ?? r.status}
                      </span>
                    </td>
                    <td className="px-5 py-2 text-slate-600">
                      {r.decider_name ?? '—'}
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

      {past.length > 0 && (
        <Card title="Geretourneerd">
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Code</th>
                  <th className="px-5 py-2">Naam</th>
                  <th className="px-5 py-2">Vanaf</th>
                  <th className="px-5 py-2">Geretourneerd</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {past.map((a) => (
                  <tr key={a.id}>
                    <td className="px-5 py-2 font-mono text-slate-700">{a.asset_code}</td>
                    <td className="px-5 py-2 text-slate-900">{a.asset_name}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(a.assigned_at)}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(a.returned_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </div>
  );
}
