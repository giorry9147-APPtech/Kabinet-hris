import Image from 'next/image';
import { LoginForm } from '@/components/login-form';

const features = [
  { label: 'Mijn dossier' },
  { label: 'Verlofaanvragen' },
  { label: 'Certificaten' },
  { label: 'Contracten' },
  { label: 'Resoluties' },
  { label: 'Salarisstrook' },
  { label: 'Asset-beheer' },
  { label: 'Organogram' },
];

export default function Home() {
  return (
    <div className="grid lg:grid-cols-2 min-h-screen w-full">
      <aside className="relative bg-mas-blue-900 text-white px-8 py-12 lg:px-16 lg:py-16 flex flex-col justify-between overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-mas-blue-900 via-mas-blue-950 to-slate-950 -z-0" />
        <div className="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-mas-red-500/10 blur-3xl -z-0" />
        <div className="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-mas-blue-500/10 blur-3xl -z-0" />

        <div className="relative space-y-10">
          <div className="bg-white rounded-2xl px-6 py-5 inline-block shadow-xl shadow-black/30">
            <Image
              src="/kabinetlogo.png"
              alt="Kabinet van de President van de Republiek Suriname"
              width={900}
              height={642}
              priority
              className="h-20 lg:h-24 w-auto"
            />
          </div>

          <div className="space-y-4">
            <p className="text-xs uppercase tracking-[0.25em] text-mas-blue-200/80 font-semibold">
              Republiek Suriname
            </p>
            <h1 className="text-3xl lg:text-5xl font-bold tracking-tight leading-tight">
              HR-portaal<br className="hidden lg:block" /> Kabinet van de President
            </h1>
            <p className="text-mas-blue-100 text-base lg:text-lg max-w-xl leading-relaxed">
              Bekijk je dossier, dien verlof in, beheer je certificaten, contracten en
              resoluties — één omgeving voor medewerkers van het Kabinet van de President
              van de Republiek Suriname.
            </p>
          </div>
        </div>

        <div className="relative mt-12 space-y-6">
          <div className="grid grid-cols-2 gap-3">
            {features.map((it) => (
              <div
                key={it.label}
                className="rounded-full bg-white/5 border border-white/10 backdrop-blur-sm px-5 py-2 text-sm text-mas-blue-100 hover:bg-white/10 transition"
              >
                {it.label}
              </div>
            ))}
          </div>
          <p className="text-xs text-mas-blue-200/60 pt-4 border-t border-white/10">
            © {new Date().getFullYear()} Kabinet van de President van de Republiek Suriname
          </p>
        </div>
      </aside>

      <section className="flex items-center justify-center bg-white px-6 py-12 lg:px-16">
        <div className="w-full max-w-sm">
          <p className="text-xs uppercase tracking-[0.2em] text-mas-red-500 font-bold mb-3">
            Inloggen
          </p>
          <h2 className="text-3xl lg:text-4xl font-bold text-mas-blue-700 mb-2">
            Welkom terug
          </h2>
          <p className="text-slate-600 text-sm mb-8">
            Gebruik je Kabinet-accountgegevens om door te gaan.
          </p>

          <LoginForm />

          <p className="text-xs text-slate-400 mt-8 text-center">
            Problemen met inloggen? Neem contact op met HRD.
          </p>
        </div>
      </section>
    </div>
  );
}
