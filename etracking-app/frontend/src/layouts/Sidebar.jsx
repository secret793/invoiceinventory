import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useSidebar } from '../contexts/SidebarContext';
import { useNotification } from '../contexts/NotificationContext';

const GN_RED = '#E31E24';

/* Mirror of AllocationPoint::slugify() on the backend */
const slugify = (name = '') =>
  name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_');

export default function Sidebar() {
  const { user, hasRole } = useAuth();
  const { collapsed, allocationPoints, distributionPoints } = useSidebar();
  const { unreadCount } = useNotification();
  const location = useLocation();
  const [expanded, setExpanded] = useState({});
  const toggle = (key) => setExpanded(p => ({ ...p, [key]: !p[key] }));

  /* ── Role flags ─────────────────────────────────────────────────────── */
  const isSA  = hasRole('Super Admin');
  const isWM  = hasRole('Warehouse Manager');
  const isFO  = hasRole('Finance Officer');
  const isRO  = hasRole('Read Only Tracker Officer');
  const isMon = hasRole('Monitoring Officer');
  const isRet = hasRole('Retrieval Officer');
  const isAff = hasRole('Affixing Officer');
  const isAO  = hasRole('Allocation Officer');
  const isDO  = hasRole('Distribution Officer');
  const isDEO = hasRole('Data Entry Officer');

  /* ── User permissions array (from JWT payload) ───────────────────────── */
  const userPerms = user?.permissions || [];

  /* ── Permission-filtered AP lists for limited-access roles ──────────── */
  const aoAPs  = allocationPoints.filter(ap => {
    const s = ap.slug || slugify(ap.name);
    return userPerms.includes(`view_allocationpoint_${s}`);
  });

  const deoAPs = allocationPoints.filter(ap => {
    const s = ap.slug || slugify(ap.name);
    return userPerms.includes(`view_data_entry_${s}`);
  });

  /* ── Helpers ─────────────────────────────────────────────────────────── */
  const isActive = (path) =>
    location.pathname === path || location.pathname.startsWith(path + '/');

  const renderLink = (path, icon, label, badge = 0) => {
    const active = isActive(path);
    return (
      <Link key={path} to={path}
        className="flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
        style={{
          color: active ? 'white' : 'rgba(255,255,255,0.65)',
          background: active ? 'rgba(255,255,255,0.15)' : 'transparent',
          borderLeft: active ? `3px solid ${GN_RED}` : '3px solid transparent',
        }}
        onMouseEnter={e => { if (!active) e.currentTarget.style.background = 'rgba(255,255,255,0.08)'; }}
        onMouseLeave={e => { if (!active) e.currentTarget.style.background = active ? 'rgba(255,255,255,0.15)' : 'transparent'; }}
      >
        <span className="flex-shrink-0 text-base leading-none">{icon}</span>
        {!collapsed && (
          <>
            <span className="flex-1 truncate">{label}</span>
            {badge > 0 && (
              <span className="text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
                style={{ background: GN_RED }}>{badge > 99 ? '99+' : badge}</span>
            )}
          </>
        )}
      </Link>
    );
  };

  const renderGroup = (key, icon, label, children) => {
    const open = expanded[key];
    return (
      <div key={key}>
        <button onClick={() => toggle(key)}
          className="w-full flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
          style={{ color: 'rgba(255,255,255,0.65)', background: 'transparent', borderLeft: '3px solid transparent' }}
          onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.08)'}
          onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
        >
          <span className="flex-shrink-0 text-base leading-none">{icon}</span>
          {!collapsed && (
            <>
              <span className="flex-1 text-left truncate">{label}</span>
              <svg className={`w-3.5 h-3.5 transition-transform flex-shrink-0 ${open ? 'rotate-90' : ''}`}
                fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
              </svg>
            </>
          )}
        </button>
        {!collapsed && open && (
          <div style={{ background: 'rgba(0,0,0,0.15)' }}>{children}</div>
        )}
      </div>
    );
  };

  const renderSub = (path, label, badge = 0) => {
    const active = isActive(path);
    return (
      <Link key={path} to={path}
        className="flex items-center justify-between pl-10 pr-3 py-2 text-xs transition-colors"
        style={{ color: active ? 'white' : 'rgba(255,255,255,0.5)' }}
        onMouseEnter={e => e.currentTarget.style.color = 'white'}
        onMouseLeave={e => e.currentTarget.style.color = active ? 'white' : 'rgba(255,255,255,0.5)'}
      >
        <span className="truncate">{label}</span>
        {badge > 0 && (
          <span className="ml-2 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
            style={{ background: GN_RED }}>{badge}</span>
        )}
      </Link>
    );
  };

  const empty = (msg) => (
    <p className="pl-10 py-2 text-xs italic" style={{ color: 'rgba(255,255,255,0.3)' }}>{msg}</p>
  );

  /* ══════════════════════════════════════════════════════════════════════
     renderNav — composable multi-role navigation
     ══════════════════════════════════════════════════════════════════════ */
  const renderNav = () => {

    /* ── Super Admin / Warehouse Manager: full menu ───────────────────── */
    if (isSA || isWM) {
      const configItems = [
        { label: 'Distribution Points', path: '/config/distribution-points' },
        { label: 'Allocation Points',   path: '/config/allocation-points'   },
        { label: 'Routes',              path: '/config/routes'              },
        { label: 'Long Routes',         path: '/config/long-routes'         },
        { label: 'Regimes',             path: '/config/regimes'             },
        { label: 'Destinations',        path: '/config/destinations'        },
        ...(isSA ? [
          { label: 'Users',       path: '/config/users'       },
          { label: 'Roles',       path: '/config/roles'       },
          { label: 'Permissions', path: '/config/permissions' },
          { label: 'Settings',    path: '/config/settings'    },
        ] : []),
      ];
      return (
        <>
          {renderLink('/dashboard',         '🏠', 'Dashboard')}
          {renderLink('/devices',            '📱', 'Devices')}
          {renderLink('/stores',             '🏭', 'Inventory')}
          {renderLink('/other-items',        '📦', 'Other Items')}
          {renderLink('/transfers',          '🔄', 'Transfers')}

          {renderGroup('dist', '🌐', 'Distribution', <>
            {distributionPoints.length > 0
              ? distributionPoints.map(dp => renderSub(`/distribution/${dp.id}`, dp.name, dp.received_count || 0))
              : empty('No DPs')}
          </>)}

          {renderGroup('alloc', '📍', 'Allocation Points', <>
            {allocationPoints.length > 0
              ? allocationPoints.map(ap => renderSub(`/allocation/${ap.id}`, ap.name, ap.received_count || 0))
              : empty('No APs')}
          </>)}

          {renderGroup('de', '✏️', 'Data Entry', <>
            {allocationPoints.length > 0
              ? allocationPoints.map(ap => renderSub(`/data-entry/${ap.id}`, ap.name))
              : empty('No APs')}
          </>)}

          {renderLink('/confirmed-affixed', '✅', 'Confirmed Dispatch')}
          {renderLink('/device-retrievals', '🔙', 'Device Retrievals')}
          {renderLink('/invoices',          '🧾', 'Overstay Invoices')}
          {renderLink('/monitoring',        '👁️', 'Monitoring')}
          {renderLink('/reports',           '📊', 'Reports')}

          {isSA && renderLink('/notifications', '🔔', 'Notifications', unreadCount)}

          {renderGroup('cfg', '⚙️', 'Configuration', <>
            {configItems.map(c => renderSub(c.path, c.label))}
          </>)}
        </>
      );
    }

    /* ── Read Only Tracker: Tracking group only — no Dashboard ────────── */
    if (isRO) {
      return (
        <>
          {renderGroup('tracking', '🔍', 'Tracking', <>
            {renderSub('/devices',           'Device Tracker')}
            {renderSub('/device-retrievals', 'Device Retrieval')}
            {renderSub('/confirmed-affixed', 'Confirmed Affix')}
          </>)}
        </>
      );
    }

    /* ── Composable menu for all remaining single/dual/multi roles ──────
       Each role adds its own section(s). Multiple roles accumulate.       */

    // Finance Officer alone has no Dashboard; every other role has one.
    const foOnly = isFO && !isAff && !isRet && !isMon && !isAO && !isDO && !isDEO;

    // For Aff+Ret dual role the two groups merge into one combined group.
    const showAffRet  = isAff && isRet;
    const showAffOnly = isAff && !isRet;
    const showRetOnly = isRet && !isAff;

    return (
      <>
        {/* Dashboard — suppressed only for FO-only */}
        {!foOnly && renderLink('/dashboard', '🏠', 'Dashboard')}

        {/* Finance Management (Finance Officer) */}
        {isFO && renderGroup('finance', '💰', 'Finance Management', <>
          {renderSub('/invoices',                 'Overstay Receipts')}
          {renderSub('/finance/dispatch-records', 'Dispatch Records')}
          {renderSub('/receipts',                 'Generated Receipts')}
        </>)}

        {/* Distribution Points (Distribution Officer) */}
        {isDO && renderGroup('do-g', '🌐', 'Distribution Points', <>
          {distributionPoints.length > 0
            ? distributionPoints.map(dp => renderSub(`/distribution/${dp.id}`, dp.name, dp.received_count || 0))
            : empty('No distribution points')}
        </>)}

        {/* Allocation Points (Allocation Officer) — filtered by view_allocationpoint_* */}
        {isAO && renderGroup('ao-g', '📍', 'Allocation', <>
          {aoAPs.length > 0
            ? aoAPs.map(ap => renderSub(`/allocation/${ap.id}`, ap.name, ap.received_count || 0))
            : empty('No allocation points assigned')}
        </>)}

        {/* Data Entry (Data Entry Officer) — filtered by view_data_entry_* */}
        {isDEO && renderGroup('de-g', '✏️', 'Data Entry/Assignment', <>
          {deoAPs.length > 0
            ? deoAPs.map(ap => renderSub(`/data-entry/${ap.id}`, ap.name))
            : empty('No assignments')}
        </>)}

        {/* Affixing + Retrieval dual role → merged group */}
        {showAffRet && renderGroup('ar-g', '🔗', 'Affixing & Retrieval Management', <>
          {renderSub('/confirmed-affixed', 'Confirmed Affixed')}
          {renderSub('/device-retrievals', 'Device Retrievals')}
        </>)}

        {/* Affixing Officer only */}
        {showAffOnly && renderGroup('aff-g', '✅', 'Confirmed Dispatch', <>
          {renderSub('/confirmed-affixed', 'Confirmed Affixed')}
        </>)}

        {/* Retrieval Officer only */}
        {showRetOnly && renderGroup('ret-g', '🔙', 'Device Retrieval', <>
          {renderSub('/device-retrievals', 'Device Retrievals')}
        </>)}

        {/* Monitoring Officer */}
        {isMon && renderGroup('mon-g', '👁️', 'Monitoring', <>
          {renderSub('/monitoring', 'Device Monitoring')}
        </>)}
      </>
    );
  };

  return (
    <aside
      className="flex flex-col flex-shrink-0 transition-all duration-300 h-full overflow-hidden"
      style={{
        width: collapsed ? 56 : 240,
        background: 'linear-gradient(180deg, #162260 0%, #1E2D7A 40%, #1a2970 100%)',
        borderRight: '1px solid rgba(255,255,255,0.08)',
      }}
    >
      <div className="flex items-center gap-3 px-3 h-14 flex-shrink-0"
        style={{ borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
        <div className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-lg"
          style={{ background: 'rgba(255,255,255,0.12)' }}>
          📡
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <div className="flex items-center gap-0.5">
              <span className="font-extrabold text-sm" style={{ color: GN_RED }}>GN</span>
              <span className="font-extrabold text-sm text-white">SW</span>
              <span className="text-white font-medium text-sm ml-1">E-Track</span>
            </div>
            <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.45)' }}>Inventory System</p>
          </div>
        )}
      </div>

      <nav className="flex-1 overflow-y-auto py-2 scrollbar-thin">
        {renderNav()}
      </nav>

      {!collapsed && (
        <div className="px-3 py-3 text-xs"
          style={{ borderTop: '1px solid rgba(255,255,255,0.08)', color: 'rgba(255,255,255,0.4)' }}>
          {user?.roles?.join(', ') || 'User'}
        </div>
      )}
    </aside>
  );
}
