import { Link } from 'react-router-dom';

const features = [
  {
    title: 'Partnership Sekolah & Perusahaan',
    description: 'Guru menemukan perusahaan partner dan mengajukan kerja sama resmi secara digital.',
    icon: '🤝',
  },
  {
    title: 'Lowongan PKL Terpusat',
    description: 'Perusahaan membuka kebutuhan PKL dengan kuota, periode, dan persyaratan yang jelas.',
    icon: '📋',
  },
  {
    title: 'Pendaftaran Online',
    description: 'Siswa mencari, memfilter, dan mendaftar PKL langsung dari dashboard.',
    icon: '🎯',
  },
  {
    title: 'Seleksi & Interview',
    description: 'Perusahaan meninjau profil siswa, menyeleksi, dan menjadwalkan interview.',
    icon: '🗂️',
  },
  {
    title: 'Monitoring Guru',
    description: 'Guru memantau lamaran, penempatan, dan status PKL siswa sekolahnya.',
    icon: '📊',
  },
  {
    title: 'Smart Matching',
    description: 'Rekomendasi lowongan yang cocok berdasarkan jurusan, skill, dan lokasi.',
    icon: '✨',
  },
];

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-slate-50 text-slate-800">
      {/* Navbar */}
      <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/80 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <Link to="/" className="flex items-center gap-2 text-lg font-bold text-slate-900">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">P</span>
            SmartPKL
          </Link>
          <nav className="hidden gap-6 text-sm font-medium text-slate-600 sm:flex">
            <a href="#features" className="transition hover:text-brand-600">Fitur</a>
            <a href="#about" className="transition hover:text-brand-600">Tentang</a>
          </nav>
          <div className="flex gap-2">
            <Link
              to="/login"
              className="rounded-lg border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50"
            >
              Masuk
            </Link>
            <Link
              to="/register"
              className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
            >
              Daftar
            </Link>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="mx-auto max-w-6xl px-6 py-20 text-center">
        <span className="inline-block rounded-full bg-brand-100 px-4 py-1 text-sm font-medium text-brand-700">
          Platform Praktik Kerja Lapangan Digital
        </span>
        <h1 className="mx-auto mt-6 max-w-3xl text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
          Menghubungkan Sekolah, Perusahaan, dan Siswa dalam Satu Platform
        </h1>
        <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
          SmartPKL mempermudah guru mengelola siswa PKL, perusahaan mendapatkan siswa sesuai kebutuhan,
          dan siswa menemukan tempat PKL melalui proses digital yang terstruktur.
        </p>
        <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
          <Link
            to="/internships"
            className="rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700"
          >
            Cari Lowongan PKL
          </Link>
          <Link
            to="/register"
            className="rounded-lg border border-brand-600 bg-white px-6 py-3 font-semibold text-brand-700 transition hover:bg-brand-50"
          >
            Untuk Sekolah & Perusahaan
          </Link>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="mx-auto max-w-6xl px-6 pb-20">
        <h2 className="text-center text-2xl font-bold text-slate-900">Fitur Utama</h2>
        <p className="mt-2 text-center text-slate-600">
          Dirancang sederhana, terstruktur, dan siap untuk deployment shared hosting.
        </p>
        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {features.map((feature) => (
            <div
              key={feature.title}
              className="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md hover:border-brand-300"
            >
              <div className="text-3xl">{feature.icon}</div>
              <h3 className="mt-4 font-semibold text-slate-900">{feature.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-slate-600">{feature.description}</p>
            </div>
          ))}
        </div>
      </section>

      {/* About */}
      <section id="about" className="border-t border-slate-200 bg-white py-16">
        <div className="mx-auto max-w-4xl px-6 text-center">
          <h2 className="text-2xl font-bold text-slate-900">Tentang SmartPKL</h2>
          <p className="mt-4 leading-relaxed text-slate-600">
            SmartPKL dikembangkan bertahap untuk sekolah, guru, siswa, dan perusahaan. Backend Laravel sebagai
            sumber utama business logic, web ReactJS + Tailwind CSS, aplikasi mobile Flutter untuk siswa,
            dan database MySQL — semuanya kompatibel dengan shared hosting.
          </p>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-200 bg-slate-900 py-8 text-center text-sm text-slate-400">
        © 2026 SmartPKL · Platform Praktik Kerja Lapangan
      </footer>
    </div>
  );
}
