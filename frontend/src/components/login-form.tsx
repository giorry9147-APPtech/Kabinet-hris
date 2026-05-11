'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import { login } from '@/lib/auth';

export function LoginForm() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login(email, password);
      router.push('/dashboard');
    } catch (err) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Inloggen mislukt — controleer je gegevens.';
      setError(message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5" autoComplete="on">
      <div className="space-y-1.5">
        <label htmlFor="email" className="text-sm font-medium text-slate-700">
          E-mailadres
        </label>
        <input
          id="email"
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-mas-blue-600 focus:outline-none focus:ring-2 focus:ring-mas-blue-600/20"
          autoComplete="email"
          placeholder="naam@kabinet.sr"
        />
      </div>

      <div className="space-y-1.5">
        <label htmlFor="password" className="text-sm font-medium text-slate-700">
          Wachtwoord
        </label>
        <input
          id="password"
          type="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-mas-blue-600 focus:outline-none focus:ring-2 focus:ring-mas-blue-600/20"
          autoComplete="current-password"
          placeholder="••••••••"
        />
      </div>

      {error && (
        <p className="text-sm text-mas-red-500 bg-mas-red-50 border border-mas-red-100 rounded-md px-3 py-2">
          {error}
        </p>
      )}

      <button
        type="submit"
        disabled={loading}
        className="w-full rounded-lg bg-mas-red-500 hover:bg-mas-red-600 text-white text-sm font-semibold py-3 transition-colors disabled:opacity-60 disabled:cursor-not-allowed shadow-sm"
      >
        {loading ? 'Bezig met inloggen…' : 'Inloggen'}
      </button>
    </form>
  );
}
