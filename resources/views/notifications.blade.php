@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div x-data="notificationsPage()">

  <div class="page-header">
    <div><h1>Notifications &amp; Alerts</h1><div class="page-breadcrumb"><a href="{{ route('dashboard') }}">Home</a> / Notifications</div></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" @click="markAllRead()"><i class="bi bi-check2-all me-1"></i>Mark All Read</button>
      <button class="btn btn-primary btn-sm" @click="showRuleModal=true"><i class="bi bi-bell-plus me-1"></i>New Alert Rule</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card stat-danger"><div class="stat-icon"><i class="bi bi-bell-fill"></i></div><div><div class="stat-value" x-text="unreadCount">0</div><div class="stat-label">Unread Alerts</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-value">3</div><div class="stat-label">Critical Alerts</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-sliders"></i></div><div><div class="stat-value">12</div><div class="stat-label">Active Alert Rules</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-envelope-check"></i></div><div><div class="stat-value">284</div><div class="stat-label">Sent This Month</div></div></div></div>
  </div>

  <!-- Tabs -->
  <div class="pills-nav mb-3">
    <button class="pill-btn" :class="{active: tab==='notifications'}" @click="tab='notifications'">
      <i class="bi bi-bell me-1"></i>Notifications
      <span x-show="unreadCount > 0" class="badge bg-danger ms-1" style="font-size:10px" x-text="unreadCount"></span>
    </button>
    <button class="pill-btn" :class="{active: tab==='rules'}" @click="tab='rules'"><i class="bi bi-sliders me-1"></i>Alert Rules</button>
  </div>

  <!-- Notifications List -->
  <div x-show="tab==='notifications'">
    <!-- Filter Pills -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <button class="btn btn-sm" :class="filterType==='' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterType=''">All</button>
      <button class="btn btn-sm" :class="filterType==='critical' ? 'btn-danger' : 'btn-outline-secondary'" @click="filterType='critical'"><i class="bi bi-exclamation-triangle me-1"></i>Critical</button>
      <button class="btn btn-sm" :class="filterType==='warning' ? 'btn-warning' : 'btn-outline-secondary'" @click="filterType='warning'"><i class="bi bi-exclamation-circle me-1"></i>Warning</button>
      <button class="btn btn-sm" :class="filterType==='info' ? 'btn-info' : 'btn-outline-secondary'" @click="filterType='info'"><i class="bi bi-info-circle me-1"></i>Info</button>
      <button class="btn btn-sm" :class="filterType==='unread' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterType='unread'">Unread Only</button>
    </div>

    <div class="d-flex flex-column gap-2">
      <template x-for="n in filteredNotifications" :key="n.id">
        <div class="card" :class="!n.read ? 'border-start border-4 border-primary' : ''" @click="markRead(n)" style="cursor:pointer">
          <div class="card-body py-2 px-3">
            <div class="d-flex align-items-start gap-3">
              <div class="stat-icon mt-1" :class="'stat-' + n.colorClass" style="width:36px;height:36px;font-size:16px;flex-shrink:0"><i class="bi" :class="n.icon"></i></div>
              <div class="flex-fill">
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-semibold" style="font-size:13px" :class="!n.read ? '' : 'text-muted'" x-text="n.title"></span>
                  <span x-show="!n.read" class="badge bg-primary" style="font-size:10px">New</span>
                  <span class="badge bg-light text-secondary border ms-auto" style="font-size:10px" x-text="n.type"></span>
                </div>
                <div style="font-size:12px" x-text="n.message"></div>
                <div class="text-muted-sm" x-text="n.time"></div>
              </div>
              <div class="d-flex gap-1 flex-shrink-0">
                <button class="btn btn-outline-secondary btn-sm btn-icon" title="Dismiss" @click.stop="dismiss(n)"><i class="bi bi-x"></i></button>
              </div>
            </div>
          </div>
        </div>
      </template>
      <div x-show="filteredNotifications.length===0" class="text-center py-5 text-muted">
        <i class="bi bi-bell-slash" style="font-size:32px;opacity:.3"></i>
        <div class="mt-2">No notifications in this category.</div>
      </div>
    </div>
  </div>

  <!-- Alert Rules -->
  <div x-show="tab==='rules'">
    <div class="card table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Rule Name</th><th>Entity Type</th><th>Condition</th><th>Severity</th><th>Recipients</th><th>Channel</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <template x-for="r in alertRules" :key="r.id">
                <tr>
                  <td class="fw-semibold" style="font-size:13px" x-text="r.name"></td>
                  <td><span class="badge bg-light text-secondary border" style="font-size:11px" x-text="r.entity"></span></td>
                  <td style="font-size:12px" x-text="r.condition"></td>
                  <td>
                    <span x-show="r.severity==='Critical'" class="badge-status badge-cancelled">Critical</span>
                    <span x-show="r.severity==='Warning'" class="badge-status badge-pending">Warning</span>
                    <span x-show="r.severity==='Info'" class="badge-status badge-approved">Info</span>
                  </td>
                  <td style="font-size:12px" x-text="r.recipients"></td>
                  <td>
                    <i x-show="r.channels.includes('email')" class="bi bi-envelope text-muted me-1"></i>
                    <i x-show="r.channels.includes('sms')" class="bi bi-phone text-muted me-1"></i>
                    <i x-show="r.channels.includes('push')" class="bi bi-bell text-muted me-1"></i>
                  </td>
                  <td>
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" type="checkbox" :checked="r.active" @change="r.active=!r.active">
                    </div>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-pencil"></i></button>
                      <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- New Alert Rule Modal -->
  <div class="modal fade" :class="{show:showRuleModal}" :style="showRuleModal?'display:block':''" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-bell-plus me-2 text-primary"></i>New Alert Rule</h5><button class="btn-close" @click="showRuleModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Rule Name <span class="text-danger">*</span></label><input type="text" class="form-control" placeholder="e.g. Batch Expiry Warning"></div>
            <div class="col-md-6"><label class="form-label">Entity Type</label><select class="form-select"><option>Batch</option><option>Shipment</option><option>Order</option><option>Document</option><option>Scan</option></select></div>
            <div class="col-md-6"><label class="form-label">Condition</label><input type="text" class="form-control" placeholder="e.g. expiry_days < 90"></div>
            <div class="col-md-6"><label class="form-label">Severity</label><select class="form-select"><option>Info</option><option>Warning</option><option>Critical</option></select></div>
            <div class="col-md-6"><label class="form-label">Notification Channels</label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check"><input class="form-check-input" type="checkbox" id="ch_email" checked><label class="form-check-label" for="ch_email">Email</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" id="ch_push" checked><label class="form-check-label" for="ch_push">Push</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" id="ch_sms"><label class="form-check-label" for="ch_sms">SMS</label></div>
              </div>
            </div>
            <div class="col-12"><label class="form-label">Recipients</label><input type="text" class="form-control" placeholder="Enter email addresses, separated by commas"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showRuleModal=false">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Rule</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" x-show="showRuleModal" @click="showRuleModal=false"></div>

