/* ============================================================
   js/admin.js — Admin Dashboard, Clinic Visits, Students,
                 Staff, Slots, Reports
   OLLC School Clinic · Clinic Visit Monitoring System
   ============================================================ */

'use strict';

/* ============================================================
   ADMIN DASHBOARD
   ============================================================ */

async function renderAdminDash() {
  document.getElementById('aDashSub').textContent = new Date().toLocaleDateString('en-PH', {
    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
  });

  var stats = await API.aptStats();
  if (stats.success) {
    document.getElementById('as-today').textContent    = stats.today    || 0;
    document.getElementById('as-pending').textContent  = stats.pending  || 0;
    document.getElementById('as-approved').textContent = stats.approved || 0;
    document.getElementById('as-urgent').textContent   = stats.urgent   || 0;
  }

  // Today's timeline
  var res       = await API.listApts({ date_filter: 'today' });
  var tl        = document.getElementById('todayTimeline');
  var todayApts = (res.data || []).filter(function (a) { return a.status !== 'Cancelled'; });

  if (!todayApts.length) {
    tl.innerHTML = '<div class="empty-state"><div class="ei"><i class="fa-regular fa-calendar-xmark"></i></div>No clinic visits scheduled today.</div>';
  } else {
    tl.innerHTML = todayApts.map(function (a) {
      var sname = (a.first_name || '') + ' ' + (a.last_name || '');
      return '<div class="tl-item">' +
        '<div class="tl-time">' + a.apt_time + '</div>' +
        '<div class="tl-dot' + (a.priority === 'urgent' ? ' urgent' : '') + '"></div>' +
        '<div class="tl-body">' +
          '<div class="tl-name">' + sname +
            (a.priority === 'urgent'
              ? ' <span class="badge badge-urgent"><i class="fa-solid fa-triangle-exclamation"></i> Urgent</span>'
              : '') +
          '</div>' +
          '<div class="tl-sub">' + a.reason + ' · ' + statusBadge(a.status) + '</div>' +
        '</div>' +
        '</div>';
    }).join('');
  }

  setTimeout(buildWeekChart,   100);
  setTimeout(buildStatusChart, 200);
}

