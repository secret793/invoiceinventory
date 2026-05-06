import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function AuthLayout() {
  const { user, loading } = useAuth();
  if (loading) return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0A1540' }}>
      <Spinner />
    </div>
  );
  if (user) return <Navigate to="/dashboard" replace />;

  return (
    <div style={{
      minHeight: '100vh',
      overflowY: 'auto',
      position: 'relative',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '1.5rem 1rem',
      background: 'linear-gradient(135deg, #0A1540 0%, #1E2D7A 60%, #0F2060 100%)',
    }}>
      <TrackingScene />

      {/* Login card — always fully visible, scrollable if viewport too short */}
      <div style={{ position: 'relative', zIndex: 10, width: '100%', maxWidth: 420, margin: 'auto' }}>
        <div style={{ borderRadius: 16, overflow: 'hidden', boxShadow: '0 25px 60px rgba(0,0,0,0.5)' }}>

          {/* Blue header */}
          <div style={{ background: '#1E2D7A', padding: '24px 32px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 10 }}>
              <div style={{ width: 44, height: 44, borderRadius: 12, background: 'rgba(255,255,255,0.14)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20 }}>
                📡
              </div>
              <div>
                <div style={{ display: 'flex', alignItems: 'baseline', gap: 2 }}>
                  <span style={{ fontSize: 22, fontWeight: 900, color: '#E31E24', letterSpacing: '-0.5px' }}>GN</span>
                  <span style={{ fontSize: 22, fontWeight: 900, color: 'white', letterSpacing: '-0.5px' }}>SW</span>
                </div>
                <div style={{ fontSize: 10, fontWeight: 600, letterSpacing: '0.1em', color: 'rgba(255,255,255,0.55)', marginTop: 1 }}>
                  NATIONAL SINGLE WINDOW
                </div>
              </div>
            </div>
            <h1 style={{ fontSize: 18, fontWeight: 700, color: 'white', margin: 0, textAlign: 'center' }}>
              E-Tracking System
            </h1>
            <p style={{ fontSize: 11, color: 'rgba(255,255,255,0.5)', margin: '4px 0 0', textAlign: 'center' }}>
              Inventory &amp; Device Management Platform
            </p>
          </div>

          {/* White form area */}
          <div style={{ background: 'white', padding: '24px 32px 20px' }}>
            <Outlet />
            <p style={{ textAlign: 'center', fontSize: 11, color: '#CBD5E1', marginTop: 16, marginBottom: 0 }}>
              © 2026 GNSW · All rights reserved
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

function Spinner() {
  return (
    <div style={{ width: 32, height: 32, border: '4px solid rgba(30,45,122,0.3)', borderTopColor: '#1E2D7A', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
  );
}

function TrackingScene() {
  return (
    <div style={{ position: 'fixed', inset: 0, overflow: 'hidden', pointerEvents: 'none', userSelect: 'none', zIndex: 0 }}>
      <svg style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.08 }}>
        <defs>
          <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M 60 0 L 0 0 0 60" fill="none" stroke="#5B8DD9" strokeWidth="0.6"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
      </svg>

      <div style={{ position: 'absolute', top: '8%', left: '4%', width: 280, height: 280, borderRadius: '50%', background: 'radial-gradient(circle, rgba(30,45,122,0.45) 0%, transparent 70%)', animation: 'pulse-orb 6s ease-in-out infinite' }} />
      <div style={{ position: 'absolute', bottom: '28%', right: '6%', width: 220, height: 220, borderRadius: '50%', background: 'radial-gradient(circle, rgba(8,94,55,0.28) 0%, transparent 70%)', animation: 'pulse-orb 8s ease-in-out infinite 2s' }} />

      <SatelliteSignal x={100}  y={70}  delay={0}   />
      <SatelliteSignal x={380}  y={45}  delay={1.5} />
      <SatelliteSignal x={680}  y={80}  delay={3}   />
      <SatelliteSignal x={960}  y={55}  delay={0.8} />
      <SatelliteSignal x={1180} y={95}  delay={2.2} />

      <GPSPing x="16%" y="76%" delay={0}   color="#F59E0B" />
      <GPSPing x="44%" y="80%" delay={1.2} color="#10B981" />
      <GPSPing x="71%" y="77%" delay={2.5} color="#E31E24" />
      <GPSPing x="87%" y="74%" delay={0.6} color="#F59E0B" />

      <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: '22%' }}>
        <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: '40%', background: 'linear-gradient(to bottom, transparent, rgba(8,16,42,0.6))' }} />
        <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: '60%', background: 'rgba(8,16,42,0.85)', borderTop: '1.5px solid rgba(255,255,255,0.07)' }}>
          <div style={{ position: 'absolute', top: '45%', left: 0, right: 0, height: 2, overflow: 'hidden' }}>
            <div style={{ display: 'flex', gap: 32, animation: 'road-dash 1.5s linear infinite', whiteSpace: 'nowrap', width: '200%' }}>
              {Array.from({ length: 60 }).map((_, i) => (
                <div key={i} style={{ width: 48, height: 2, background: 'rgba(255,255,255,0.22)', flexShrink: 0 }} />
              ))}
            </div>
          </div>
          <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 1, background: 'rgba(255,255,255,0.1)' }} />
        </div>
      </div>

      <Truck lane="80%" speed={14} delay={0}  scale={1.1}  color="#E31E24" />
      <Truck lane="77%" speed={20} delay={5}  scale={0.85} color="#1E2D7A" />
      <Truck lane="83%" speed={11} delay={9}  scale={0.7}  color="#085E37" />
      <Truck lane="79%" speed={17} delay={13} scale={0.9}  color="#F59E0B" />

      <LocationPin x="15%" bottom="24%" delay={0}   />
      <LocationPin x="42%" bottom="19%" delay={1.5} />
      <LocationPin x="68%" bottom="22%" delay={3}   />
      <LocationPin x="85%" bottom="25%" delay={2}   />

      <DataStream x1="15%" y1="70%" x2={100} y2={70}  delay={0}   />
      <DataStream x1="45%" y1="72%" x2={380} y2={45}  delay={1.5} />
      <DataStream x1="72%" y1="71%" x2={680} y2={80}  delay={2.5} />
    </div>
  );
}

