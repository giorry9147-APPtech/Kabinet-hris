'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const items = [
  { href: '/dashboard', label: 'Dashboard', icon: '📊' },
  { href: '/mijn-dossier', label: 'Mijn dossier', icon: '👤' },
  { href: '/mijn-verlof', label: 'Mijn verlof', icon: '🏖️' },
  { href: '/mijn-certificaten', label: 'Certificaten', icon: '🎓' },
  { href: '/mijn-salaris', label: 'Salaris', icon: '💰' },
  { href: '/mijn-assets', label: 'Assets', icon: '💻' },
  { href: '/mijn-documenten', label: 'Mijn documenten', icon: '📄' },
];

export function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="w-60 bg-white border-r border-slate-200 flex flex-col">
      <div className="px-6 py-5 border-b border-slate-200">
        <p className="text-[11px] uppercase tracking-widest text-mas-blue-600 font-semibold">Kabinet HRIS</p>
        <p className="italic text-mas-red-500 text-xs mt-0.5">Kabinet van de President</p>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-1">
        {items.map((item) => {
          const active = pathname === item.href || pathname.startsWith(item.href + '/');
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-3 px-3 py-2 rounded-md text-sm transition ${
                active
                  ? 'bg-mas-blue-600 text-white font-medium'
                  : 'text-slate-700 hover:bg-slate-100'
              }`}
            >
              <span aria-hidden className="text-base">
                {item.icon}
              </span>
              {item.label}
            </Link>
          );
        })}
      </nav>

      <div className="px-6 py-4 border-t border-slate-200 text-[11px] text-slate-500">
        © {new Date().getFullYear()} Kabinet van de President
      </div>
    </aside>
  );
}