async function buildWeekChart() {
  destroyChart('week');
  var ctx = document.getElementById('chartWeek');
  if (!ctx) return;
  var res = await API.weekData();
  charts.week = new Chart(ctx, {
    type: 'bar',
    data: {
      labels:   res.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
      datasets: [{
        label:           'Clinic Visits',
        data:            res.data || [0, 0, 0, 0, 0, 0],
        backgroundColor: 'rgba(26,58,107,0.8)',
        borderRadius:    6
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales:  { y: { beginAtZero: true, grid: { color: '#eee' } } }
    }
  });
}

async function buildStatusChart() {
  destroyChart('status');
  var ctx = document.getElementById('chartStatus');
  if (!ctx) return;
  var stats    = await API.aptStats();
  var approved  = parseInt(stats.approved  || 0);
  var pending   = parseInt(stats.pending   || 0);
  var urgent    = parseInt(stats.urgent    || 0);
  var cancelled = parseInt(stats.cancelled || 0);
  charts.status = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels:   ['Pending', 'Approved', 'Cancelled', 'Urgent'],
      datasets: [{
        data:            [pending, approved, cancelled, urgent],
        backgroundColor: ['#f0c030', '#1a3a6b', '#e63946', '#1e6fa8'],
        borderWidth:     0
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
  });
}

/* ============================================================
   ADMIN APPOINTMENTS
   ============================================================ */

async function renderAdminApts() {
  var q  = document.getElementById('aSearch').value  || '';
  var sf = document.getElementById('aStatusF').value || '';
  var df = document.getElementById('aDateF').value   || '';

  var res = await API.listApts({ q, status: sf, date_filter: df });
  var tb  = document.getElementById('adminAptTbody');

  if (!res.success) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="8">Error loading visit records.</td></tr>';
    return;
  }

  var list = res.data || [];
  if (!list.length) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="8">No clinic visit records found.</td></tr>';
    return;
  }

  tb.innerHTML = list.map(function (a) {
    var sname     = (a.first_name || '') + ' ' + (a.last_name || '');
    var urgentBadge = a.priority === 'urgent'
      ? '<span class="badge badge-urgent"><i class="fa-solid fa-triangle-exclamation"></i> Urgent</span>'
      : '<span class="badge badge-normal">Normal</span>';
    var quickBtns = a.status === 'Pending'
      ? '<button class="btn btn-success btn-sm" onclick="quickApprove(' + a.id + ')">Approve</button>' +
        '<button class="btn btn-danger  btn-sm" onclick="quickCancel('  + a.id + ')">Cancel</button>'
      : '';
    return '<tr>' +
      '<td class="mono">' + a.apt_code + '</td>' +
      '<td><div style="font-weight:600">' + sname + '</div>' +
          '<div style="font-size:.72rem;color:var(--text2)">' + (a.patient_no || '') + '</div></td>' +
      '<td>' + fmtDate(a.apt_date) + '</td>' +
      '<td style="font-family:var(--font-mono);font-size:.82rem">' + a.apt_time + '</td>' +
      '<td>' + (a.service_name
          ? ('<span style="color:var(--accent);font-size:.82rem"><i class="fa-solid ' + (a.service_icon || 'fa-stethoscope') + '" style="margin-right:.25rem"></i>' + a.service_name + '</span>')
          : '<span style="color:var(--text3)">—</span>') + '</td>' +
      '<td>' + urgentBadge + '</td>' +
      '<td>' + statusBadge(a.status) + '</td>' +
      '<td><div style="display:flex;gap:.35rem;flex-wrap:wrap">' +
        '<button class="btn btn-ghost btn-sm" onclick="viewAdminApt(' + a.id + ')">View</button>' +
        quickBtns +
      '</div></td>' +
      '</tr>';
  }).join('');
}

async function quickApprove(id) {
  var res = await API.updateApt({ id, status: 'Approved' });
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Visit confirmed!', 'success');
  renderAdminApts();
}

async function quickCancel(id) {
  var res = await API.updateApt({ id, status: 'Cancelled' });
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Visit cancelled.', 'success');
  renderAdminApts();
}

async function viewAdminApt(id) {
  var res = await API.listApts();
  var a   = (res.data || []).find(function (x) { return x.id == id; });
  if (!a) return;
  var sname = (a.first_name || '') + ' ' + (a.last_name || '');

  var slotsRes = await API.slots(a.apt_date);
  var slotOpts = (slotsRes.slots || []).map(function (s) {
    return '<option' + (s.time === a.apt_time ? ' selected' : '') + '>' + s.time + '</option>';
  }).join('');

  var urgentBadge = a.priority === 'urgent'
    ? '<span class="badge badge-urgent"><i class="fa-solid fa-triangle-exclamation"></i> Urgent</span>'
    : '<span class="badge badge-normal">Normal</span>';

  var body =
    '<div class="info-grid" style="margin-bottom:1.2rem">' +
    row('Reference No.', '<span class="mono">' + a.apt_code + '</span>') +
    row('Patient',       '<strong>' + sname + '</strong>') +
    row('Patient No.',   a.patient_no || '—') +
    row('Service',       a.service_name
          ? ('<i class="fa-solid ' + (a.service_icon || 'fa-stethoscope') + '" style="color:var(--accent);margin-right:.3rem"></i><strong>' + a.service_name + '</strong>')
          : '—') +
    row('Reason',        a.reason || '—') +
    row('Date',          fmtDate(a.apt_date)) +
    row('Time',          '<span class="mono">' + a.apt_time + '</span>') +
    row('Status',        statusBadge(a.status)) +
    row('Priority',      urgentBadge) +
    '</div>' +
    (a.notes ? '<div style="font-size:.82rem;color:var(--text2);margin-bottom:.3rem">Patient Notes:</div><div class="detail-block" style="margin-bottom:1rem">' + a.notes + '</div>' : '') +
    '<div class="form-group"><label>Clinic Notes</label>' +
    '<textarea class="form-control textarea-field" id="mNotes">' + (a.admin_notes || '') + '</textarea></div>' +
    '<div class="form-group"><label>Priority</label>' +
    '<select class="form-control" id="mPriority">' +
    '<option value="normal"' + (a.priority === 'normal' ? ' selected' : '') + '>Normal</option>' +
    '<option value="urgent"' + (a.priority === 'urgent' ? ' selected' : '') + '>Urgent</option>' +
    '</select></div>' +
    (a.status === 'Pending' || a.status === 'Approved'
      ? '<div class="form-group"><label>Reschedule Date &amp; Time</label>' +
        '<div style="display:flex;gap:.6rem">' +
        '<input class="form-control" type="date" id="mRDate" value="' + a.apt_date + '" min="' + todayStr() + '" style="flex:1">' +
        '<select class="form-control" id="mRTime" style="flex:1">' + slotOpts + '</select></div></div>'
      : '');

  var btns = [{ cls: 'btn-ghost', lbl: 'Save Notes', fn: function () { saveAdminNotes(id); } }];
  if (a.status === 'Pending') {
    btns.push({ cls: 'btn-success', lbl: 'Approve',    fn: function () { modalApprove(id); } });
  }
  if (a.status === 'Pending' || a.status === 'Approved') {
    btns.push({ cls: 'btn-warning', lbl: 'Reschedule', fn: function () { modalReschedule(id); } });
    btns.push({ cls: 'btn-danger',  lbl: 'Cancel',     fn: function () { modalCancel(id); } });
  }

  showModal('Visit Record: ' + a.apt_code, body, btns);
}

