import api from './api';

export const configService = {
  routes: {
    list:   (params)   => api.get('/routes', { params }).then(r => r.data),
    create: (data)     => api.post('/routes', data).then(r => r.data.data),
    update: (id, data) => api.put(`/routes/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/routes/${id}`).then(r => r.data),
  },
  longRoutes: {
    list:   (params)   => api.get('/long-routes', { params }).then(r => r.data),
    create: (data)     => api.post('/long-routes', data).then(r => r.data.data),
    update: (id, data) => api.put(`/long-routes/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/long-routes/${id}`).then(r => r.data),
  },
  regimes: {
    list:   (params)   => api.get('/regimes', { params }).then(r => r.data),
    create: (data)     => api.post('/regimes', data).then(r => r.data.data),
    update: (id, data) => api.put(`/regimes/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/regimes/${id}`).then(r => r.data),
  },
  destinations: {
    list:   (params)   => api.get('/destinations', { params }).then(r => r.data),
    create: (data)     => api.post('/destinations', data).then(r => r.data.data),
    update: (id, data) => api.put(`/destinations/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/destinations/${id}`).then(r => r.data),
  },
  companies: {
    list:   (params)   => api.get('/companies', { params }).then(r => r.data),
    create: (data)     => api.post('/companies', data).then(r => r.data.data),
    update: (id, data) => api.put(`/companies/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/companies/${id}`).then(r => r.data),
  },
  settings: {
    list:   ()         => api.get('/system-settings').then(r => r.data.data),
    update: (id, val)  => api.put(`/system-settings/${id}`, { value: val }).then(r => r.data.data),
  },
  users: {
    list:   (params)   => api.get('/users', { params }).then(r => r.data),
    get:    (id)       => api.get(`/users/${id}`).then(r => r.data.data),
    create: (data)     => api.post('/users', data).then(r => r.data.data),
    update: (id, data) => api.put(`/users/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/users/${id}`).then(r => r.data),
  },
  roles: {
    list:   (params)   => api.get('/roles', { params }).then(r => r.data),
    create: (data)     => api.post('/roles', data).then(r => r.data.data),
    update: (id, data) => api.put(`/roles/${id}`, data).then(r => r.data.data),
    delete: (id)       => api.delete(`/roles/${id}`).then(r => r.data),
  },
  permissions: {
    list:       ()              => api.get('/permissions').then(r => r.data.data),
    create:     (data)          => api.post('/permissions', data).then(r => r.data.data),
    update:     (id, data)      => api.put(`/permissions/${id}`, data).then(r => r.data.data),
    delete:     (id)            => api.delete(`/permissions/${id}`).then(r => r.data),
    autoCreate: (type, slug)    => api.post('/permissions/auto-create', { type, slug }).then(r => r.data),
  },
};
