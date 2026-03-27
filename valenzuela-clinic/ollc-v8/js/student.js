/* ============================================================
   js/student.js  —  Patient Dashboard, Booking, History
   Valenzuela Clinic System
   ============================================================ */

'use strict';

/* Service icons map (FA class → used in tables/cards) */
var SERVICE_ICONS = {
  'fa-heart-pulse':  '<i class="fa-solid fa-heart-pulse"></i>',
  'fa-baby':         '<i class="fa-solid fa-baby"></i>',
  'fa-tooth':        '<i class="fa-solid fa-tooth"></i>',
  'fa-syringe':      '<i class="fa-solid fa-syringe"></i>',
  'fa-droplet':      '<i class="fa-solid fa-droplet"></i>',
  'fa-user-doctor':  '<i class="fa-solid fa-user-doctor"></i>',
  'fa-video':        '<i class="fa-solid fa-video"></i>',
  'fa-seedling':     '<i class="fa-solid fa-seedling"></i>',
  'fa-stethoscope':  '<i class="fa-solid fa-stethoscope"></i>',
};

function svcIcon(icon) {
  return icon ? ('<i class="fa-solid ' + icon + '" style="color:var(--accent);margin-right:.3rem"></i>') : '';
}

/* ============================================================
   PATIENT DASHBOARD
   ============================================================ */
async function renderStudentDash() {
  var stats = await API.aptStats();
  if (stats.success) {
    document.getElementById('ss-total').textContent     = stats.total     || 0;
    document.getElementById('ss-pending').textContent   = stats.pending   || 0;
    document.getElementById('ss-approved').textContent  = stats.approved  || 0;
    document.getElementById('ss-cancelled').textContent = stats.cancelled || 0;
  }

  var res = await API.listApts();
  var tb  = document.getElementById('sRecentTbody');
  if (!res.success) {
    tb.innerHTML = '<tr class="empty-row"><td colspan="5">Error loading records.</td></tr>';
  } else {
    var recent = (res.data || []).slice(0, 5);
    if (!recent.length) {
      tb.innerHTML = '<tr class="empty-row"><td colspan="5">No appointments yet. ' +
        '<a href="#" onclick="nav(\'s-book\');return false;" style="color:var(--accent)">Schedule one now →</a></td></tr>';
    } else {
      tb.innerHTML = recent.map(function (a) {
        var svcCell = a.service_name
          ? (svcIcon(a.service_icon) + a.service_name)
          : '<span style="color:var(--text3)">—</span>';
        var action = a.status === 'Pending'
          ? '<button class="btn btn-danger btn-sm" onclick="cancelMyApt(' + a.id + ')">Cancel</button>'
          : '<button class="btn btn-ghost btn-sm"  onclick="viewMyApt('   + a.id + ')">View</button>';
        return '<tr>' +
          '<td>' + fmtDate(a.apt_date) + '</td>' +
          '<td style="font-family:var(--font-mono);font-size:.82rem">' + a.apt_time + '</td>' +
          '<td>' + svcCell + '</td>' +
          '<td>' + statusBadge(a.status) + '</td>' +
          '<td>' + action + '</td>' +
          '</tr>';
      }).join('');
    }
  }

  var notifRes = await API.notifications();
  var nl       = document.getElementById('sNotifList');
  var notifs   = (notifRes.data || []).slice(0, 3);
  if (!notifs.length) {
    nl.innerHTML = '<div style="text-align:center;color:var(--text3);padding:1rem;font-size:.85rem">No notifications yet.</div>';
  } else {
    nl.innerHTML = notifs.map(function (n) {
      return '<div class="notif-item' + (n.is_read == 0 ? ' unread' : '') + '">' +
        '<div class="notif-dot"></div>' +
        '<div><div class="notif-text">' + n.message + '</div>' +
        '<div class="notif-time">' + fmtDT(n.created_at) + '</div></div></div>';
    }).join('');
  }
}

