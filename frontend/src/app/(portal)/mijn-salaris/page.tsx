'use client';

import { useQuery } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { formatCurrency, formatDate } from '@/lib/format';

export default function SalaryPage() {
  const { data, isLoading, isError } = useQuery({ queryKey: ['salary'], queryFn: portal.salary });

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
      <PageHeader title="Mijn salaris" />

      <Card title="Huidig salaris">
        {data.current ? (
          <div className="space-y-4">
            <div>
              <div className="text-[11px] uppercase tracking-wide text-slate-500 font-medium">
                Bruto totaal per maand
              </div>
              <div className="text-3xl font-semibold text-mas-blue-700 mt-1">
                {formatCurrency(
                  Number(data.current.base_amount) + Number(data.current.allowances),
                  data.current.currency,
                )}
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100">
              <Stat label="Basisbedrag" value={formatCurrency(data.current.base_amount, data.current.currency)} />
              <Stat label="Toelagen" value={formatCurrency(data.current.allowances, data.current.currency)} />
              <Stat label="Ingangsdatum" value={formatDate(data.current.start_date)} />
              <Stat label="Einddatum" value={data.current.end_date ? formatDate(data.current.end_date) : 'lopend'} />
            </div>
          </div>
        ) : (
          <p className="text-sm text-slate-500">Geen actieve salarisinformatie.</p>
        )}
      </Card>

      <Card title="Salaris-historiek">
        {data.history.length === 0 ? (
          <p className="text-sm text-slate-500">Geen historiek beschikbaar.</p>
        ) : (
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Schaal</th>
                  <th className="px-5 py-2 text-right">Basis</th>
                  <th className="px-5 py-2 text-right">Toelagen</th>
                  <th className="px-5 py-2 text-right">Totaal</th>
                  <th className="px-5 py-2">Vanaf</th>
                  <th className="px-5 py-2">T/m</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {data.history.map((s) => (
                  <tr key={s.id}>
                    <td className="px-5 py-2 font-mono text-slate-700">
                      {s.grade_code ?? `S${s.schaal ?? '?'}-T${s.trede ?? '?'}`}
                    </td>
                    <td className="px-5 py-2 text-right text-slate-700">
                      {formatCurrency(s.base_amount, s.currency)}
                    </td>
                    <td className="px-5 py-2 text-right text-slate-700">
                      {formatCurrency(s.allowances, s.currency)}
                    </td>
                    <td className="px-5 py-2 text-right font-semibold text-mas-blue-700">
                      {formatCurrency(s.total, s.currency)}
                    </td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(s.start_date)}</td>
                    <td className="px-5 py-2 text-slate-600">
                      {s.end_date ? formatDate(s.end_date) : <span className="text-green-700">lopend</span>}
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

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
      <div className="text-sm text-slate-900 mt-0.5 font-medium">{value}</div>
    </div>
  );
}
