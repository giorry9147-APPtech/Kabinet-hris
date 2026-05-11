import Link from 'next/link';
import { LoginForm } from '@/components/login-form';

export default function LoginPage() {
  return (
    <main className="flex flex-1 items-center justify-center px-6 py-16 bg-slate-50">
      <div className="w-full max-w-sm bg-white rounded-2xl border border-slate-200 p-8 space-y-6 shadow-sm">
        <div className="space-y-1 text-center">
          <p className="text-xs uppercase tracking-[0.2em] text-mas-red-500 font-bold">Kabinet HRIS</p>
          <h1 className="text-2xl font-bold text-mas-blue-700">Medewerker inloggen</h1>
          <p className="text-[11px] italic text-mas-red-500">Kabinet van de President</p>
        </div>

        <LoginForm />

        <p className="text-xs text-center text-slate-500">
          <Link href="/" className="hover:underline">
            ← Terug naar startpagina
          </Link>
        </p>
      </div>
    </main>
  );
}
