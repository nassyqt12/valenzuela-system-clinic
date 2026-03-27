# OLLC School Clinic — Appointment System (v3.0 — Fully Fixed)
## PHP + MySQL · Ready to Run in VSCode + XAMPP

---

## ⚡ Quick Start (3 Steps)

### Step 1 — Copy Files
Extract the ZIP and copy the `ollc-clinic` folder to your XAMPP web root:
```
C:\xampp\htdocs\ollc-clinic\
```

### Step 2 — Import Database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose `ollc_clinic.sql` → click **Go**

### Step 3 — Run Setup (upgrades passwords to bcrypt)
Open your browser:
```
http://localhost/ollc-clinic/setup.php
```
Click **Run Setup** → then **delete setup.php** for security.

That's it! Open → `http://localhost/ollc-clinic/`

---

## 🔑 Login Credentials

| Role | Email | Password |
|---|---|---|
| 🎓 Student | student@ollc.edu | password123 |
| 🎓 Student 2 | juan@ollc.edu | password123 |
| 🎓 Student 3 | ana@ollc.edu | password123 |
| 🏥 Admin / Chief Nurse | admin@ollc.edu | admin123 |
| 👤 Clinic Staff | staff@ollc.edu | admin123 |

---

## 🐛 Bugs Fixed (What Was Broken & Why)

### 🔴 Bug 1 — "Database connection failed: could not find driver"
**Root Cause:** The original `config.php` used `new PDO('mysql:...')` which requires
the `pdo_mysql` PHP extension. This extension is **disabled by default** in XAMPP and
many shared hosting environments. The error appeared immediately on any page load.

**Fix:** Completely replaced PDO with **`mysqli`** — the MySQL Improved Extension that
ships enabled by default in every XAMPP/WAMP installation. No php.ini changes needed.

---

### 🔴 Bug 2 — Login Does Not Redirect After Success
**Root Cause 1 (JavaScript):** `doLogin()` referenced `event.target` to get the button
element, but `event` is not passed as a parameter in the `onclick="doLogin()"` call.
This caused a silent `ReferenceError` or wrong element grab, and the code after it
(including `showSection('app')`) was never reached.

**Fix:** Added explicit `id="loginBtn"` and `id="regBtn"` on the HTML buttons, then
reference them via `document.getElementById('loginBtn')` instead.

**Root Cause 2 (HTTP):** API calls used `credentials: 'same-origin'`. In some browsers
and server configurations this does not forward the PHP session cookie, so each API
request starts a new session and the server always returns "Not authenticated."

**Fix:** Changed to `credentials: 'include'` in `api.js` — this always attaches
session cookies to every fetch request, exactly like a traditional form submission.

---

### 🔴 Bug 3 — Registered Accounts Cannot Log In
**Root Cause:** The original `verifyPassword()` function completely ignored the `$stored`
hash parameter. When `DEMO_MODE = true` it compared `$input` against the hardcoded
constant `DEMO_STUDENT_PASS` ('password123') regardless of what was stored in the
database. This meant:
- Demo accounts worked — but only if you used the exact hardcoded string
- Any newly registered user (with their own chosen password) could never log in
- The stored bcrypt hash was never checked

**Fix:** Removed `DEMO_MODE` entirely. `verifyPw()` now:
1. Checks `password_verify($input, $stored)` — works for bcrypt
2. Falls back to plain-text equality `$input === $stored` — works for seeded accounts

---

### 🔴 Bug 4 — config.php Circular Include (Fatal Error)
**Root Cause:** The uploaded `config.php` contained this line at the top:
```php
include_once `includes/config.php`;   // ← backtick syntax (shell exec) not quotes!
```
This caused a fatal parse error before any other code ran, crashing every API call.

**Fix:** Removed the circular self-include entirely.

---

### 🟡 Bug 5 — Admin Charts & Reports Show Wrong Data
**Root Cause (week chart):** `buildWeekChart()` in `admin.js` accessed `res.labels` and
`res.data` but the original API returned individual `{day_name, cnt}` objects without
those keys assembled.

**Fix:** API `week_data` now returns `{labels: [...], data: [...]}` directly. Chart
function uses them without transformation.

