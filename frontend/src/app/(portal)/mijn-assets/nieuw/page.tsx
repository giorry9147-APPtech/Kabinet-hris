'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';

const categories = [
  'Laptop',
  'Mobiel',
  'Tablet',
  'Voertuig',
  'Radio',
  'Werkkleding',
  'Overig',
];

export default function NewAssetRequestPage() {
  const router = useRouter();
  const qc = useQueryClient();

  const [category, setCategory] = useState(categories[0]);
  const [subject, setSubject] = useState('');
  const [neededBy, setNeededBy] = useState('');
  const [reason, setReason] = useState('');
  const [error, setError] = useState<string | null>(null);

  const submit = useMutation({
    mutationFn: portal.submitAssetRequest,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['asset-requests'] });
      router.push('/mijn-assets');
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
    if (!subject.trim()) {
      setError('Vul een onderwerp in.');
      return;
    }
    submit.mutate({
      category,
      subject: subject.trim(),
      reason: reason || undefined,
      needed_by: neededBy || undefined,
    });
  }

  return (
    <div className="max-w-2xl space-y-6">
      <PageHeader title="Nieuwe asset-aanvraag" subtitle="Vraag een laptop, telefoon of ander item aan" />

      <Card>
        <form onSubmit={onSubmit} className="space-y-4">
          <Field label="Categorie">
            <select
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            >
              {categories.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>
          </Field>

          <Field label="Onderwerp">
            <input
              type="text"
              required
              maxLength={255}
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="bv. Vervanging laptop, headset voor VTS-dienst, ..."
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            />
          </Field>

          <Field label="Nodig per (optioneel)">
            <input
              type="date"
              value={neededBy}
              onChange={(e) => setNeededBy(e.target.value)}
              min={new Date().toISOString().slice(0, 10)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            />
          </Field>

          <Field label="Toelichting">
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={4}
              maxLength={2000}
              placeholder="Waarom heb je dit nodig?"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
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
            <Link href="/mijn-assets" className="text-sm text-slate-600 hover:underline">
              Annuleren
            </Link>
          </div>
        </form>
      </Card>

      <Card>
        <p className="text-xs text-slate-500">
          Een afdelingshoofd of HR neemt de aanvraag in behandeling. Je krijgt een melding zodra er een
          beslissing is.
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
