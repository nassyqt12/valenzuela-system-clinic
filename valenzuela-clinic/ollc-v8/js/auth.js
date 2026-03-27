/* ============================================================
   js/auth.js  —  Login, Register, Logout
   Valenzuela Clinic System
   ============================================================ */

'use strict';

function showAuth(mode) {
  authMode = mode;
  showSection('auth');

  document.getElementById('authRoleTitle').textContent =
    mode === 'admin' ? 'Clinic Staff Portal' : 'Patient Login';
  document.getElementById('authRoleSub').textContent =
    mode === 'admin'
      ? 'Manage appointments and patient records.'
      : 'Access your appointments and health records.';

  document.getElementById('authTabs').style.display = 'flex';
  document.getElementById('loginId').value = '';
  document.getElementById('loginPw').value = '';

  switchTab('login');
}

function goLanding() { showSection('landing'); }

function switchTab(tab) {
  var tabs = document.querySelectorAll('.auth-tab');
  if (tabs[0]) tabs[0].classList.toggle('active', tab === 'login');
  if (tabs[1]) tabs[1].classList.toggle('active', tab === 'register');

  document.getElementById('formLogin').classList.toggle('hidden',    tab !== 'login');
  document.getElementById('formReg').classList.toggle('hidden',
    !(tab === 'register' && authMode === 'patient'));
  document.getElementById('formStaffReg').classList.toggle('hidden',
    !(tab === 'register' && authMode === 'admin'));
}

async function doLogin() {
  var id = document.getElementById('loginId').value.trim();
  var pw = document.getElementById('loginPw').value;
  if (!id || !pw) { toast('Please fill in all fields.', 'error'); return; }

  var btn = document.getElementById('loginBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Signing in…'; }

  var res = await API.login(id, pw, authMode);

  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In'; }

  if (!res.success) { toast(res.error || 'Login failed.', 'error'); return; }

  currentUser = res.user;
  showSection('app');
  initApp();
  toast('Welcome back, ' + currentUser.first_name + '!', 'success');
}

async function doRegister() {
  var data = {
    firstName: document.getElementById('rFirst').value.trim(),
    lastName:  document.getElementById('rLast').value.trim(),
    email:     document.getElementById('rEmail').value.trim(),
    phone:     document.getElementById('rPhone').value.trim(),
    password:  document.getElementById('rPw').value,
  };

  if (!data.firstName || !data.lastName || !data.email || !data.password) {
    toast('Please fill in all required fields.', 'error'); return;
  }

  var btn = document.getElementById('regBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Creating account…'; }

  var res = await API.register(data);

  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Create Account'; }

  if (!res.success) { toast(res.error || 'Registration failed.', 'error'); return; }

  toast('Account created! You can now sign in.', 'success');
  document.getElementById('loginId').value = data.email;
  document.getElementById('loginPw').value = '';
  switchTab('login');
}

async function doStaffRegister() {
  var firstName = document.getElementById('srFirst').value.trim();
  var lastName  = document.getElementById('srLast').value.trim();
  var email     = document.getElementById('srEmail').value.trim();
  var password  = document.getElementById('srPw').value;
  var position  = document.getElementById('srPosition').value.trim() || 'Clinic Nurse';
  var licenseNo = document.getElementById('srLicense').value.trim();

  if (!firstName || !lastName || !email || !password) {
    toast('Please fill in all required fields.', 'error'); return;
  }

  var btn = document.getElementById('staffRegBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Creating account…'; }

  var res = await API.post('api/auth.php?action=register_staff_self', {
    firstName, lastName, email, password, position, licenseNo
  });

  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-user-nurse"></i> Create Staff Account'; }

  if (!res.success) { toast(res.error || 'Registration failed.', 'error'); return; }

  toast('Staff account created! You can now sign in.', 'success');
  document.getElementById('loginId').value = email;
  document.getElementById('loginPw').value = '';
  switchTab('login');
}

async function doLogout() {
  await API.logout();
  currentUser = null;
  Object.keys(charts).forEach(function (k) { destroyChart(k); });
  showSection('landing');
  toast('Signed out successfully.');
}

document.addEventListener('DOMContentLoaded', function () {
  ['loginId', 'loginPw'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') doLogin();
    });
  });
});