async function saveAdminNotes(id) {
  var res = await API.updateApt({
    id,
    admin_notes: document.getElementById('mNotes').value,
    priority:    document.getElementById('mPriority').value
  });
  if (!res.success) { toast(res.error, 'error'); return; }
  toast('Notes saved!', 'success');
  closeModal(); renderAdminApts();
}

async function modalApprove(id) {
  var res = await API.updateApt({
    id,
    status:      'Approved',
    admin_notes: document.getElementById('mNotes').value,
    priority:    document.getElementById('mPriority').value
  });
  if (!res.success) { toast(res.error, 'error'); return; }
  toast('Approved!', 'success');
  closeModal(); renderAdminApts();
}

async function modalReschedule(id) {
  var res = await API.updateApt({
    id,
    status:      'Rescheduled',
    new_date:    document.getElementById('mRDate').value,
    new_time:    document.getElementById('mRTime').value,
    admin_notes: document.getElementById('mNotes').value
  });
  if (!res.success) { toast(res.error, 'error'); return; }
  toast('Rescheduled!', 'success');
  closeModal(); renderAdminApts();
}

async function modalCancel(id) {
  var res = await API.updateApt({ id, status: 'Cancelled' });
  if (!res.success) { toast(res.error, 'error'); return; }
  toast('Cancelled.', 'success');
  closeModal(); renderAdminApts();
}

/* ============================================================
   ADMIN STUDENTS
   ============================================================ */

async function renderStudents() {
  var q   = document.getElementById('stuSearch').value || '';
  var res = await API.students(q);
  var tb  = document.getElementById('stuTbody');

  if (!res.success || !res.data.length) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="5">No patients found.</td></tr>';
    return;
  }

  tb.innerHTML = res.data.map(function (s) {
    return '<tr>' +
      '<td class="mono">' + (s.patient_no || '—') + '</td>' +
      '<td><div style="font-weight:600">' + s.first_name + ' ' + s.last_name + '</div>' +
           '<div style="font-size:.72rem;color:var(--text3)">' + (s.phone || s.email) + '</div></td>' +
      '<td>' + s.email + '</td>' +
      '<td><span class="badge badge-approved">' + (s.apt_count || 0) + '</span></td>' +
      '<td><button class="btn btn-ghost btn-sm" onclick="viewStudent(' + s.id + ')">View Records</button></td>' +
      '</tr>';
  }).join('');
}

