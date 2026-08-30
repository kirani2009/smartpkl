import { Link } from 'react-router-dom';

const features = [
  { title: 'Partnership Sekolah & Perusahaan', description: 'Guru menemukan perusahaan partner dan mengajukan kerja sama resmi secara digital.', icon: '🤝', gradient: 'from-blue-500 to-indigo-600' },
  { title: 'Lowongan PKL Terpusat', description: 'Perusahaan membuka kebutuhan PKL dengan kuota, periode, dan persyaratan yang jelas.', icon: '📋', gradient: 'from-purple-500 to-violet-600' },
  { title: 'Pendaftaran Online', description: 'Siswa mencari, memfilter, dan mendaftar PKL langsung dari dashboard.', icon: '🎯', gradient: 'from-emerald-500 to-teal-600' },
  { title: 'Seleksi & Interview', description: 'Perusahaan meninjau profil siswa, menyeleksi, dan menjadwalkan interview.', icon: '🗂️', gradient: 'from-amber-500 to-orange-600' },
  { title: 'Monitoring Guru', description: 'Guru memantau lamaran, penempatan, dan status PKL siswa sekolahnya.', icon: '📊', gradient: 'from-rose-500 to-pink-600' },
  { title: 'Smart Matching', description: 'Rekomendasi lowongan yang cocok berdasarkan jurusan, skill, dan lokasi.', icon: '✨', gradient: 'from-cyan-500 to-blue-600' },
];

const stats = [
  { value: '100+', label: 'Siswa Aktif' },
  { value: '50+', label: 'Lowongan PKL' },
  { value: '30+', label: 'Perusahaan Partner' },
  { value: '95%', label: 'Tingkat Penempatan' },
];

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 text-slate-800">
      {/* Navbar */}
      <header className="sticky top-0 z-50 border-b border-slate-200/60 bg-white/80 backdrop-blur-xl">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <Link to="/" className="flex items-center gap-2.5">
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 shadow-md shadow-blue-200">
              <span className="text-sm font-bold text-white">P</span>
            </div>
            <span className="text-lg font-bold text-slate-900">SmartPKL</span>
          </Link>
          <nav className="hidden gap-8 text-sm font-medium text-slate-600 sm:flex">
            <a href="#features" className="transition-colors hover:text-blue-600">Fitur</a>
            <a href="#stats" className="transition-colors hover:text-blue-600">Statistik</a>
            <a href="#about" className="transition-colors hover:text-blue-600">Tentang</a>
          </nav>
          <div className="flex gap-2.5">
            <Link to="/login"
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50 hover:border-slate-400">
              Masuk
            </Link>
            <Link to="/register"
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300">
              Daftar
            </Link>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden py-24 sm:py-32">
        <div className="absolute inset-0 pointer-events-none">
          <div className="absolute -right-40 top-20 h-96 w-96 rounded-full bg-blue-200/30 blur-3xl" />
          <div className="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-indigo-200/30 blur-3xl" />
        </div>
        <div className="relative mx-auto max-w-6xl px-6 text-center">
          <span className="inline-block rounded-full border border-blue-200 bg-blue-50 px-4 py-1.5 text-sm font-semibold text-blue-700">
            🚀 Platform Praktik Kerja Lapangan Digital
          </span>
          <h1 className="mx-auto mt-8 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
            Menghubungkan{' '}
            <span className="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Sekolah</span>,{' '}
            <span className="bg-gradient-to-r from-purple-600 to-violet-600 bg-clip-text text-transparent">Perusahaan</span>, dan{' '}
            <span className="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Siswa</span>{' '}
            dalam Satu Platform
          </h1>
          <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
            SmartPKL mempermudah guru mengelola siswa PKL, perusahaan mendapatkan siswa sesuai kebutuhan,
            dan siswa menemukan tempat PKL melalui proses digital yang terstruktur.
          </p>
          <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
            <Link to="/internships"
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-300">
              🔍 Cari Lowongan PKL
            </Link>
            <Link to="/register"
              className="rounded-xl border border-slate-300 bg-white px-8 py-3.5 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50 hover:border-slate-400 hover:-translate-y-0.5">
              Untuk Sekolah & Perusahaan
            </Link>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section id="stats" className="py-16">
        <div className="mx-auto max-w-6xl px-6">
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {stats.map((stat) => (
              <div key={stat.label} className="rounded-2xl border border-slate-200/60 bg-white/80 p-6 text-center shadow-sm backdrop-blur-xl">
                <p className="text-3xl font-bold text-slate-900">{stat.value}</p>
                <p className="mt-1 text-sm text-slate-500">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="py-20">
        <div className="mx-auto max-w-6xl px-6">
          <div className="text-center">
            <h2 className="text-3xl font-bold text-slate-900">Fitur Utama</h2>
            <p className="mt-3 text-slate-600">
              Dirancang sederhana, terstruktur, dan siap untuk deployment shared hosting.
            </p>
          </div>
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {features.map((feature) => (
              <div key={feature.title}
                className="group rounded-2xl border border-slate-200/60 bg-white/80 p-7 shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl hover:shadow-slate-200/50">
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br ${feature.gradient} text-2xl shadow-md transition-transform duration-300 group-hover:scale-110`}>
                  {feature.icon}
                </div>
                <h3 className="mt-5 font-bold text-slate-900">{feature.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* About */}
      <section id="about" className="py-20">
        <div className="mx-auto max-w-4xl px-6">
          <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-10 shadow-sm backdrop-blur-xl text-center">
            <h2 className="text-2xl font-bold text-slate-900">Tentang SmartPKL</h2>
            <p className="mt-4 max-w-2xl mx-auto leading-relaxed text-slate-600">
              SmartPKL dikembangkan bertahap untuk sekolah, guru, siswa, dan perusahaan. Backend Laravel sebagai
              sumber utama business logic, web ReactJS + Tailwind CSS, aplikasi mobile Flutter untuk siswa,
              dan database MySQL — semuanya kompatibel dengan shared hosting.
            </p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-200/60 bg-slate-900 py-10">
        <div className="mx-auto max-w-6xl px-6 text-center">
          <div className="flex items-center justify-center gap-2.5 mb-4">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
              <span className="text-sm font-bold text-white">P</span>
            </div>
            <span className="text-sm font-bold text-white">SmartPKL</span>
          </div>
          <p className="text-sm text-slate-400">© 2026 SmartPKL · Platform Praktik Kerja Lapangan</p>
        </div>
      </footer>
    </div>
  );
}
