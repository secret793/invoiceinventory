import React from 'react';

const STATUS_MAP = {
  // Device statuses
  UNCONFIGURED:    { cls: 'badge-gray',   label: 'Unconfigured' },
  CONFIGURED:      { cls: 'badge-blue',   label: 'Configured'   },
  ALLOCATED:       { cls: 'badge-purple', label: 'Allocated'    },
  DISTRIBUTED:     { cls: 'badge-orange', label: 'Distributed'  },
  ACTIVE:          { cls: 'badge-green',  label: 'Active'       },
  IN_USE:          { cls: 'badge-green',  label: 'In Use'       },
  RETURNED:        { cls: 'badge-yellow', label: 'Returned'     },
  FAULTY:          { cls: 'badge-red',    label: 'Faulty'       },
  LOST:            { cls: 'badge-red',    label: 'Lost'         },
  RECEIVED:        { cls: 'badge-blue',   label: 'Received'     },
  // Retrieval
  NOT_RETRIEVED:   { cls: 'badge-yellow', label: 'Not Retrieved' },
  RETRIEVED:       { cls: 'badge-green',  label: 'Retrieved'    },
  OVERDUE:         { cls: 'badge-red',    label: 'Overdue'      },
  // Payment
  PP:              { cls: 'badge-yellow', label: 'Pending'      },
  PAID:            { cls: 'badge-green',  label: 'Paid'         },
  WAIVED:          { cls: 'badge-gray',   label: 'Waived'       },
  EXEMPTED:        { cls: 'badge-blue',   label: 'Exempted'     },
  // Transfer
  PENDING:         { cls: 'badge-yellow', label: 'Pending'      },
  APPROVED:        { cls: 'badge-green',  label: 'Approved'     },
  CANCELLED:       { cls: 'badge-red',    label: 'Cancelled'    },
  COMPLETED:       { cls: 'badge-green',  label: 'Completed'    },
  // Generic
  active:          { cls: 'badge-green',  label: 'Active'       },
  inactive:        { cls: 'badge-gray',   label: 'Inactive'     },
};

export default function StatusBadge({ status, className = '' }) {
  const key = String(status || '').toUpperCase();
  const cfg = STATUS_MAP[key] || STATUS_MAP[status] || { cls: 'badge-gray', label: status || '—' };
  return <span className={`${cfg.cls} ${className}`}>{cfg.label}</span>;
}
