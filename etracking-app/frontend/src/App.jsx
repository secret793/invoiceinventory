import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';

// Layouts
import AuthLayout from './layouts/AuthLayout';
import AppLayout  from './layouts/AppLayout';

// Auth
import LoginPage from './pages/auth/LoginPage';

// Dashboard
import DashboardPage from './pages/dashboard/DashboardPage';

// Inventory
import DevicesPage   from './pages/devices/DevicesPage';
import StoresPage    from './pages/stores/StoresPage';
import OtherItemsPage from './pages/otherItems/OtherItemsPage';
import TransfersPage from './pages/transfers/TransfersPage';

// Distribution
import DistributionListPage   from './pages/distribution/DistributionListPage';
import DistributionDetailPage from './pages/distribution/DistributionDetailPage';

// Allocation
import AllocationListPage   from './pages/allocation/AllocationListPage';
import AllocationDetailPage from './pages/allocation/AllocationDetailPage';

// Data Entry / Dispatch Flow
import DataEntryDetailPage    from './pages/dataentry/DataEntryDetailPage';
import ConfirmedAffixedPage   from './pages/confirmedAffixed/ConfirmedAffixedPage';

// Retrievals + Monitoring
import RetrievalsPage  from './pages/retrievals/RetrievalsPage';
import InvoicesPage    from './pages/retrievals/InvoicesPage';
import MonitoringPage  from './pages/monitoring/MonitoringPage';

// Receipts
import ReceiptsPage from './pages/receipts/ReceiptsPage';

// Notifications
import NotificationsPage from './pages/notifications/NotificationsPage';

// Reports
import ReportsPage from './pages/reports/ReportsPage';

// Finance
import FinanceDispatchPage from './pages/finance/FinanceDispatchPage';

// Configuration
import RoutesPage      from './pages/config/RoutesPage';
import LongRoutesPage  from './pages/config/LongRoutesPage';
import RegimesPage     from './pages/config/RegimesPage';
import DestinationsPage from './pages/config/DestinationsPage';
import CompaniesPage    from './pages/config/CompaniesPage';
import UsersPage       from './pages/config/UsersPage';
import RolesPage        from './pages/config/RolesPage';
import PermissionsPage  from './pages/config/PermissionsPage';
import SettingsPage     from './pages/config/SettingsPage';

// 404
import NotFoundPage from './pages/NotFoundPage';

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Routes>
          {/* Auth routes */}
          <Route element={<AuthLayout />}>
            <Route path="/login" element={<LoginPage />} />
          </Route>

          {/* App routes (protected) */}
          <Route element={<AppLayout />}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard"  element={<DashboardPage />} />

            {/* Inventory */}
            <Route path="/devices"     element={<DevicesPage />} />
            <Route path="/stores"      element={<StoresPage />} />
            <Route path="/other-items" element={<OtherItemsPage />} />
            <Route path="/transfers"   element={<TransfersPage />} />

            {/* Distribution */}
            <Route path="/distribution"     element={<DistributionListPage />} />
            <Route path="/distribution/:id" element={<DistributionDetailPage />} />

            {/* Allocation Points */}
            <Route path="/allocation"     element={<AllocationListPage />} />
            <Route path="/allocation/:id" element={<AllocationDetailPage />} />

            {/* Data Entry — each AP gets its own route */}
            <Route path="/data-entry"     element={<AllocationListPage />} />
            <Route path="/data-entry/:id" element={<DataEntryDetailPage />} />

            {/* Dispatch Flow */}
            <Route path="/confirmed-affixed" element={<ConfirmedAffixedPage />} />

            {/* Retrievals + Monitoring */}
            <Route path="/device-retrievals" element={<RetrievalsPage />} />
            <Route path="/invoices"          element={<InvoicesPage />} />
            <Route path="/monitoring"        element={<MonitoringPage />} />

            {/* Receipts */}
            <Route path="/receipts" element={<ReceiptsPage />} />

            {/* Finance */}
            <Route path="/finance/dispatch-records" element={<FinanceDispatchPage />} />

            {/* Notifications */}
            <Route path="/notifications" element={<NotificationsPage />} />

            {/* Reports */}
            <Route path="/reports" element={<ReportsPage />} />

            {/* Configuration */}
            <Route path="/config/distribution-points" element={<DistributionListPage />} />
            <Route path="/config/allocation-points"   element={<AllocationListPage />} />
            <Route path="/config/routes"       element={<RoutesPage />} />
            <Route path="/config/long-routes"  element={<LongRoutesPage />} />
            <Route path="/config/regimes"      element={<RegimesPage />} />
            <Route path="/config/destinations" element={<DestinationsPage />} />
            <Route path="/config/companies"   element={<CompaniesPage />} />
            <Route path="/config/users"        element={<UsersPage />} />
            <Route path="/config/roles"        element={<RolesPage />} />
            <Route path="/config/permissions"  element={<PermissionsPage />} />
            <Route path="/config/settings"     element={<SettingsPage />} />
          </Route>

          {/* 404 */}
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
