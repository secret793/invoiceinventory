import React, { useState } from 'react';
import Modal from '../common/Modal';
import { retrievalService } from '../../services/retrievalService';
import { useNotification } from '../../contexts/NotificationContext';

export default function InvoiceModal({ retrieval, isOpen, onClose, onGenerated }) {
  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(false);
  const { notify } = useNotification();

  const generate = async () => {
    setLoading(true);
    try {
      const data = await retrievalService.generateInvoice(retrieval.id);
      setInvoice(data);
      notify.success('Invoice generated successfully');
      onGenerated?.(data);
    } catch (e) {
      notify.error(e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Generate Invoice" size="md"
      footer={
        <>
          <button onClick={onClose} className="btn-secondary">Close</button>
          {!invoice && (
            <button onClick={generate} disabled={loading} className="btn-primary">
              {loading ? 'Generating…' : 'Generate Invoice'}
            </button>
          )}
        </>
      }>
      {!invoice ? (
        <div className="space-y-3 text-sm">
          <div className="grid grid-cols-2 gap-3">
            <div><span className="text-gray-500">BOE:</span> <strong>{retrieval?.boe}</strong></div>
            <div><span className="text-gray-500">Vehicle:</span> <strong>{retrieval?.vehicle_number}</strong></div>
            <div><span className="text-gray-500">Device:</span> <strong>{retrieval?.device_identifier}</strong></div>
            <div><span className="text-gray-500">Overstay Days:</span> <strong className="text-red-600">{retrieval?.overstay_days ?? 0}</strong></div>
          </div>
          <p className="text-gray-500 text-xs mt-3">Click "Generate Invoice" to calculate and record the overstay charges.</p>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="bg-green-50 border border-green-200 rounded-xl p-4 text-sm">
            <p className="font-semibold text-green-800 mb-3">Invoice Generated Successfully</p>
            <div className="grid grid-cols-2 gap-2 text-green-700">
              <div>Overstay Days: <strong>{invoice.overstay_days}</strong></div>
              <div>Exchange Rate: <strong>{invoice.exchange_rate}</strong></div>
              <div className="col-span-2 text-lg font-bold">
                Overstay Amount: <span className="text-red-600">GMD {Number(invoice.overstay_amount || 0).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </Modal>
  );
}
