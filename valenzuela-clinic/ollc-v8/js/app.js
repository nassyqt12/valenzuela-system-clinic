/* ============================================================
   js/app.js — App Shell, Navigation, Dark Mode
   OLLC Clinic Appointment & Reservation System
   ============================================================ */

'use strict';

var currentUser  = null;
var authMode     = 'patient';
var bookData     = {};
var selectedSlot = null;
var charts       = {};

/* ── Boot ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async function () {
  // Apply saved theme before anything renders
  applyThemePref();

  var res = await API.me();
  if (res.success && res.user) {
    currentUser = res.user;
    showSection('app');
    initApp();
  } else {
    showSection('landing');
  }
});

/* ── Section Switcher ───────────────────────────────────────── */
function showSection(name) {
  ['landing', 'auth', 'app'].forEach(function (s) {
    var el = document.getElementById(s);
    if (el) el.classList.toggle('hidden', s !== name);
  });
}

/* ── App Init ───────────────────────────────────────────────── */
function initApp() {
  var isAdmin = currentUser.role === 'admin' || currentUser.role === 'staff';
  var name    = currentUser.first_name + ' ' + currentUser.last_name;

  document.getElementById('sidebarName').textContent = name;
  document.getElementById('topbarUser').textContent  = name;

  var roleEl = document.getElementById('sidebarRole');
  if (roleEl) roleEl.textContent = isAdmin ? 'Clinic Staff' : 'Patient';

  var navEl = document.getElementById('sidebarNav');

  if (isAdmin) {
    navEl.innerHTML =
      '<div class="nav-section">Overview</div>'                                    +
      navBtn('a-dashboard',    'fa-gauge-high',      'Dashboard')                  +
      '<div class="nav-section">Management</div>'                                  +
      navBtn('a-appointments', 'fa-clipboard-list',  'Appointments')               +
      navBtn('a-students',     'fa-users',           'Patient Records')            +
      navBtn('a-staff',        'fa-user-nurse',      'Clinic Staff')               +
      navBtn('a-services',     'fa-grid-2',          'Services')                   +
      navBtn('a-slots',        'fa-clock',           'Time Slots')                 +
      '<div class="nav-section">Analytics</div>'                                   +
      navBtn('a-reports',      'fa-chart-line',      'Reports & Analytics');

    nav('a-dashboard');
  } else {
    document.getElementById('sWelcome').textContent = currentUser.first_name;
    document.getElementById('sToday').textContent   = new Date().toLocaleDateString('en-PH', {
      weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
    });

    navEl.innerHTML =
      '<div class="nav-section">Menu</div>'                                        +
      navBtn('s-dashboard', 'fa-house',           'Dashboard')                     +
      navBtn('s-book',      'fa-calendar-plus',  'Book Appointment')          +
      navBtn('s-history',   'fa-clipboard-list', 'My Appointments')              +
      navBtn('s-notifs',    'fa-bell',            'Notifications');

    nav('s-dashboard');
  }

  var bd = document.getElementById('bDate');
  if (bd) bd.min = todayStr();
}

function navBtn(page, icon, label) {
  return '<button class="nav-item" id="nav-' + page + '" onclick="nav(\'' + page + '\')">' +
    '<span class="ni"><i class="fa-solid ' + icon + '"></i></span> ' + label +
    '</button>';
}

/* ── Page Navigation ────────────────────────────────────────── */
function nav(page) {
  document.querySelectorAll('.page').forEach(function (p)     { p.classList.remove('active'); });
  document.querySelectorAll('.nav-item').forEach(function (n) { n.classList.remove('active'); });

  var pg = document.getElementById('pg-' + page);
  if (pg) pg.classList.add('active');

  var ni = document.getElementById('nav-' + page);
  if (ni) ni.classList.add('active');

  var renders = {
    's-dashboard':    renderStudentDash,
    's-history':      renderHistory,
    's-book':         resetBooking,
    's-notifs':       renderFullNotifs,
    'a-dashboard':    function() { renderAdminDash(); setTimeout(renderServiceStats, 300); },
    'a-appointments': renderAdminApts,
    'a-students':     renderStudents,
    'a-staff':        renderStaff,
    'a-services':     renderServices,
    'a-slots':        renderSlots,
    'a-reports':      renderReports,
  };

  if (renders[page]) renders[page]();
  if (window.innerWidth < 960) closeSidebar();
}

/* ── Sidebar ────────────────────────────────────────────────── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlayBg').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlayBg').classList.remove('open');
}

/* ── Close modal on outside click ──────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  var overlay = document.getElementById('modalOverlay');
  if (overlay) overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });
});


/* ============================================================
   DARK MODE / THEME TOGGLE
   Saves preference to localStorage as 'ollc_theme'
   Values: 'dark' | 'light'  (default is 'light')
   ============================================================ */

function applyThemePref() {
  var saved = localStorage.getItem('ollc_theme');
  if (saved === 'dark') {
    document.body.classList.add('dark');
    updateThemeUI(true);
  }
}

function toggleTheme() {
  var isDark = document.body.classList.toggle('dark');
  localStorage.setItem('ollc_theme', isDark ? 'dark' : 'light');
  updateThemeUI(isDark);
}

function updateThemeUI(isDark) {
  var moonClass = 'fa-solid fa-moon';
  var sunClass  = 'fa-solid fa-sun';

  var ids = ['sidebarThemeIcon', 'topbarThemeIcon'];
  ids.forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.className = isDark ? sunClass : moonClass;
  });
}