async function viewStudent(uid) {
  var resUser = await API.students('');
  var s = (resUser.data || []).find(function (x) { return x.id == uid; });
  if (!s) return;

  var histRes = await API.studentHistory(uid);
  var apts    = histRes.data || [];

  var body =
    '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;border:1px solid var(--border)">' +
    '<div style="font-weight:800;font-size:1rem">' + s.first_name + ' ' + s.last_name + '</div>' +
    '<div style="font-size:.8rem;color:var(--text2);margin-top:.3rem">' +
      (s.patient_no || '') + ' · ' + s.email +
      (s.phone ? ' · ' + s.phone : '') +
    '</div></div>' +
    '<div style="font-weight:700;margin-bottom:.7rem;font-size:.88rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text2)">Visit History (' + apts.length + ')</div>' +
    (apts.length
      ? apts.map(function (a) {
          return '<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:.65rem 0;border-bottom:1px solid var(--border);gap:.5rem">' +
            '<div>' +
              (a.service_name ? '<div style="font-size:.72rem;color:var(--accent);font-weight:700;margin-bottom:.12rem">' + a.service_name + '</div>' : '') +
              '<div style="font-weight:600;font-size:.84rem">' + (a.reason || 'No reason') + '</div>' +
              '<div style="color:var(--text3);font-size:.72rem;margin-top:.08rem">' + fmtDate(a.apt_date) + ' · ' + a.apt_time + '</div>' +
            '</div>' +
            statusBadge(a.status) + '</div>';
        }).join('')
      : '<div style="text-align:center;color:var(--text3);padding:1.5rem">No clinic visits recorded.</div>');

  showModal('Patient: ' + s.first_name + ' ' + s.last_name, body, []);
}

/* ============================================================
   ADMIN CLINIC STAFF
   ============================================================ */

async function renderStaff() {
  var res = await API.staffList();
  var tb  = document.getElementById('staffTbody');
  if (!tb) return;

  if (!res.success || !res.data.length) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="6">No staff found.</td></tr>';
    return;
  }

  tb.innerHTML = res.data.map(function (s) {
    var active = s.is_active == 1;
    return '<tr>' +
      '<td><strong>' + s.first_name + ' ' + s.last_name + '</strong></td>' +
      '<td>' + s.email + '</td>' +
      '<td>' + (s.position || '—') + '</td>' +
      '<td>' + (s.license_no || '—') + '</td>' +
      '<td><span class="badge ' + (active ? 'badge-approved' : 'badge-cancelled') + '">' +
        (active
          ? '<i class="fa-solid fa-circle-check"></i> Active'
          : '<i class="fa-solid fa-circle-xmark"></i> Inactive') +
        '</span></td>' +
      '<td><div style="display:flex;gap:.35rem">' +
        '<button class="btn btn-ghost btn-sm" onclick="toggleStaff(' + s.id + ',' + (active ? 0 : 1) + ')">' +
          (active ? 'Deactivate' : 'Activate') +
        '</button>' +
        '<button class="btn btn-danger btn-sm" onclick="deleteStaff(' + s.id + ')">Delete</button>' +
      '</div></td>' +
      '</tr>';
  }).join('');
}

function openAddStaff() {
  showModal('Add Clinic Staff',
    '<div class="form-row">' +
    '<div class="form-group"><label>First Name</label><input class="form-control" id="sfFirst" placeholder="Juan"></div>' +
    '<div class="form-group"><label>Last Name</label><input class="form-control" id="sfLast" placeholder="Dela Cruz"></div>' +
    '</div>' +
    '<div class="form-group"><label>Email</label><input class="form-control" type="email" id="sfEmail" placeholder="nurse@ollc.edu"></div>' +
    '<div class="form-group"><label>Password</label><input class="form-control" type="password" id="sfPw" placeholder="Temporary password"></div>' +
    '<div class="form-group"><label>Position</label><input class="form-control" id="sfPos" value="Clinic Nurse"></div>' +
    '<div class="form-group"><label>License No.</label><input class="form-control" id="sfLic" placeholder="RN-00000"></div>' +
    '<div class="form-group"><label>Role</label>' +
    '<select class="form-control" id="sfRole">' +
    '<option value="staff">Clinic Staff</option><option value="admin">Admin</option>' +
    '</select></div>',
    [{ cls: 'btn-primary', lbl: 'Create Account', fn: confirmAddStaff }]
  );
}

