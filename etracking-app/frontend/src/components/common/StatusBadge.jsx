import React from 'react';

const STATUS_MAP = {
  UNCONFIGURED:  { cls: 'badge-gray',   label: 'Unconfigured'   },
  CONFIGURED:    { cls: 'badge-blue',   label: 'Configured'     },
  ONLINE:        { cls: 'badge-green',  label: 'Online'         },
  OFFLINE:       { cls: 'badge-red',    label: 'Offline'        },
  DAMAGED:       { cls: 'badge-orange', label: 'Damaged'        },
  FIXED:         { cls: 'badge-purple', label: 'Fixed'          },
  LOST:          { cls: 'badge-red',    label: 'Lost'           },
  RECEIVED:      { cls: 'badge-blue',   label: 'Received'       },
  PENDING:       { cls: 'badge-yellow', label: 'Pending'        },
  RETRIEVED:     { cls: 'badge-gray',   label: 'Retrieved'      },
  ALLOCATED:     { cls: 'badge-purple', label: 'Allocated'      },
  DISTRIBUTED:   { cls: 'badge-orange', label: 'Distributed'    },
  ACTIVE:        { cls: 'badge-green',  label: 'Active'         },
  IN_USE:        { cls: 'badge-green',  label: 'In Use'         },
  RETURNED:      { cls: 'badge-yellow', label: 'Returned'       },
  FAULTY:        { cls: 'badge-red',    label: 'Faulty'         },
  NOT_RETRIEVED: { cls: 'badge-yellow', label: 'Not Retrieved'  },
  OVERDUE:       { cls: 'badge-red',    label: 'Overdue'        },
  PP:            { cls: 'badge-red',    label: 'Pending Pmt'    },
  PD:            { cls: 'badge-green',  label: 'Paid'           },
  PAID:          { cls: 'badge-green',  label: 'Paid'           },
  WAIVED:        { cls: 'badge-gray',   label: 'Waived'         },
  EXEMPTED:      { cls: 'badge-blue',   label: 'Exempted'       },
  APPROVED:      { cls: 'badge-green',  label: 'Approved'       },
  CANCELLED:     { cls: 'badge-red',    label: 'Cancelled'      },
  COMPLETED:     { cls: 'badge-green',  label: 'Completed'      },
  AFFIXED:       { cls: 'badge-green',  label: 'Affixed'        },
  SAD:           { cls: 'badge-blue',   label: 'SAD'            },
  TRUCK:         { cls: 'badge-green',  label: 'Truck'          },
  ALLOCATION:    { cls: 'badge-green',  label: 'Allocation'     },
  DISTRIBUTION:  { cls: 'badge-blue',   label: 'Distribution'   },
  OK:            { cls: 'badge-green',  label: 'OK'             },
  NEW:           { cls: 'badge-blue',   label: 'New'            },
  active:        { cls: 'badge-green',  label: 'Active'         },
  inactive:      { cls: 'badge-gray',   label: 'Inactive'       },
};

export default function StatusBadge({ status, className = '' }) {
  const key = String(status || '').toUpperCase();
  const cfg = STATUS_MAP[key] || STATUS_MAP[status] || { cls: 'badge-gray', label: status || '—' };
  return <span className={`${cfg.cls} ${className}`}>{cfg.label}</span>;
}