async function viewMyApt(id) {
  var res = await API.listApts();
  var a   = (res.data || []).find(function (x) { return x.id == id; });
  if (!a) return;

  var urgentBadge = a.priority === 'urgent'
    ? '<span class="badge badge-urgent"><i class="fa-solid fa-triangle-exclamation"></i> Urgent</span>'
    : '<span class="badge badge-normal">Normal</span>';

  showModal('Appointment Details',
    '<div class="info-grid">' +
    row('Reference No.', '<span style="font-family:var(--font-mono);font-size:.82rem">' + a.apt_code + '</span>') +
    row('Service',       a.service_name ? (svcIcon(a.service_icon) + '<strong>' + a.service_name + '</strong>') : '—') +
    row('Reason',        a.reason || '—') +
    row('Date',          fmtDate(a.apt_date)) +
    row('Time',          '<span style="font-family:var(--font-mono)">' + a.apt_time + '</span>') +
    row('Status',        statusBadge(a.status)) +
    row('Priority',      urgentBadge) +
    '</div>' +
    (a.notes       ? '<div style="margin-top:.8rem;font-size:.8rem;color:var(--text2);margin-bottom:.25rem">Your Notes:</div><div class="detail-block">'          + a.notes       + '</div>' : '') +
    (a.admin_notes ? '<div style="margin-top:.8rem;font-size:.8rem;color:var(--accent);margin-bottom:.25rem">Clinic Notes:</div><div class="detail-block clinic">' + a.admin_notes + '</div>' : ''),
    []
  );
}

async function cancelMyApt(id) {
  if (!confirm('Are you sure you want to cancel this appointment?')) return;
  var res = await API.cancelApt(id);
  if (!res.success) { toast(res.error || 'Cancel failed.', 'error'); return; }
  toast('Appointment cancelled.', 'success');
  renderStudentDash();
}

/* ============================================================
   PATIENT VISIT HISTORY
   ============================================================ */
async function renderHistory() {
  var q   = (document.getElementById('hSearch').value || '').toLowerCase();
  var f   = document.getElementById('hFilter').value || '';
  var res = await API.listApts();
  var el  = document.getElementById('histBody');

  if (!res.success) {
    el.innerHTML = '<div class="empty-state"><div class="ei"><i class="fa-solid fa-triangle-exclamation"></i></div><p>Error loading records.</p></div>';
    return;
  }

  var list = (res.data || []).filter(function (a) {
    var reason  = (a.reason || '').toLowerCase();
    var svcName = (a.service_name || '').toLowerCase();
    var okQ = !q || reason.includes(q) || svcName.includes(q) || a.apt_date.includes(q) || a.apt_code.toLowerCase().includes(q);
    var okF = !f || a.status === f;
    return okQ && okF;
  });

  if (!list.length) {
    el.innerHTML = '<div class="empty-state"><div class="ei"><i class="fa-solid fa-clipboard-list"></i></div><p>No records found.</p></div>';
    return;
  }

  el.innerHTML = list.map(function (a) {
    var canCancel = a.status === 'Pending';
    var svcLine = a.service_name
      ? ('<span style="font-size:.76rem;color:var(--accent);font-weight:600">' + svcIcon(a.service_icon) + a.service_name + '</span>')
      : '';
    return '<div class="hist-card">' +
      '<div class="hist-top">' +
        '<div>' +
          '<div class="hist-id">' + a.apt_code + ' · ' + fmtDT(a.created_at) + '</div>' +
          (svcLine ? ('<div style="margin:.18rem 0">' + svcLine + '</div>') : '') +
          '<div class="hist-reason">' + (a.reason || 'No reason specified') + '</div>' +
          '<div class="hist-meta">' +
            '<span><i class="fa-regular fa-calendar" style="margin-right:.3rem"></i>' + fmtDate(a.apt_date) + '</span>' +
            '<span><i class="fa-regular fa-clock"    style="margin-right:.3rem"></i>' + a.apt_time + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="hist-actions">' +
          statusBadge(a.status) +
          (a.priority === 'urgent' ? '<span class="badge badge-urgent"><i class="fa-solid fa-triangle-exclamation"></i> Urgent</span>' : '') +
          '<button class="btn btn-ghost btn-sm" onclick="viewMyApt(' + a.id + ')">Details</button>' +
          (canCancel ? '<button class="btn btn-danger btn-sm" onclick="cancelMyApt(' + a.id + ')">Cancel</button>' : '') +
        '</div>' +
      '</div>' +
      '</div>';
  }).join('');
}

/* ============================================================
   NOTIFICATIONS
   ============================================================ */
async function renderFullNotifs() {
  var res  = await API.notifications();
  var el   = document.getElementById('fullNotifList');
  var list = res.data || [];

  if (!list.length) {
    el.innerHTML = '<div class="empty-state"><div class="ei"><i class="fa-solid fa-bell-slash"></i></div><p>No notifications yet.</p></div>';
    return;
  }

  el.innerHTML = list.map(function (n) {
    return '<div class="notif-item">' +
      '<div class="notif-dot"></div>' +
      '<div><div class="notif-text">' + n.message + '</div>' +
      '<div class="notif-time">' + fmtDT(n.created_at) + '</div></div></div>';
  }).join('');

  API.markRead();
}

/* ============================================================
   APPOINTMENT BOOKING WIZARD
   ============================================================ */

