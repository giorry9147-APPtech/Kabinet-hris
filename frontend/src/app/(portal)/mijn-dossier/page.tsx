'use client';

import { useQuery } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { formatDate } from '@/lib/format';

const genderLabel: Record<string, string> = { m: 'Man', v: 'Vrouw', x: 'Anders/onbekend' };
const maritalLabel: Record<string, string> = {
  single: 'Ongehuwd',
  married: 'Gehuwd',
  divorced: 'Gescheiden',
  widowed: 'Weduw(e)naar',
  partner: 'Samenwonend',
};

export default function DossierPage() {
  const { data: profile, isLoading, isError } = useQuery({ queryKey: ['profile'], queryFn: portal.profile });
  const employmentQ = useQuery({ queryKey: ['employment'], queryFn: portal.employment });

  if (isLoading) return <div className="text-sm text-slate-500">Laden...</div>;
  if (isError || !profile) {
    return (
      <Card>
        <p className="text-sm text-slate-700">Geen dossier gekoppeld aan dit account.</p>
      </Card>
    );
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <PageHeader title="Mijn dossier" subtitle={`Personeelsnummer ${profile.employee_number}`} />

      <Card title="Persoonlijke gegevens">
        <dl className="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <Row label="Volledige naam">{profile.full_name}</Row>
          <Row label="Geboortedatum">{formatDate(profile.date_of_birth)}</Row>
          <Row label="Geslacht">{profile.gender ? genderLabel[profile.gender] : '—'}</Row>
          <Row label="Burgerlijke staat">
            {profile.marital_status ? maritalLabel[profile.marital_status] : '—'}
          </Row>
          <Row label="Nationaliteit">{profile.nationality ?? '—'}</Row>
          <Row label="ID-nummer">{profile.national_id ?? '—'}</Row>
        </dl>
      </Card>

      <Card title="Contact">
        <dl className="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <Row label="E-mail">{profile.email ?? '—'}</Row>
          <Row label="Telefoon">{profile.phone ?? '—'}</Row>
          <Row label="Adres" wide>
            {profile.address ?? '—'}
          </Row>
        </dl>
      </Card>

      <Card title="Huidige functie">
        {profile.position ? (
          <dl className="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <Row label="Functie">{profile.position.title}</Row>
            <Row label="Functiecode">{profile.position.code}</Row>
            <Row label="Afdeling">{profile.position.org_unit?.name ?? '—'}</Row>
            <Row label="Onder">{profile.position.org_unit?.parent_name ?? '—'}</Row>
            <Row label="In dienst sinds">{formatDate(profile.joined_at)}</Row>
            <Row label="Status">
              <span className="text-xs px-2 py-0.5 rounded bg-green-100 text-green-800">
                {profile.status}
              </span>
            </Row>
          </dl>
        ) : (
          <p className="text-sm text-slate-500">Geen actieve functie.</p>
        )}
      </Card>

      <Card title="Dienstverband-historiek">
        {employmentQ.isLoading ? (
          <p className="text-sm text-slate-500">Laden...</p>
        ) : (employmentQ.data?.length ?? 0) === 0 ? (
          <p className="text-sm text-slate-500">Geen historiek beschikbaar.</p>
        ) : (
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Functie</th>
                  <th className="px-5 py-2">Afdeling</th>
                  <th className="px-5 py-2">Vanaf</th>
                  <th className="px-5 py-2">T/m</th>
                  <th className="px-5 py-2">Reden</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {employmentQ.data?.map((r) => (
                  <tr key={r.id}>
                    <td className="px-5 py-2 font-medium text-slate-900">{r.position_title}</td>
                    <td className="px-5 py-2 text-slate-600">{r.org_unit ?? '—'}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(r.start_date)}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDate(r.end_date)}</td>
                    <td className="px-5 py-2 text-slate-600">{r.reason}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Card title="Foutje gespot?">
        <p className="text-sm text-slate-600">
          Controleer je gegevens regelmatig. Onjuistheden? Neem contact op met HR via{' '}
          <a href="mailto:hr@kabinet.sr" className="text-mas-blue-600 hover:underline">
            hr@kabinet.sr
          </a>
          .
        </p>
      </Card>
    </div>
  );
}

function Row({ label, children, wide }: { label: string; children: React.ReactNode; wide?: boolean }) {
  return (
    <div className={wide ? 'sm:col-span-2' : undefined}>
      <dt className="text-xs uppercase tracking-wide text-slate-500 mb-0.5">{label}</dt>
      <dd className="text-slate-900">{children}</dd>
    </div>
  );
}
