'use client';

import { useRouter } from 'next/navigation';
import { type CurrentUser, isAdmin, logout } from '@/lib/auth';

export function Topbar({ user }: { user: CurrentUser }) {
  const router = useRouter();
  const adminUrl = process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://127.0.0.1:8000/admin';

  async function handleLogout() {
    await logout();
    router.replace('/');
  }

  const initials = user.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((s) => s[0])
    .join('')
    .toUpperCase();

  return (
    <header className="h-14 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
      <div />
      <div className="flex items-center gap-3">
        <div className="w-8 h-8 rounded-full bg-mas-blue-600 text-white text-xs font-semibold flex items-center justify-center">
          {initials}
        </div>
        <div className="text-left hidden sm:block">
          <div className="text-sm font-medium text-slate-900 leading-tight">{user.name}</div>
          <div className="text-[11px] text-slate-500 leading-tight">{user.email}</div>
        </div>
        {isAdmin(user) && (
          <a
            href={adminUrl}
            className="hidden md:inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50 transition"
          >
            HR-administratie ↗
          </a>
        )}
        <button
          onClick={handleLogout}
          className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md border border-mas-red-200 text-mas-red-600 hover:bg-mas-red-50 transition"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth="1.8"
            stroke="currentColor"
            className="w-4 h-4"
            aria-hidden
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12"
            />
          </svg>
          Uitloggen
        </button>
      </div>
    </header>
  );
}