async function confirmAddStaff() {
  var data = {
    firstName: document.getElementById('sfFirst').value.trim(),
    lastName:  document.getElementById('sfLast').value.trim(),
    email:     document.getElementById('sfEmail').value.trim(),
    password:  document.getElementById('sfPw').value,
    position:  document.getElementById('sfPos').value.trim(),
    licenseNo: document.getElementById('sfLic').value.trim(),
    role:      document.getElementById('sfRole').value,
  };
  if (!data.firstName || !data.email || !data.password) {
    toast('Please fill all required fields.', 'error'); return;
  }
  var res = await API.addStaff(data);
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Staff account created!', 'success');
  closeModal(); renderStaff();
}

async function toggleStaff(uid, active) {
  var res = await API.toggleStaff(uid, active);
  if (!res.success) { toast('Error', 'error'); return; }
  toast(active ? 'Staff activated.' : 'Staff deactivated.', 'success');
  renderStaff();
}

async function deleteStaff(uid) {
  if (!confirm('Delete this staff account? This cannot be undone.')) return;
  var res = await API.deleteStaff(uid);
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Staff account deleted.', 'success');
  renderStaff();
}

/* ============================================================
   ADMIN TIME SLOTS
   ============================================================ */

async function renderSlots() {
  var res = await API.adminSlots();
  var tb  = document.getElementById('slotsTbody');

  if (!res.success || !res.data.length) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="4">No time slots configured.</td></tr>';
    return;
  }

  tb.innerHTML = res.data.map(function (s) {
    return '<tr>' +
      '<td>' + s.slot_time + '</td>' +
      '<td>' + s.max_per_day + ' per day</td>' +
      '<td><span class="badge badge-approved"><i class="fa-solid fa-circle-check"></i> Active</span></td>' +
      '<td><div style="display:flex;gap:.4rem">' +
        '<button class="btn btn-ghost btn-sm" onclick="editSlot(' + s.id + ',\'' + s.slot_time + '\',' + s.max_per_day + ')">Edit Limit</button>' +
        '<button class="btn btn-danger btn-sm" onclick="delSlot(' + s.id + ')">Remove</button>' +
      '</div></td></tr>';
  }).join('');
}

function openAddSlot() {
  showModal('Add Time Slot',
    '<div class="form-group"><label>Time Slot</label>' +
    '<input class="form-control" id="nsTime" placeholder="e.g. 4:30 PM"></div>' +
    '<div class="form-group"><label>Max Bookings Per Day</label>' +
    '<input class="form-control" type="number" id="nsMax" value="3" min="1"></div>',
    [{ cls: 'btn-primary', lbl: 'Add Slot', fn: confirmAddSlot }]
  );
}

async function confirmAddSlot() {
  var t = document.getElementById('nsTime').value.trim();
  var m = parseInt(document.getElementById('nsMax').value) || 3;
  if (!t) { toast('Please enter a time.', 'error'); return; }
  var res = await API.addSlot(t, m);
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Time slot added!', 'success');
  closeModal(); renderSlots();
}

function editSlot(id, time, curMax) {
  var v = prompt('Max bookings per day for ' + time + ':', curMax);
  if (v !== null && !isNaN(v) && parseInt(v) > 0) {
    API.editSlot(id, parseInt(v)).then(function (res) {
      if (!res.success) { toast('Error updating slot.', 'error'); return; }
      toast('Slot limit updated!', 'success');
      renderSlots();
    });
  }
}

async function delSlot(id) {
  if (!confirm('Remove this time slot?')) return;
  var res = await API.deleteSlot(id);
  if (!res.success) { toast('Error removing slot.', 'error'); return; }
  toast('Time slot removed.', 'success');
  renderSlots();
}

