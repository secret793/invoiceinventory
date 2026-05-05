import api from './api';

export const allocationService = {
  list:         ()          => api.get('/allocation-points').then(r => r.data.data),
  get:          (id)        => api.get(`/allocation-points/${id}`).then(r => r.data.data),
  devices:      (id, params)=> api.get(`/allocation-points/${id}/devices`, { params }).then(r => r.data),
  create:       (data)      => api.post('/allocation-points', data).then(r => r.data.data),
  update:       (id, data)  => api.put(`/allocation-points/${id}`, data).then(r => r.data.data),
  delete:       (id)        => api.delete(`/allocation-points/${id}`).then(r => r.data),
};
