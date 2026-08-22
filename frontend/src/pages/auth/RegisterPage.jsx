import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Button, Input, Select, Card } from '../../components/ui';

const roleOptions = [
  { value: 'student', label: 'Siswa' },
  { value: 'teacher', label: 'Guru' },
  { value: 'company', label: 'Perusahaan' },
];

export default function RegisterPage() {
  const { register, loading } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '', role: 'student' });
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setFieldErrors({ ...fieldErrors, [e.target.name]: '' });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});

    if (form.password !== form.password_confirmation) {
      setFieldErrors({ password_confirmation: 'Konfirmasi password tidak cocok.' });
      return;
    }

    const result = await register(form);
    if (result.success) {
      const role = result.user?.role;
      if (role === 'teacher') navigate('/teacher/dashboard');
      else if (role === 'company') navigate('/company/dashboard');
      else navigate('/student/dashboard');
    } else {
      setError(result.message);
      if (result.errors) {
        const fe = {};
        for (const [key, msgs] of Object.entries(result.errors)) {
          fe[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        }
        setFieldErrors(fe);
      }
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <Card className="w-full max-w-md">
        <Card.Header>
          <div className="flex items-center justify-center gap-2">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">P</span>
            <span className="text-lg font-bold text-slate-900">SmartPKL</span>
          </div>
        </Card.Header>
        <Card.Body>
          <h2 className="mb-6 text-center text-xl font-bold text-slate-900">Daftar</h2>

          {error && (
            <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <Select
              label="Saya adalah"
              name="role"
              value={form.role}
              onChange={handleChange}
              options={roleOptions}
            />
            <Input
              label="Nama Lengkap"
              name="name"
              placeholder="Nama Anda"
              value={form.name}
              onChange={handleChange}
              error={fieldErrors.name}
              required
            />
            <Input
              label="Email"
              type="email"
              name="email"
              placeholder="email@contoh.com"
              value={form.email}
              onChange={handleChange}
              error={fieldErrors.email}
              required
            />
            <Input
              label="Password"
              type="password"
              name="password"
              placeholder="••••••••"
              value={form.password}
              onChange={handleChange}
              error={fieldErrors.password}
              required
            />
            <Input
              label="Konfirmasi Password"
              type="password"
              name="password_confirmation"
              placeholder="••••••••"
              value={form.password_confirmation}
              onChange={handleChange}
              error={fieldErrors.password_confirmation}
              required
            />
            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? 'Mendaftar...' : 'Daftar'}
            </Button>
          </form>

          <p className="mt-4 text-center text-sm text-slate-500">
            Sudah punya akun?{' '}
            <Link to="/login" className="font-medium text-brand-600 hover:underline">
              Masuk
            </Link>
          </p>
        </Card.Body>
      </Card>
    </div>
  );
}
