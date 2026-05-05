import React from 'react';
import Modal from './Modal';

export default function ConfirmDialog({
  isOpen,
  onClose,
  onConfirm,
  title    = 'Are you sure?',
  message  = 'This action cannot be undone.',
  confirmLabel = 'Confirm',
  danger   = false,
  loading  = false,
}) {
  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} size="sm"
      footer={
        <>
          <button onClick={onClose} className="btn-secondary">Cancel</button>
          <button onClick={onConfirm} disabled={loading}
            className={danger ? 'btn-danger' : 'btn-primary'}>
            {loading ? 'Processing…' : confirmLabel}
          </button>
        </>
      }>
      <p className="text-gray-600 text-sm">{message}</p>
    </Modal>
  );
}
