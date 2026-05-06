import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

export default function LoginPage() {
  const { login }      = useAuth();
  const navigate       = useNavigate();
  const [form, setForm]   = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(''); setLoading(true);
    try {
      await login(form.email, form.password);
      navigate('/dashboard', { replace: true });
    } catch (e) {
      setError(e.message || 'Invalid email or password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="mb-5">
        <h2 className="text-xl font-bold" style={{ color: '#0F172A' }}>Welcome back</h2>
        <p className="text-sm mt-1" style={{ color: '#475569' }}>Sign in to your account to continue</p>
      </div>

      {error && (
        <div className="rounded-lg px-4 py-3 text-sm mb-4 flex items-center gap-2" style={{ background: '#FEF2F2', border: '1px solid #FECACA', color: '#B91C1C' }}>
          <span>⚠️</span> {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1" style={{ color: '#374151' }}>Email Address</label>
          <input
            type="email" value={form.email}
            onChange={e => set('email', e.target.value)}
            required autoFocus
            placeholder="you@gnsw.gm"
            style={{ display: 'block', width: '100%', borderRadius: 8, border: '1.5px solid #D1D5DB', padding: '9px 12px', fontSize: 14, outline: 'none', transition: 'border-color 0.2s' }}
            onFocus={e => e.target.style.borderColor = '#1E2D7A'}
            onBlur={e => e.target.style.borderColor = '#D1D5DB'}
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1" style={{ color: '#374151' }}>Password</label>
          <input
            type="password" value={form.password}
            onChange={e => set('password', e.target.value)}
            required
            placeholder="••••••••"
            style={{ display: 'block', width: '100%', borderRadius: 8, border: '1.5px solid #D1D5DB', padding: '9px 12px', fontSize: 14, outline: 'none', transition: 'border-color 0.2s' }}
            onFocus={e => e.target.style.borderColor = '#1E2D7A'}
            onBlur={e => e.target.style.borderColor = '#D1D5DB'}
          />
        </div>
        <button
          type="submit" disabled={loading}
          style={{
            display: 'flex', alignItems: 'center', justifyContent: 'center', width: '100%',
            padding: '10px 0', borderRadius: 8, border: 'none', cursor: loading ? 'not-allowed' : 'pointer',
            background: loading ? '#94A3B8' : '#1E2D7A', color: 'white',
            fontSize: 14, fontWeight: 600, transition: 'background 0.2s', gap: 8,
          }}
          onMouseEnter={e => { if (!loading) e.target.style.background = '#162260'; }}
          onMouseLeave={e => { if (!loading) e.target.style.background = '#1E2D7A'; }}
        >
          {loading ? (
            <>
              <span style={{ width: 16, height: 16, border: '2px solid rgba(255,255,255,0.3)', borderTopColor: 'white', borderRadius: '50%', animation: 'spin 0.8s linear infinite', display: 'inline-block' }} />
              Signing in…
            </>
          ) : 'Sign In'}
        </button>
      </form>

      <div className="mt-5 pt-4 flex items-center justify-center text-xs" style={{ borderTop: '1px solid #F1F5F9', color: '#94A3B8' }}>
        <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
          <span style={{ color: '#085E37' }}>🔒</span> Secure Connection
        </span>
      </div>
    </div>
  );
}
