import api from './api';

export const retrievalService = {
  list:            (params = {}) => api.get('/device-retrievals', { params }).then(r => r.data),
  get:             (id)          => api.get(`/device-retrievals/${id}`).then(r => r.data.data),
  create:          (data)        => api.post('/device-retrievals', data).then(r => r.data.data),
  update:          (id, data)    => api.put(`/device-retrievals/${id}`, data).then(r => r.data.data),
  delete:          (id)          => api.delete(`/device-retrievals/${id}`).then(r => r.data),
  report:          (params = {}) => api.get('/device-retrievals/report', { params }).then(r => r.data.data),
  export:          (params = {}) => api.get('/device-retrievals/export', { params, responseType: 'blob' }),
  generateInvoice: (id)          => api.post(`/device-retrievals/${id}/generate-invoice`).then(r => r.data.data),
  invoice:         (id)          => api.get(`/device-retrievals/${id}/invoice`).then(r => r.data.data),
};
