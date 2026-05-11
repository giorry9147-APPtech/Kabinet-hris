'use client';

import { useState, type ChangeEvent, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { portal } from '@/lib/portal-api';
import { Card, PageHeader } from '@/components/portal/page-header';
import { formatDateTime } from '@/lib/format';

const statusLabel: Record<string, string> = {
  pending: 'In review',
  approved: 'Goedgekeurd',
  rejected: 'Afgewezen',
};

const statusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
};

const ACCEPTED = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
const MAX_BYTES = 10 * 1024 * 1024;

export default function DocumentsPage() {
  const qc = useQueryClient();
  const { data, isLoading, isError } = useQuery({
    queryKey: ['documents'],
    queryFn: portal.documents,
  });

  const [title, setTitle] = useState('');
  const [category, setCategory] = useState('id_copy');
  const [notes, setNotes] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);

  const upload = useMutation({
    mutationFn: portal.uploadDocument,
    onSuccess: () => {
      setTitle('');
      setNotes('');
      setFile(null);
      setError(null);
      qc.invalidateQueries({ queryKey: ['documents'] });
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } };
      const errors = e?.response?.data?.errors;
      const firstKey = errors ? Object.keys(errors)[0] : null;
      setError(firstKey ? errors![firstKey][0] : (e?.response?.data?.message ?? 'Upload mislukt.'));
    },
  });

  const remove = useMutation({
    mutationFn: (id: number) => portal.deleteDocument(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['documents'] }),
  });

  function onFileChange(e: ChangeEvent<HTMLInputElement>) {
    const f = e.target.files?.[0] ?? null;
    if (f && f.size > MAX_BYTES) {
      setError('Bestand is groter dan 10 MB.');
      setFile(null);
      e.target.value = '';
      return;
    }
    setError(null);
    setFile(f);
  }

  function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    if (!file) {
      setError('Selecteer eerst een bestand.');
      return;
    }
    if (!title.trim()) {
      setError('Vul een titel in.');
      return;
    }
    upload.mutate({ title: title.trim(), category, notes: notes || undefined, file });
  }

  if (isLoading) return <div className="text-sm text-slate-500">Laden...</div>;
  if (isError) {
    return (
      <Card>
        <p className="text-sm text-slate-700">Geen dossier gekoppeld aan dit account.</p>
      </Card>
    );
  }

  const docs = data?.documents ?? [];
  const categories = data?.categories ?? {};

  return (
    <div className="space-y-6 max-w-4xl">
      <PageHeader
        title="Mijn documenten"
        subtitle="Upload documenten ter beoordeling door HR"
      />

      <Card title="Nieuw document uploaden">
        <form onSubmit={onSubmit} className="space-y-4">
          <Field label="Titel">
            <input
              type="text"
              required
              maxLength={255}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="bv. Diploma HBO Maritiem, Loonheffingsverklaring 2026"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            />
          </Field>

          <Field label="Categorie">
            <select
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            >
              {Object.entries(categories).map(([key, label]) => (
                <option key={key} value={key}>
                  {label}
                </option>
              ))}
            </select>
          </Field>

          <Field label="Bestand (PDF, JPG, PNG, DOC, max 10 MB)">
            <input
              type="file"
              accept={ACCEPTED}
              onChange={onFileChange}
              className="w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-mas-blue-600 file:text-white file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-mas-blue-700"
            />
            {file && (
              <p className="mt-1 text-xs text-slate-500">
                {file.name} — {(file.size / 1024).toFixed(0)} KB
              </p>
            )}
          </Field>

          <Field label="Toelichting (optioneel)">
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              maxLength={2000}
              placeholder="Korte toelichting voor HR..."
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-mas-blue-600 focus:outline-none"
            />
          </Field>

          {error && <p className="text-sm text-mas-red-600">{error}</p>}

          <div className="pt-2">
            <button
              type="submit"
              disabled={upload.isPending}
              className="bg-mas-blue-600 hover:bg-mas-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md disabled:opacity-60"
            >
              {upload.isPending ? 'Uploaden...' : 'Document indienen'}
            </button>
          </div>
        </form>
      </Card>

      <Card title="Mijn uploads">
        {docs.length === 0 ? (
          <p className="text-sm text-slate-500">Nog geen documenten ingediend.</p>
        ) : (
          <div className="overflow-x-auto -mx-5">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-5 py-2">Titel</th>
                  <th className="px-5 py-2">Categorie</th>
                  <th className="px-5 py-2">Geüpload</th>
                  <th className="px-5 py-2">Status</th>
                  <th className="px-5 py-2">Beslist door</th>
                  <th className="px-5 py-2"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {docs.map((d) => (
                  <tr key={d.id}>
                    <td className="px-5 py-2">
                      <div className="font-medium text-slate-900">{d.title}</div>
                      {d.notes && <div className="text-[11px] text-slate-500">{d.notes}</div>}
                      {d.status === 'rejected' && d.decision_notes && (
                        <div className="text-[11px] text-mas-red-700 mt-0.5">
                          Reden: {d.decision_notes}
                        </div>
                      )}
                    </td>
                    <td className="px-5 py-2 text-slate-700">{d.category_label}</td>
                    <td className="px-5 py-2 text-slate-600">{formatDateTime(d.created_at)}</td>
                    <td className="px-5 py-2">
                      <span className={`text-[11px] px-2 py-0.5 rounded ${statusClass[d.status] ?? ''}`}>
                        {statusLabel[d.status] ?? d.status}
                      </span>
                    </td>
                    <td className="px-5 py-2 text-slate-600">
                      {d.decider_name ?? '—'}
                      {d.decided_at && (
                        <div className="text-[11px] text-slate-400">{formatDateTime(d.decided_at)}</div>
                      )}
                    </td>
                    <td className="px-5 py-2 text-right space-x-2 whitespace-nowrap">
                      {d.file_url && (
                        <a
                          href={d.file_url}
                          target="_blank"
                          rel="noreferrer"
                          className="text-xs text-mas-blue-700 hover:underline"
                        >
                          Bekijk
                        </a>
                      )}
                      {d.can_delete && (
                        <button
                          onClick={() => {
                            if (confirm('Document verwijderen?')) remove.mutate(d.id);
                          }}
                          disabled={remove.isPending}
                          className="text-xs text-mas-red-600 hover:underline disabled:opacity-50"
                        >
                          Verwijder
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

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1">
      <label className="block text-xs uppercase tracking-wide text-slate-500 font-medium">{label}</label>
      {children}
    </div>
  );
}