</div>
@endsection

@push('scripts')
<script>
function notificationsPage() {
  return {
    tab:'notifications', filterType:'', showRuleModal:false,
    notifications:[
      { id:1, title:'Counterfeit Detected — Lagos, Nigeria',    message:'Code AC-2026-PHM-000897 flagged as counterfeit during scan at West Africa Pharma.', type:'critical', colorClass:'danger',  icon:'bi-shield-x',           time:'Jun 10, 2026 · 11:42 AM', read:false },
      { id:2, title:'Batch Expiring in 12 Days',                message:'BRN-00067-2511-001 (Insulin Glargine) expires Jun 22, 2026. Review action required.', type:'critical', colorClass:'danger',  icon:'bi-exclamation-triangle',time:'Jun 10, 2026 · 09:14 AM', read:false },
      { id:3, title:'Import Permit Expiring — Nigeria',         message:'NAFDAC Import Permit expires Jun 30, 2026. Renewal required.',                        type:'warning',  colorClass:'warning', icon:'bi-file-earmark-x',      time:'Jun 10, 2026 · 08:00 AM', read:false },
      { id:4, title:'Shipment SHP-2026-0491 Delayed',           message:'DHL Express shipment to Cairo, Egypt is delayed by 2 days. New ETA: Jun 14, 2026.',   type:'warning',  colorClass:'warning', icon:'bi-truck',               time:'Jun 9, 2026  · 03:22 PM', read:false },
      { id:5, title:'PO Approved — PO-2026-0092',               message:'Purchase Order PO-2026-0092 for BD MedCo Ltd. has been approved.',                    type:'info',     colorClass:'success', icon:'bi-check2-circle',        time:'Jun 9, 2026  · 01:15 PM', read:true  },
      { id:6, title:'Shipment SHP-2026-0495 Delivered',         message:'EMS shipment to Lagos, Nigeria delivered successfully.',                               type:'info',     colorClass:'success', icon:'bi-box-seam',             time:'Jun 9, 2026  · 10:08 AM', read:true  },
      { id:7, title:'New User Registration',                    message:'ops.team@company.com has been added to the system with Logistics role.',               type:'info',     colorClass:'info',    icon:'bi-person-plus',          time:'Jun 8, 2026  · 09:55 AM', read:true  },
      { id:8, title:'Batch BRN-00033 Recalled',                 message:'Amoxicillin 250mg Capsules LOT-2510-001 has been recalled. Qty: 0 remaining.',        type:'critical', colorClass:'danger',  icon:'bi-exclamation-diamond',  time:'Jun 7, 2026  · 02:30 PM', read:true  },
    ],
    alertRules:[
      { id:1, name:'Batch Expiry Warning',      entity:'Batch',    condition:'expiry_days < 90',     severity:'Warning',  recipients:'2 users', channels:['email','push'], active:true  },
      { id:2, name:'Batch Expiry Critical',     entity:'Batch',    condition:'expiry_days < 30',     severity:'Critical', recipients:'4 users', channels:['email','push','sms'], active:true  },
      { id:3, name:'Counterfeit Scan Alert',    entity:'Scan',     condition:'result = counterfeit', severity:'Critical', recipients:'5 users', channels:['email','push','sms'], active:true  },
      { id:4, name:'Shipment Delay Alert',      entity:'Shipment', condition:'delayed = true',       severity:'Warning',  recipients:'3 users', channels:['email','push'], active:true  },
      { id:5, name:'Permit Expiry Warning',     entity:'Document', condition:'expiry_days < 60',     severity:'Warning',  recipients:'2 users', channels:['email'],        active:true  },
      { id:6, name:'Order Pending > 7 days',    entity:'Order',    condition:'pending_days > 7',     severity:'Info',     recipients:'1 user',  channels:['push'],         active:false },
    ],
    get unreadCount(){ return this.notifications.filter(n=>!n.read).length; },
    get filteredNotifications(){
      return this.notifications.filter(n => {
        if(this.filterType==='unread') return !n.read;
        if(this.filterType) return n.type===this.filterType;
        return true;
      });
    },
    markRead(n){ n.read=true; },
    markAllRead(){ this.notifications.forEach(n=>n.read=true); },
    dismiss(n){ this.notifications = this.notifications.filter(x=>x.id!==n.id); }
  };
}
</script>
@endpush