**Root Cause (reports):** `renderReports()` accessed `res.reasons` but the API key
was `res.by_reason`. Also `auditTbody` used `l.by_name` which is a SQL CONCAT alias —
this part was actually correct but the wrong SQL key `reasons` broke the reason chart.

**Fix:** API returns both `by_reason` and `reasons` (alias) for compatibility. JS
uses `res.by_reason || res.reasons` as fallback.

---

### 🟡 Bug 6 — Seed Passwords Don't Match Demo Hints
**Root Cause:** SQL seed file contained bcrypt hash of `"password"` but demo hints
showed `"password123"` and `"admin123"`. Nobody could log in with the displayed values.

**Fix:** SQL now seeds plain-text passwords. `setup.php` upgrades them to bcrypt at
first run. `verifyPw()` has a plain-text fallback for accounts not yet upgraded.

---

## 📁 Project Structure

```
ollc-clinic/
├── index.php               Main SPA entry point
├── setup.php               ⭐ One-click installer (DELETE after use)
├── ollc_clinic.sql         Database import file
├── img/
│   └── ollc_logo.png       Official OLLC seal logo
├── includes/
│   └── config.php          DB config, mysqli helpers, auth guards
├── api/
│   ├── auth.php            Login · Register · Logout · Session
│   ├── appointments.php    Book · List · Update · Cancel · Slots · Stats
│   ├── admin.php           Students · Staff CRUD · Slots · Reports · Audit
│   └── notifications.php  List · Mark read · Unread count
├── css/
│   ├── base.css            Variables, reset, typography, buttons, badges
│   ├── layout.css          Sidebar, topbar, main layout, responsive
│   ├── components.css      Cards, tables, modal, charts, notifications
│   └── pages.css           Landing, auth, booking wizard, reports
└── js/
    ├── api.js              Fetch wrapper (credentials:include fix)
    ├── helpers.js          Toast, modal, date utils, QR, chart helper
    ├── auth.js             Login / Register / Logout (button ID fix)
    ├── app.js              Session restore, routing, navigation, sidebar
    ├── student.js          Student dashboard, booking wizard, history
    └── admin.js            Admin dashboard, appointments, staff, reports
```

---

## 🔧 Manual Configuration

If you prefer not to use `setup.php`, edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ollc_clinic');
define('DB_USER', 'root');    // your MySQL username
define('DB_PASS', '');        // your MySQL password (blank for XAMPP default)
define('DB_PORT', 3306);
```

---

## 🖥️ VSCode Tips

1. Install the **PHP Intelephense** extension for IntelliSense
2. Install **Live Server** for front-end preview (note: PHP requires XAMPP running)
3. Set your workspace folder to `C:\xampp\htdocs\ollc-clinic`
4. Use the integrated terminal for Git or file operations
5. Recommended: **PHP Debug** extension + Xdebug for step debugging

---

## ✅ Features

### Student Portal
- Register & login with Student ID or email
- 3-step appointment booking wizard with live slot availability
- View appointment history with search & status filters
- Cancel pending appointments
- Receive notifications for approval / reschedule / cancellation
- View QR code for approved appointments

### Clinic Staff & Admin Portal
- Dashboard with today's schedule, stats, and charts
- Approve, cancel, or reschedule appointments with notes
- Set appointment priority (Normal / Urgent)
- View all student records and appointment histories
- **Manage Clinic Staff** — add / activate / deactivate / delete
- Manage time slots (add, edit capacity, remove)
- Reports: by-reason chart, monthly trend, total/approved/cancelled
- Full audit trail of all admin actions

---

## ❓ Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| Blank page / 500 error | PHP syntax error | Check Apache error log in XAMPP |
| "Could not find driver" | mysqli not enabled | Open `php.ini`, uncomment `extension=mysqli`, restart Apache |
| "Can't connect to MySQL" | MySQL not running | Start MySQL in XAMPP Control Panel |
| "Unknown database" | DB not imported | Import `ollc_clinic.sql` via phpMyAdmin |
| Login says success but stays on auth page | JS error | Open DevTools (F12) → Console tab |
| Session lost after login | Wrong server path | Access via `http://localhost/...`, not file:// |
| Charts don't load | JS error or CORS | Ensure you're using http://localhost, not file:// |