// Cache loaded services to avoid re-fetching
var _services = null;

async function getServices() {
  if (_services) return _services;
  var res = await API.services();
  _services = res.success ? (res.data || []) : [];
  return _services;
}

function resetBooking() {
  bookData     = {};
  selectedSlot = null;
  _services    = null; // clear cache so fresh load on next visit

  var r = document.getElementById('bService');
  if (r) r.value = '';
  var rr = document.getElementById('bReason');
  if (rr) rr.value = '';
  document.getElementById('bNotes').value = '';

  var bd = document.getElementById('bDate');
  if (bd) { bd.value = ''; bd.min = todayStr(); }

  document.getElementById('slotGrid').innerHTML =
    '<div style="color:var(--text3);font-size:.84rem;grid-column:1/-1;padding:.5rem 0">Select a date first…</div>';

  showStep(1);

  // Load services into the dropdown
  loadServiceOptions();
}

async function loadServiceOptions() {
  var sel = document.getElementById('bService');
  if (!sel) return;

  sel.innerHTML = '<option value="">— Select a service (optional) —</option>';
  var list = await getServices();
  list.forEach(function (s) {
    var opt = document.createElement('option');
    opt.value       = s.id;
    opt.textContent = s.name;
    sel.appendChild(opt);
  });
}

function showStep(n) {
  [1, 2, 3].forEach(function (i) {
    document.getElementById('bStep' + i).classList.toggle('hidden', i !== n);
    var st = document.getElementById('st' + i);
    if (st) {
      st.classList.toggle('active', i === n);
      st.classList.toggle('done',   i < n);
    }
  });
}

async function bookNext(from) {
  if (from === 1) {
    var serviceId   = parseInt(document.getElementById('bService').value) || 0;
    var serviceName = '';
    if (serviceId) {
      var list = await getServices();
      var svc  = list.find(function (s) { return s.id == serviceId; });
      serviceName = svc ? svc.name : '';
    }
    bookData.service_id   = serviceId || null;
    bookData.service_name = serviceName;
    bookData.reason       = (document.getElementById('bReason').value || '').trim();
    bookData.notes        = document.getElementById('bNotes').value;
    showStep(2);

  } else if (from === 2) {
    var d = document.getElementById('bDate').value;
    if (!d)            { toast('Please select a date.', 'error'); return; }
    if (!selectedSlot) { toast('Please select a time slot.', 'error'); return; }
    bookData.date = d;
    bookData.time = selectedSlot;

    document.getElementById('cService').textContent = bookData.service_name || 'Not selected';
    document.getElementById('cReason').textContent  = bookData.reason       || 'Not specified';
    document.getElementById('cDate').textContent    = fmtDate(d);
    document.getElementById('cTime').textContent    = selectedSlot;
    showStep(3);
  }
}

function bookBack(from) { showStep(from - 1); }

async function loadSlots() {
  var date = document.getElementById('bDate').value;
  if (!date) return;

  document.getElementById('slotGrid').innerHTML =
    '<div style="color:var(--text3);font-size:.84rem;grid-column:1/-1">Loading…</div>';
  selectedSlot = null;

  var res = await API.slots(date);
  if (!res.success) {
    document.getElementById('slotGrid').innerHTML =
      '<div style="color:var(--danger);font-size:.84rem;grid-column:1/-1">Error loading slots. Make sure the server is running.</div>';
    return;
  }

  if (!res.slots || !res.slots.length) {
    document.getElementById('slotGrid').innerHTML =
      '<div style="color:var(--text3);font-size:.84rem;grid-column:1/-1">No time slots configured. Contact the clinic admin.</div>';
    return;
  }

  document.getElementById('slotGrid').innerHTML = res.slots.map(function (s) {
    var full = !s.available;
    return '<div class="slot' + (full ? ' taken' : '') + '" ' +
      (full ? '' : 'onclick="pickSlot(this,\'' + s.time + '\')"') + '>' +
      s.time + (full ? '<br><small>Full</small>' : '') +
      '</div>';
  }).join('');
}

function pickSlot(el, slot) {
  document.querySelectorAll('.slot').forEach(function (s) { s.classList.remove('selected'); });
  el.classList.add('selected');
  selectedSlot = slot;
}

async function submitBooking() {
  var res = await API.book({
    service_id: bookData.service_id || null,
    reason:     bookData.reason || '',
    notes:      bookData.notes  || '',
    date:       bookData.date,
    time:       bookData.time
  });

  if (!res.success) { toast(res.error || 'Booking failed.', 'error'); return; }
  toast('Appointment scheduled! Reference: ' + res.apt_code, 'success');
  nav('s-dashboard');
}
