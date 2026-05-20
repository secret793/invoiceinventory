import api from '../services/api';

export async function downloadFile(url, defaultFilename = 'download.csv') {
  const res = await api.get(url, { responseType: 'blob' });

  const disposition = res.headers?.['content-disposition'] || '';
  const match = disposition.match(/filename[^;=\n]*=(['"]?)([^'";\n]+)\1/i);
  const filename = match ? match[2].trim() : defaultFilename;

  const blobUrl = window.URL.createObjectURL(new Blob([res.data]));
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(blobUrl);
  document.body.removeChild(a);
}