/* ============================================================
   ADMIN REPORTS & AUDIT
   ============================================================ */

async function renderReports() {
  var res = await API.reportStats();
  if (!res.success) return;

  document.getElementById('rTotal').textContent     = res.total     || 0;
  document.getElementById('rApproved').textContent  = res.approved  || 0;
  document.getElementById('rCancelled').textContent = res.cancelled || 0;

  var auditRes = await API.audit();
  var actionClass = {
    APPROVE:    'badge-approved',
    CANCEL:     'badge-cancelled',
    RESCHEDULE: 'badge-rescheduled',
    BOOK:       'badge-pending',
    ADD_STAFF:  'badge-approved',
    UPDATE:     'badge-normal'
  };
  var tb = document.getElementById('auditTbody');
  tb.innerHTML = (auditRes.data || []).map(function (l) {
    return '<tr>' +
      '<td style="font-family:\'DM Mono\',monospace;font-size:.75rem">' + (l.created_at || '') + '</td>' +
      '<td><span class="badge ' + (actionClass[l.action] || 'badge-normal') + '">' + l.action + '</span></td>' +
      '<td>' + (l.by_name || 'System') + '</td>' +
      '<td style="font-size:.83rem">' + l.detail + '</td>' +
      '</tr>';
  }).join('');

  setTimeout(function () {
    buildReasonChart(res.by_reason || res.reasons || []);
    buildTrendChart(res.trend || []);
  }, 100);
}

function buildReasonChart(reasons) {
  destroyChart('reason');
  var ctx = document.getElementById('chartReason');
  if (!ctx || !reasons.length) return;
  var colors = ['#1a3a6b', '#2556a8', '#f0c030', '#1e6fa8', '#e63946', '#3b82f6', '#64748b', '#0ea5e9'];
  charts.reason = new Chart(ctx, {
    type: 'bar',
    data: {
      labels:   reasons.map(function (r) { return r.reason; }),
      datasets: [{
        data:            reasons.map(function (r) { return parseInt(r.cnt); }),
        backgroundColor: colors,
        borderRadius:    6
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true }, x: { ticks: { font: { size: 9 } } } }
    }
  });
}

