import api from './api';

export const retrievalService = {
  list:              (params = {}) => api.get('/device-retrievals', { params }).then(r => r.data),
  get:               (id)          => api.get(`/device-retrievals/${id}`).then(r => r.data.data),
  create:            (data)        => api.post('/device-retrievals', data).then(r => r.data.data),
  update:            (id, data)    => api.put(`/device-retrievals/${id}`, data).then(r => r.data.data),
  delete:            (id)          => api.delete(`/device-retrievals/${id}`).then(r => r.data),
  report:            (params = {}) => api.get('/device-retrievals/report', { params }).then(r => r.data.data),
  export:            (params = {}) => api.get('/device-retrievals/export', { params, responseType: 'blob' }),
  generateInvoice:   (id, data={}) => api.post(`/device-retrievals/${id}/generate-invoice`, data).then(r => r.data.data),
  invoice:           (id)          => api.get(`/device-retrievals/${id}/invoice`).then(r => r.data.data),
  retrieve:          (id, data)    => api.post(`/device-retrievals/${id}/retrieve`, data).then(r => r.data.data),
  returnOutstation:  (id, data)    => api.post(`/device-retrievals/${id}/return-outstation`, data).then(r => r.data.data),
  waiver:            (id, data)    => api.post(`/device-retrievals/${id}/waiver`, data).then(r => r.data.data),
  approvePayment:    (id, data)    => api.post(`/device-retrievals/${id}/approve-payment`, data).then(r => r.data.data),
  manualOverstay:    (id, data)    => api.post(`/device-retrievals/${id}/manual-overstay`, data).then(r => r.data.data),
  overstayDevices:   (params = {}) => api.get('/device-retrievals/overstay-devices', { params }).then(r => r.data.data),
  downloadInvoiceUrl:(id)          => `/api/device-retrievals/${id}/download-invoice`,
  checkLastDevice:   (id)          => api.get(`/device-retrievals/${id}/check-last-device`).then(r => r.data.data),
};

export const invoiceService = {
  list:  (params = {}) => api.get('/invoices', { params }).then(r => r.data),
  show:  (id)          => api.get(`/invoices/${id}`).then(r => r.data.data),
};
