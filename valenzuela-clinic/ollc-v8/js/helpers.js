/* ============================================================
   js/helpers.js — Utility / Helper Functions
   OLLC School Clinic · Appointment System
   ============================================================ */

'use strict';

/* ── Date Helpers ───────────────────────────────────────────── */

function todayStr() {
  return new Date().toISOString().split('T')[0];
}

function weekStart() {
  var d = new Date();
  d.setDate(d.getDate() - d.getDay());
  return d.toISOString().split('T')[0];
}

function fmtDate(d) {
  if (!d) return '';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric'
  });
}

function fmtDT(d) {
  if (!d) return '';
  try {
    return new Date(d).toLocaleString('en-PH', {
      month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  } catch (e) { return d; }
}

/* ── Status Helpers ─────────────────────────────────────────── */

/* Returns an inline FA icon span for a given status */
function statusIcon(s) {
  var icons = {
    Pending:     '<i class="fa-solid fa-hourglass-half"  style="color:#a16207"></i>',
    Approved:    '<i class="fa-solid fa-circle-check"    style="color:#1a3a6b"></i>',
    Cancelled:   '<i class="fa-solid fa-circle-xmark"    style="color:#e63946"></i>',
    Rescheduled: '<i class="fa-solid fa-calendar-xmark"  style="color:#1e6fa8"></i>',
  };
  return icons[s] || '';
}

function statusBadge(s) {
  return '<span class="badge badge-' + s.toLowerCase() + '">' +
    statusIcon(s) + ' ' + s +
    '</span>';
}

/* ── Toast ──────────────────────────────────────────────────── */

function toast(msg, type) {
  var el = document.createElement('div');
  el.className = 'toast' + (type ? ' ' + type : '');
  el.textContent = msg;
  document.getElementById('toastCont').appendChild(el);
  setTimeout(function () { el.remove(); }, 3500);
}

/* ── Modal ──────────────────────────────────────────────────── */

function showModal(title, body, btns) {
  document.getElementById('mTitle').textContent = title;
  document.getElementById('mBody').innerHTML    = body;

  var footer = document.getElementById('mFooter');
  footer.innerHTML = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';

  (btns || []).forEach(function (b) {
    var btn = document.createElement('button');
    btn.className   = 'btn ' + b.cls;
    btn.textContent = b.lbl;
    btn.onclick     = b.fn;
    footer.appendChild(btn);
  });

  document.getElementById('modalOverlay').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.add('hidden');
}

/* ── Info Row Builder ───────────────────────────────────────── */

function row(label, val) {
  return '<div class="info-row">' +
    '<span class="info-label">' + label + '</span>' +
    '<span class="info-val">'   + val   + '</span>' +
    '</div>';
}

/* ── Chart Destroy Helper ───────────────────────────────────── */

function destroyChart(key) {
  if (charts[key]) {
    try { charts[key].destroy(); } catch (e) { /* ignore */ }
    delete charts[key];
  }
}