function SatelliteSignal({ x, y, delay }) {
  return (
    <div style={{ position: 'absolute', left: x, top: y, transform: 'translate(-50%, -50%)' }}>
      <div style={{ position: 'relative', width: 8, height: 8 }}>
        <div style={{ position: 'absolute', inset: 0, borderRadius: '50%', background: '#5B8DD9' }} />
        {[1, 2, 3].map(i => (
          <div key={i} style={{ position: 'absolute', inset: -(i * 10), borderRadius: '50%', border: '1px solid rgba(91,141,217,0.5)', animation: 'sat-ring 3s ease-out infinite', animationDelay: `${delay + i * 0.4}s`, opacity: 0 }} />
        ))}
      </div>
    </div>
  );
}

function GPSPing({ x, y, delay, color }) {
  return (
    <div style={{ position: 'absolute', left: x, top: y, transform: 'translate(-50%, -50%)' }}>
      <div style={{ position: 'relative', width: 10, height: 10 }}>
        <div style={{ position: 'absolute', inset: 0, borderRadius: '50%', background: color, boxShadow: `0 0 8px ${color}` }} />
        {[1, 2].map(i => (
          <div key={i} style={{ position: 'absolute', inset: -(i * 14), borderRadius: '50%', border: `1.5px solid ${color}`, animation: 'gps-ping 2.5s ease-out infinite', animationDelay: `${delay + i * 0.5}s`, opacity: 0 }} />
        ))}
      </div>
    </div>
  );
}

function Truck({ lane, speed, delay, scale, color }) {
  return (
    <div style={{ position: 'absolute', top: lane, left: 0, transform: 'translateY(-50%)', animation: `drive ${speed}s linear infinite`, animationDelay: `${delay}s`, willChange: 'transform' }}>
      <TruckSVG width={Math.round(120 * scale)} height={Math.round(55 * scale)} color={color} />
    </div>
  );
}

