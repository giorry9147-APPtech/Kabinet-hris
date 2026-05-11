'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';

const types = [
  { value: 'vacation', label: 'Vakantie' },
  { value: 'sick', label: 'Ziekte' },
  { value: 'special', label: 'Bijzonder verlof' },
  { value: 'unpaid', label: 'Onbetaald' },
  { value: 'maternity', label: 'Zwangerschap/bevalling' },
  { value: 'study', label: 'Studieverlof' },
];

export default function NewLeavePage() {
  const router = useRouter();
  const qc = useQueryClient();

  const [type, setType] = useState('vacation');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');
  const [error, setError] = useState<string | null>(null);

  const submit = useMutation({
    mutationFn: portal.submitLeave,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['leave'] });
      router.push('/mijn-verlof');
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } };
      const errors = e?.response?.data?.errors;
      const firstKey = errors ? Object.keys(errors)[0] : null;
      const msg = firstKey ? errors![firstKey][0] : (e?.response?.data?.message ?? 'Indienen mislukt.');
      setError(msg);
    },
  });

  function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    if (!startDate || !endDate) {
      setError('Vul start- en einddatum in.');
      return;
    }
    submit.mutate({ type, start_date: startDate, end_date: endDate, reason: reason || undefined });
  }

  return (
    <div className="max-w-2xl space-y-6">
      <PageHeader title="Nieuwe verlofaanvraag" subtitle="Vul de gegevens in en dien in" />

      <Card>
        <form onSubmit={onSubmit} className="space-y-4">
          <Field label="Soort verlof">
            <select
              value={type}
              onChange={(e) => setType(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            >
              {types.map((t) => (
                <option key={t.value} value={t.value}>
                  {t.label}
                </option>
              ))}
            </select>
          </Field>

          <div className="grid sm:grid-cols-2 gap-4">
            <Field label="Begindatum">
              <input
                type="date"
                required
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
              />
            </Field>
            <Field label="Einddatum">
              <input
                type="date"
                required
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
              />
            </Field>
          </div>

          <Field label="Reden / toelichting">
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={4}
              maxLength={1000}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
              placeholder="Optioneel"
            />
          </Field>

          {error && <p className="text-sm text-mas-red-600">{error}</p>}

          <div className="flex items-center gap-3 pt-2">
            <button
              type="submit"
              disabled={submit.isPending}
              className="bg-mas-blue-600 hover:bg-mas-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md disabled:opacity-60"
            >
              {submit.isPending ? 'Bezig...' : 'Aanvraag indienen'}
            </button>
            <Link href="/mijn-verlof" className="text-sm text-slate-600 hover:underline">
              Annuleren
            </Link>
          </div>
        </form>
      </Card>

      <Card>
        <p className="text-xs text-slate-500">
          Weekenddagen tellen niet mee in het aantal dagen. Een afdelingshoofd of HR neemt de aanvraag in
          behandeling. Je krijgt een melding zodra er een beslissing is.
        </p>
      </Card>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1">
      <label className="block text-xs uppercase tracking-wide text-slate-500 font-medium">{label}</label>
      {children}
    </div>
  );
}
