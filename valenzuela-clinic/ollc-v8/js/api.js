/* ============================================================
   js/api.js  —  All API Calls
   Valenzuela Clinic System
   ============================================================ */

'use strict';

var API = {

  async _req(url, opts) {
    try {
      var res  = await fetch(url, Object.assign({ credentials: 'include' }, opts));
      var data = await res.json();
      return data;
    } catch (err) {
      console.error('API error:', url, err);
      return { success: false, error: 'Network error — check your server connection.' };
    }
  },

  async get(path)        { return this._req(path, { method: 'GET' }); },
  async post(path, body) {
    return this._req(path, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body)
    });
  },

  /* ── Auth ──────────────────────────────────────────────── */
  login:    (identifier, password, mode) =>
    API.post('api/auth.php?action=login', { identifier, password, mode }),
  register: (data) =>
    API.post('api/auth.php?action=register', data),
  logout:   () =>
    API.post('api/auth.php?action=logout', {}),
  me:       () =>
    API.get('api/auth.php?action=me'),

  /* ── Services ──────────────────────────────────────────── */
  services: () =>
    API.get('api/appointments.php?action=services'),
  adminServicesList: () =>
    API.get('api/admin.php?action=services_list'),
  addService: (data) =>
    API.post('api/admin.php?action=add_service', data),
  toggleService: (id, active) =>
    API.post('api/admin.php?action=toggle_service', { id, active }),
  serviceStats: () =>
    API.get('api/admin.php?action=service_stats'),

  /* ── Appointments ──────────────────────────────────────── */
  listApts: (params) => {
    var q = new URLSearchParams(params || {}).toString();
    return API.get('api/appointments.php?action=list&' + q);
  },
  book:      (data) => API.post('api/appointments.php?action=book', data),
  updateApt: (data) => API.post('api/appointments.php?action=update', data),
  cancelApt: (id)   => API.post('api/appointments.php?action=cancel', { id }),
  slots:     (date) => API.get('api/appointments.php?action=slots&date=' + encodeURIComponent(date)),
  aptStats:  ()     => API.get('api/appointments.php?action=stats'),

  /* ── Admin ─────────────────────────────────────────────── */
  students:       (q)          => API.get('api/admin.php?action=students&q=' + encodeURIComponent(q || '')),
  studentHistory: (uid)        => API.get('api/admin.php?action=student_history&uid=' + uid),
  staffList:      ()           => API.get('api/admin.php?action=staff_list'),
  addStaff:       (data)       => API.post('api/admin.php?action=add_staff', data),
  toggleStaff:    (uid, active)=> API.post('api/admin.php?action=toggle_staff', { uid, active }),
  deleteStaff:    (uid)        => API.post('api/admin.php?action=delete_staff', { uid }),
  adminSlots:     ()           => API.get('api/admin.php?action=slots'),
  addSlot:        (time, max)  => API.post('api/admin.php?action=add_slot', { time, max }),
  editSlot:       (id, max)    => API.post('api/admin.php?action=edit_slot', { id, max }),
  deleteSlot:     (id)         => API.post('api/admin.php?action=delete_slot', { id }),
  audit:          ()           => API.get('api/admin.php?action=audit'),
  reportStats:    ()           => API.get('api/admin.php?action=report_stats'),
  weekData:       ()           => API.get('api/admin.php?action=week_data'),

  /* ── Notifications ─────────────────────────────────────── */
  notifications:  () => API.get('api/notifications.php?action=list'),
  notifCount:     () => API.get('api/notifications.php?action=unread_count'),
  markRead:       () => API.post('api/notifications.php?action=mark_read', {}),
};