function TruckSVG({ width, height, color }) {
  return (
    <svg width={width} height={height} viewBox="0 0 120 55" xmlns="http://www.w3.org/2000/svg">
      <circle cx="85" cy="8" r="3" fill="#F59E0B" opacity="0.9" style={{ animation: 'gps-blink 1s ease-in-out infinite' }} />
      <circle cx="85" cy="8" r="8" fill="none" stroke="#F59E0B" strokeWidth="1" opacity="0.4" style={{ animation: 'gps-ping 1.5s ease-out infinite' }} />
      <circle cx="85" cy="8" r="15" fill="none" stroke="#F59E0B" strokeWidth="0.5" opacity="0.2" style={{ animation: 'gps-ping 1.5s ease-out infinite', animationDelay: '0.3s' }} />
      <rect x="2" y="14" width="75" height="30" rx="3" fill={color} opacity="0.9" />
      <rect x="2" y="26" width="75" height="4" fill="rgba(255,255,255,0.2)" />
      <text x="28" y="24" fontSize="6" fontWeight="bold" fill="white" opacity="0.8" fontFamily="sans-serif">GNSW</text>
      <rect x="77" y="20" width="38" height="24" rx="3" fill={color} />
      <path d="M80,20 L82,10 Q85,8 90,8 L112,8 Q116,8 117,12 L117,20 Z" fill={color} />
      <path d="M85,11 L87,20 L112,20 L112,11 Q110,9 105,9 L90,9 Q87,9 85,11 Z" fill="rgba(200,230,255,0.3)" stroke="rgba(255,255,255,0.2)" strokeWidth="0.5" />
      <rect x="114" y="25" width="4" height="6" rx="1" fill="#FFF9C4" />
      <rect x="97" y="13" width="14" height="7" rx="1.5" fill="rgba(200,230,255,0.25)" />
      <circle cx="18" cy="44" r="8" fill="#1a1a2e" stroke="rgba(255,255,255,0.2)" strokeWidth="1.5" />
      <circle cx="18" cy="44" r="4" fill="#2a2a4e" />
      <circle cx="55" cy="44" r="8" fill="#1a1a2e" stroke="rgba(255,255,255,0.2)" strokeWidth="1.5" />
      <circle cx="55" cy="44" r="4" fill="#2a2a4e" />
      <circle cx="98" cy="44" r="8" fill="#1a1a2e" stroke="rgba(255,255,255,0.2)" strokeWidth="1.5" />
      <circle cx="98" cy="44" r="4" fill="#2a2a4e" />
      <rect x="2" y="20" width="3" height="8" rx="1" fill="#E31E24" opacity="0.9" />
    </svg>
  );
}

function LocationPin({ x, bottom, delay }) {
  return (
    <div style={{ position: 'absolute', left: x, bottom, transform: 'translateX(-50%)', animation: 'pin-float 3s ease-in-out infinite', animationDelay: `${delay}s` }}>
      <svg width="18" height="24" viewBox="0 0 20 26" xmlns="http://www.w3.org/2000/svg">
        <path d="M10,0 C4.5,0 0,4.5 0,10 C0,17 10,26 10,26 C10,26 20,17 20,10 C20,4.5 15.5,0 10,0 Z" fill="#E31E24" opacity="0.9" />
        <circle cx="10" cy="10" r="5" fill="white" opacity="0.9" />
        <circle cx="10" cy="10" r="2.5" fill="#E31E24" />
      </svg>
    </div>
  );
}

function DataStream({ x1, y1, x2, y2, delay }) {
  return (
    <div style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
      <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0, opacity: 0.25 }}>
        <line x1={x1} y1={y1} x2={x2} y2={y2} stroke="#5B8DD9" strokeWidth="1" strokeDasharray="4 6"
          style={{ animation: 'dash-flow 2s linear infinite', animationDelay: `${delay}s` }} />
      </svg>
    </div>
  );
}