function buildTrendChart(trend) {
  destroyChart('trend');
  var ctx = document.getElementById('chartTrend');
  if (!ctx || !trend.length) return;
  charts.trend = new Chart(ctx, {
    type: 'line',
    data: {
      labels: trend.map(function (t) { return t.month; }),
      datasets: [{
        label:           'Monthly Visits',
        data:            trend.map(function (t) { return parseInt(t.cnt); }),
        fill:            true,
        backgroundColor: 'rgba(26,58,107,0.08)',
        borderColor:     '#1a3a6b',
        tension:         0.4,
        pointBackgroundColor: '#1a3a6b',
        pointRadius:     4
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });
}

/* ============================================================
   ADMIN SERVICES MANAGEMENT
   ============================================================ */

async function renderServices() {
  var res = await API.adminServicesList();
  var tb  = document.getElementById('servicesTbody');
  if (!tb) return;

  if (!res.success || !res.data.length) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="5">No services found.</td></tr>';
    return;
  }

  tb.innerHTML = res.data.map(function (s) {
    var active = s.is_active == 1;
    return '<tr>' +
      '<td>' +
        '<div style="display:flex;align-items:center;gap:.6rem">' +
          '<div style="width:30px;height:30px;border-radius:7px;background:var(--accent-pale);display:flex;align-items:center;justify-content:center;flex-shrink:0">' +
            '<i class="fa-solid ' + (s.icon || 'fa-stethoscope') + '" style="color:var(--accent);font-size:.82rem"></i>' +
          '</div>' +
          '<div><div style="font-weight:600;font-size:.88rem">' + s.name + '</div>' +
               '<div style="font-size:.72rem;color:var(--text3)">' + (s.description || '') + '</div></div>' +
        '</div>' +
      '</td>' +
      '<td class="mono" style="font-size:.78rem">' + (s.icon || '—') + '</td>' +
      '<td>' + s.sort_order + '</td>' +
      '<td><span class="badge ' + (active ? 'badge-approved' : 'badge-cancelled') + '">' +
        (active
          ? '<i class="fa-solid fa-circle-check"></i> Active'
          : '<i class="fa-solid fa-circle-xmark"></i> Inactive') +
        '</span></td>' +
      '<td>' +
        '<button class="btn btn-ghost btn-sm" onclick="toggleSvc(' + s.id + ',' + (active ? 0 : 1) + ')">' +
          (active ? 'Disable' : 'Enable') +
        '</button>' +
      '</td>' +
      '</tr>';
  }).join('');
}

function openAddService() {
  showModal('Add Clinic Service',
    '<div class="form-group"><label>Service Name</label>' +
    '<input class="form-control" id="svcName" placeholder="e.g. General Check-Up"></div>' +
    '<div class="form-group"><label>Description <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>' +
    '<input class="form-control" id="svcDesc" placeholder="Short description of the service"></div>' +
    '<div class="form-group"><label>Font Awesome Icon Class</label>' +
    '<input class="form-control" id="svcIcon" placeholder="fa-stethoscope" value="fa-stethoscope"></div>' +
    '<div style="font-size:.74rem;color:var(--text3);margin-top:-.4rem;margin-bottom:.8rem">' +
    'Example: fa-heart-pulse, fa-tooth, fa-syringe, fa-user-doctor</div>',
    [{ cls: 'btn-accent', lbl: 'Add Service', fn: confirmAddService }]
  );
}

async function confirmAddService() {
  var name = document.getElementById('svcName').value.trim();
  var desc = document.getElementById('svcDesc').value.trim();
  var icon = document.getElementById('svcIcon').value.trim() || 'fa-stethoscope';

  if (!name) { toast('Please enter a service name.', 'error'); return; }

  var res = await API.addService({ name, description: desc, icon });
  if (!res.success) { toast(res.error || 'Error', 'error'); return; }
  toast('Service added!', 'success');
  closeModal();
  renderServices();
}

async function toggleSvc(id, active) {
  var res = await API.toggleService(id, active);
  if (!res.success) { toast('Error updating service.', 'error'); return; }
  toast(active ? 'Service enabled.' : 'Service disabled.', 'success');
  renderServices();
}

/* ── Service Stats Card (used in admin dashboard) ─────────── */
async function renderServiceStats() {
  var el = document.getElementById('serviceStatsList');
  if (!el) return;

  var res = await API.serviceStats();
  if (!res.success || !res.data.length) {
    el.innerHTML = '<div style="text-align:center;color:var(--text3);padding:1rem;font-size:.85rem">No data yet.</div>';
    return;
  }

  var max = Math.max.apply(null, res.data.map(function (d) { return parseInt(d.visit_count) || 0; }));

  el.innerHTML = res.data.map(function (s) {
    var count = parseInt(s.visit_count) || 0;
    var pct   = max > 0 ? Math.round((count / max) * 100) : 0;
    return '<div style="display:flex;align-items:center;gap:.7rem;padding:.5rem 0;border-bottom:1px solid var(--border)">' +
      '<div style="width:22px;text-align:center;flex-shrink:0">' +
        '<i class="fa-solid ' + (s.icon || 'fa-stethoscope') + '" style="color:var(--accent);font-size:.82rem"></i>' +
      '</div>' +
      '<div style="flex:1;min-width:0">' +
        '<div style="font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + s.name + '</div>' +
        '<div style="height:4px;background:var(--border);border-radius:2px;margin-top:.3rem">' +
          '<div style="height:4px;background:var(--accent);border-radius:2px;width:' + pct + '%"></div>' +
        '</div>' +
      '</div>' +
      '<div style="font-size:.82rem;font-weight:700;color:var(--text);flex-shrink:0;min-width:24px;text-align:right">' + count + '</div>' +
    '</div>';
  }).join('');
}
