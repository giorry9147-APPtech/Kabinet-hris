'use client';

import { useQuery } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { certStatusClass, certStatusLabel, formatDate } from '@/lib/format';

export default function CertsPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['certificates'],
    queryFn: portal.certificates,
  });

  if (isLoading) return <div className="text-sm text-slate-500">Laden...</div>;
  if (isError) {
    return (
      <Card>
        <p className="text-sm text-slate-700">Geen dossier gekoppeld aan dit account.</p>
      </Card>
    );
  }

  const certs = data ?? [];
  const expiring = certs.filter((c) => c.status === 'expired' || c.status === 'expiring_soon');

  return (
    <div className="space-y-6 max-w-5xl">
      <PageHeader
        title="Mijn certificaten"
        subtitle={
          expiring.length > 0
            ? `${expiring.length} certificaat${expiring.length === 1 ? '' : 'en'} vraagt aandacht`
            : 'Alles op orde'
        }
      />

      {certs.length === 0 ? (
        <Card>
          <p className="text-sm text-slate-500">Nog geen certificaten geregistreerd.</p>
        </Card>
      ) : (
        <div className="grid md:grid-cols-2 gap-4">
          {certs.map((c) => (
            <div
              key={c.id}
              className="bg-white border border-slate-200 rounded-lg p-5 shadow-sm space-y-3"
            >
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <div className="text-xs uppercase tracking-wide text-slate-500">{c.type_category ?? '—'}</div>
                  <div className="font-semibold text-mas-blue-700 leading-tight">{c.type_name}</div>
                </div>
                <span className={`text-[11px] px-2 py-0.5 rounded shrink-0 ${certStatusClass[c.status]}`}>
                  {certStatusLabel[c.status]}
                </span>
              </div>

              <dl className="text-xs text-slate-600 space-y-1">
                {c.number && (
                  <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Nummer</dt>
                    <dd className="font-mono">{c.number}</dd>
                  </div>
                )}
                {c.issuer && (
                  <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Uitgever</dt>
                    <dd>{c.issuer}</dd>
                  </div>
                )}
                <div className="flex justify-between gap-3">
                  <dt className="text-slate-500">Uitgegeven</dt>
                  <dd>{formatDate(c.issued_at)}</dd>
                </div>
                <div className="flex justify-between gap-3">
                  <dt className="text-slate-500">Vervalt</dt>
                  <dd>
                    {formatDate(c.expires_at)}
                    {c.days_until_expiry !== null && c.status === 'expiring_soon' && (
                      <span className="ml-1 text-amber-700">
                        ({c.days_until_expiry > 0 ? `nog ${c.days_until_expiry}d` : 'vandaag'})
                      </span>
                    )}
                  </dd>
                </div>
              </dl>

              {c.file_url && (
                <a
                  href={c.file_url}
                  target="_blank"
                  rel="noreferrer"
                  className="text-xs text-mas-blue-600 hover:underline inline-block"
                >
                  Bestand openen ↗
                </a>
              )}
            </div>
          ))}
        </div>
      )}

      <Card>
        <p className="text-xs text-slate-500">
          Verlopen of ontbrekende certificaten? Neem contact op met HR via{' '}
          <a href="mailto:hr@kabinet.sr" className="text-mas-blue-600 hover:underline">
            hr@kabinet.sr
          </a>
          .
        </p>
      </Card>
    </div>
  );
}
