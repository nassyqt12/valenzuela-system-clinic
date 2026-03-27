<?php
// ============================================================
//  index.php  —  Valenzuela Clinic System
//  Appointment & Reservation System
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Valenzuela Clinic — Appointment & Reservation System</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/base.css" />
  <link rel="stylesheet" href="css/layout.css" />
  <link rel="stylesheet" href="css/components.css" />
  <link rel="stylesheet" href="css/pages.css" />
  <style>
    .ni i { font-size:.88rem; width:16px; text-align:center; }
    .stat-icon i { font-size:.88rem; }
    .card-title-icon i { font-size:.76rem; }
    @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(6px)} }
  </style>
</head>
<body>

<div class="toast-cont" id="toastCont"></div>


<!-- ============================================================
     LANDING PAGE
     ============================================================ -->
<div id="landing" class="hidden">
  <div class="land-hero">

    <!-- LEFT: Dark hero panel -->
    <div class="land-left">
      <div>
        <div class="land-badge-pill">
          <span class="dot"></span>
          Now Accepting Online Appointments
        </div>
        <div class="land-school">Valenzuela Clinic · Gen. T. De Leon, Valenzuela City</div>
        <div class="land-title">
          Your Health,<br>
          Our <span class="accent-word">Priority.</span>
        </div>
        <div class="land-desc">
          Book clinic appointments, track your visits, and access quality
          healthcare in Valenzuela — all in one simple system.
        </div>
        <div class="land-btns">
          <button class="btn-land student" onclick="showAuth('patient')">
            <i class="fa-solid fa-user"></i> Login Account
          </button>
          <button class="btn-land admin" onclick="showAuth('admin')">
            <i class="fa-solid fa-stethoscope"></i> Clinic Staff Login
          </button>
        </div>
      </div>
      <div class="land-status-strip">
        <div class="land-stat"><div class="land-stat-val">8</div><div class="land-stat-lbl">Services</div></div>
        <div class="land-stat"><div class="land-stat-val">Mon–Fri</div><div class="land-stat-lbl">Open Days</div></div>
        <div class="land-stat"><div class="land-stat-val">Walk-in</div><div class="land-stat-lbl">Also Accepted</div></div>
      </div>
    </div>

    <!-- RIGHT: Services panel -->
    <div class="land-right">
      <div class="services-header">
        <div class="services-eyebrow"><i class="fa-solid fa-grid-2" style="margin-right:.3rem"></i>Clinic Services</div>
        <div class="services-title">What We Offer</div>
        <div class="services-sub">Walk in or book an appointment for any of our services.</div>
      </div>
      <div class="services-grid">
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-heart-pulse"></i></div>
          <div><div class="service-name">General Check-Up</div><div class="service-desc">Basic health screening for adults & kids</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-baby"></i></div>
          <div><div class="service-name">Pediatric Consultation</div><div class="service-desc">Healthcare specifically for children</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-tooth"></i></div>
          <div><div class="service-name">Dental Check-Up</div><div class="service-desc">Cleaning, cavity check & dental advice</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-syringe"></i></div>
          <div><div class="service-name">Vaccination</div><div class="service-desc">Routine vaccines for adults & children</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-droplet"></i></div>
          <div><div class="service-name">BP & Sugar Monitoring</div><div class="service-desc">Quick in-clinic vital sign tests</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-user-doctor"></i></div>
          <div><div class="service-name">Specialist Consultation</div><div class="service-desc">Referral to specialists</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-video"></i></div>
          <div><div class="service-name">Teleconsultation</div><div class="service-desc">Video or chat for minor concerns</div></div>
        </div>
        <div class="service-item">
          <div class="service-icon"><i class="fa-solid fa-seedling"></i></div>
          <div><div class="service-name">Health & Wellness</div><div class="service-desc">Diet, exercise & lifestyle programs</div></div>
        </div>
      </div>
      <div class="land-links">
        <a class="land-link" href="https://www.facebook.com" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i> Facebook</a>
        <a class="land-link" href="mailto:clinic@valenzuela.gov.ph"><i class="fa-solid fa-envelope"></i> Email</a>
        <a class="land-link" href="tel:+63289220077"><i class="fa-solid fa-phone"></i> (02) 8922-0077</a>
      </div>
    </div>
  </div>

  <!-- Scroll hint -->
  <div style="background:var(--bg);text-align:center;padding:.9rem;">
    <a href="#meet-the-team" style="color:var(--text3);font-size:.72rem;text-decoration:none;display:inline-flex;flex-direction:column;align-items:center;gap:.25rem;"
       onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text3)'">
      <span style="text-transform:uppercase;letter-spacing:.12em;font-weight:700;">Meet the Team</span>
      <i class="fa-solid fa-chevron-down" style="animation:bounce 1.5s infinite;font-size:.72rem;"></i>
    </a>
  </div>

  <!-- Meet the Team -->
  <section class="team-section" id="meet-the-team">
    <div class="team-header">
      <div class="team-eyebrow"><i class="fa-solid fa-users" style="font-size:.6rem"></i> The Team</div>
      <div class="team-title">Built by Developers, For the Community</div>
      <div class="team-subtitle">Dedicated developers who designed and built this clinic system from scratch.</div>
    </div>
    <div class="team-grid">
      <div class="member-card">
        <div class="member-photo"><div class="member-photo-placeholder"><i class="fa-solid fa-user"></i></div></div>
        <div class="member-name">Your Name Here</div>
        <div class="member-role">Lead Developer</div>
        <div class="member-desc">Full system build — database, backend, and frontend UI.</div>
      </div>
      <div class="member-card">
        <div class="member-photo"><div class="member-photo-placeholder"><i class="fa-solid fa-user"></i></div></div>
        <div class="member-name">Your Name Here</div>
        <div class="member-role">UI / UX Designer</div>
        <div class="member-desc">Designed layout, colors, and user experience.</div>
      </div>
      <div class="member-card">
        <div class="member-photo"><div class="member-photo-placeholder"><i class="fa-solid fa-user"></i></div></div>
        <div class="member-name">Your Name Here</div>
        <div class="member-role">Researcher</div>
        <div class="member-desc">Research, requirements gathering, and documentation.</div>
      </div>
      <div class="member-card">
        <div class="member-photo"><div class="member-photo-placeholder"><i class="fa-solid fa-user"></i></div></div>
        <div class="member-name">Your Name Here</div>
        <div class="member-role">Tester / QA</div>
        <div class="member-desc">System testing, bug reports, and quality assurance.</div>
      </div>
    </div>
    <div class="team-footer">
      <p>Valenzuela Clinic — Appointment & Reservation System</p>
      <p>Gen. T. De Leon, Valenzuela City</p>
    </div>
  </section>
</div><!-- end #landing -->


<!-- ============================================================
     AUTH PAGE
     ============================================================ -->
<div id="auth" class="hidden">
  <div class="auth-wrap">
    <!-- Left panel -->
    <div class="auth-panel">
      <div class="auth-panel-logo">
        <div class="auth-logo-mark"><i class="fa-solid fa-house-medical"></i></div>
        <div>
          <div class="auth-panel-name">Valenzuela Clinic</div>
          <div class="auth-panel-sub">Appointment System</div>
        </div>
      </div>
      <div class="auth-panel-copy">
        <div class="auth-panel-tagline">Clinic care,<br><span class="hi">simplified.</span></div>
        <div class="auth-panel-desc">Book appointments, track visits, and receive health updates from one platform.</div>
        <div class="auth-panel-services">
          <div class="auth-service-dot">General Check-Up & Consultations</div>
          <div class="auth-service-dot">Dental & Vaccination Services</div>
          <div class="auth-service-dot">BP & Sugar Monitoring</div>
          <div class="auth-service-dot">Teleconsultation Available</div>
          <div class="auth-service-dot">Health & Wellness Programs</div>
        </div>
      </div>
      <div style="font-size:.65rem;color:rgba(255,255,255,0.2);position:relative;z-index:1;">
        Gen. T. De Leon, Valenzuela City
      </div>
    </div>

    <!-- Form area -->
    <div class="auth-form-area">
      <button class="auth-back" onclick="goLanding()">
        <i class="fa-solid fa-arrow-left"></i> Back to Home
      </button>
      <div class="auth-role-title" id="authRoleTitle">Patient Login</div>
      <div class="auth-role-sub"   id="authRoleSub">Access your appointments and health records.</div>

      <div class="auth-tabs" id="authTabs">
        <button class="auth-tab active" onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab"        onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN -->
      <div id="formLogin">
        <div class="form-group">
          <label>Email or Patient No.</label>
          <input class="form-control" id="loginId" placeholder="patient@email.com or P-2024-001" />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input class="form-control" type="password" id="loginPw" placeholder="Enter your password" />
        </div>
        <button class="btn btn-primary full" id="loginBtn" onclick="doLogin()" style="margin-top:.3rem">
          <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
        </button>
      </div>

      <!-- PATIENT REGISTER -->
      <div id="formReg" class="hidden">
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input class="form-control" id="rFirst" placeholder="Juan" />
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input class="form-control" id="rLast" placeholder="Dela Cruz" />
          </div>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input class="form-control" type="email" id="rEmail" placeholder="juan@email.com" />
        </div>
        <div class="form-group">
          <label>Phone <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>
          <input class="form-control" id="rPhone" placeholder="09171234567" />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input class="form-control" type="password" id="rPw" placeholder="Create a password" />
        </div>
        <button class="btn btn-primary full" id="regBtn" onclick="doRegister()">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
      </div>

      <!-- STAFF REGISTER -->
      <div id="formStaffReg" class="hidden">
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input class="form-control" id="srFirst" placeholder="Maria" />
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input class="form-control" id="srLast" placeholder="Santos" />
          </div>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input class="form-control" type="email" id="srEmail" placeholder="nurse@clinic.com" />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input class="form-control" type="password" id="srPw" placeholder="Minimum 6 characters" />
        </div>
        <div class="form-group">
          <label>Position</label>
          <input class="form-control" id="srPosition" value="Clinic Nurse" />
        </div>
        <div class="form-group">
          <label>License No. <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>
          <input class="form-control" id="srLicense" placeholder="RN-00000" />
        </div>
        <button class="btn btn-primary full" id="staffRegBtn" onclick="doStaffRegister()">
          <i class="fa-solid fa-user-nurse"></i> Create Staff Account
        </button>
        <p class="staff-reg-note">New accounts are registered as Clinic Staff.<br>An admin can update your role later if needed.</p>
      </div>
    </div>
  </div>
</div><!-- end #auth -->


<!-- ============================================================
     MAIN APP
     ============================================================ -->
<div id="app" class="hidden">

  <!-- Mobile topbar -->
  <div class="topbar">
    <button class="hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    <div class="topbar-brand">
      <div style="width:24px;height:24px;background:var(--text);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa-solid fa-house-medical" style="font-size:.65rem;color:var(--surface)"></i>
      </div>
      <span class="topbar-title">Valenzuela Clinic</span>
    </div>
    <span class="topbar-user" id="topbarUser"></span>
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode" id="topbarThemeBtn">
      <i class="fa-solid fa-moon" id="topbarThemeIcon"></i>
    </button>
  </div>

  <div class="overlay-bg" id="overlayBg" onclick="closeSidebar()"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark"><i class="fa-solid fa-house-medical"></i></div>
      <div>
        <div class="logo-text">Valenzuela Clinic</div>
        <div class="logo-sub">Appointment System</div>
      </div>
    </div>
    <nav class="sidebar-nav" id="sidebarNav"></nav>
    <div class="sidebar-footer">
      <div class="user-avatar-wrap" id="sidebarAvatar">
        <i class="fa-solid fa-user" style="font-size:.72rem"></i>
      </div>
      <div class="user-info">
        <div class="user-name" id="sidebarName"></div>
        <div class="user-role" id="sidebarRole"></div>
      </div>
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode" id="sidebarThemeBtn">
        <i class="fa-solid fa-moon" id="sidebarThemeIcon"></i>
      </button>
      <button class="logout-btn" onclick="doLogout()" title="Sign out">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
      </button>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content" id="mainContent">


    <!-- ══ PATIENT: DASHBOARD ══════════════════════════════════ -->
    <div class="page" id="pg-s-dashboard">
      <div class="page-header">
        <div>
          <div class="page-title">Good day, <span id="sWelcome" style="color:var(--accent)"></span></div>
          <div class="page-sub" id="sToday"></div>
        </div>
        <button class="btn btn-accent" onclick="nav('s-book')">
          <i class="fa-solid fa-calendar-plus"></i> Book Appointment
        </button>
      </div>

      <div class="stat-grid">
        <div class="stat-card blue">
          <div class="stat-top"><div class="stat-label">Total Appointments</div><div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div></div>
          <div class="stat-val" id="ss-total">0</div>
          <div class="stat-sub">All time</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-top"><div class="stat-label">Pending</div><div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div></div>
          <div class="stat-val" id="ss-pending">0</div>
          <div class="stat-sub">Awaiting confirmation</div>
        </div>
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-label">Confirmed</div><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div></div>
          <div class="stat-val" id="ss-approved">0</div>
          <div class="stat-sub">Approved appointments</div>
        </div>
        <div class="stat-card red">
          <div class="stat-top"><div class="stat-label">Cancelled</div><div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div></div>
          <div class="stat-val" id="ss-cancelled">0</div>
          <div class="stat-sub">Cancelled appointments</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>Recent Appointments</div>
          <button class="btn btn-ghost btn-sm" onclick="nav('s-history')">View all →</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="sRecentTbody"></tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-bell"></i></div>Notifications</div>
        </div>
        <div class="card-body">
          <div class="notif-list" id="sNotifList"></div>
        </div>
      </div>
    </div>


    <!-- ══ PATIENT: BOOK APPOINTMENT ══════════════════════════ -->
    <div class="page" id="pg-s-book">
      <div class="page-header">
        <div>
          <div class="page-title">Book an Appointment</div>
          <div class="page-sub">Walk-ins are also welcome — appointments help us prepare for your visit</div>
        </div>
      </div>
      <div class="card" style="max-width:560px">
        <div class="card-body">
          <div class="steps">
            <div class="step-tab active" id="st1">1 · Service</div>
            <div class="step-tab"        id="st2">2 · Schedule</div>
            <div class="step-tab"        id="st3">3 · Confirm</div>
          </div>

          <!-- Step 1: Service & Reason -->
          <div id="bStep1">
            <div class="form-group">
              <label>Select Service</label>
              <select class="form-control" id="bService">
                <option value="">Loading services…</option>
              </select>
            </div>
            <div class="form-group">
              <label>Reason for Visit <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>
              <input class="form-control" id="bReason" placeholder="e.g. annual check-up, flu shot, tooth pain…" />
              <div style="font-size:.72rem;color:var(--text3);margin-top:.3rem;">
                <i class="fa-solid fa-circle-info" style="margin-right:.2rem"></i>
                Briefly describe your concern. You may leave this blank.
              </div>
            </div>
            <div class="form-group">
              <label>Additional Notes <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>
              <textarea class="form-control textarea-field" id="bNotes" placeholder="Any other details for the clinic staff…"></textarea>
            </div>
            <button class="btn btn-primary" onclick="bookNext(1)">
              Next: Pick Schedule <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>

          <!-- Step 2: Date & Time -->
          <div id="bStep2" class="hidden">
            <div class="form-group">
              <label>Select Date</label>
              <input class="form-control" type="date" id="bDate" onchange="loadSlots()" />
            </div>
            <div class="form-group">
              <label>Available Time Slots</label>
              <div class="slot-grid" id="slotGrid">
                <div style="color:var(--text3);font-size:.84rem;grid-column:1/-1;padding:.5rem 0">Select a date first…</div>
              </div>
            </div>
            <div style="display:flex;gap:.65rem;margin-top:1rem;flex-wrap:wrap;">
              <button class="btn btn-ghost"  onclick="bookBack(2)"><i class="fa-solid fa-arrow-left"></i> Back</button>
              <button class="btn btn-primary" onclick="bookNext(2)">Next: Confirm <i class="fa-solid fa-arrow-right"></i></button>
            </div>
          </div>

          <!-- Step 3: Confirm -->
          <div id="bStep3" class="hidden">
            <div class="booking-summary">
              <div style="font-weight:800;margin-bottom:.7rem;font-size:.88rem;display:flex;align-items:center;gap:.45rem">
                <i class="fa-solid fa-clipboard-check" style="color:var(--accent)"></i> Appointment Summary
              </div>
              <div class="summary-row"><span class="label">Service:</span>  <strong id="cService"></strong></div>
              <div class="summary-row"><span class="label">Reason:</span>   <strong id="cReason"></strong></div>
              <div class="summary-row"><span class="label">Date:</span>     <strong id="cDate"></strong></div>
              <div class="summary-row"><span class="label">Time:</span>     <strong id="cTime" style="font-family:var(--font-mono)"></strong></div>
              <div class="summary-row"><span class="label">Status:</span>   <span class="badge badge-pending">Pending Confirmation</span></div>
            </div>
            <div style="display:flex;gap:.65rem;flex-wrap:wrap;">
              <button class="btn btn-ghost"  onclick="bookBack(3)"><i class="fa-solid fa-arrow-left"></i> Back</button>
              <button class="btn btn-accent" onclick="submitBooking()">
                <i class="fa-solid fa-check"></i> Confirm Appointment
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- ══ PATIENT: APPOINTMENT HISTORY ═══════════════════════ -->
    <div class="page" id="pg-s-history">
      <div class="page-header">
        <div>
          <div class="page-title">My Appointments</div>
          <div class="page-sub">Your complete appointment and visit history</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="toolbar">
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" placeholder="Search service, reason, date…" id="hSearch" oninput="renderHistory()" />
            </div>
            <select class="filter-select" id="hFilter" onchange="renderHistory()">
              <option value="">All Status</option>
              <option>Pending</option><option>Approved</option>
              <option>Cancelled</option><option>Rescheduled</option>
            </select>
          </div>
        </div>
        <div class="card-body" id="histBody"></div>
      </div>
    </div>


    <!-- ══ PATIENT: NOTIFICATIONS ══════════════════════════════ -->
    <div class="page" id="pg-s-notifs">
      <div class="page-header">
        <div><div class="page-title">Notifications</div><div class="page-sub">Updates from the clinic</div></div>
      </div>
      <div class="card">
        <div class="card-body"><div class="notif-list" id="fullNotifList"></div></div>
      </div>
    </div>


    <!-- ══ ADMIN: DASHBOARD ════════════════════════════════════ -->
    <div class="page" id="pg-a-dashboard">
      <div class="page-header">
        <div><div class="page-title">Dashboard</div><div class="page-sub" id="aDashSub"></div></div>
      </div>

      <div class="stat-grid">
        <div class="stat-card blue">
          <div class="stat-top"><div class="stat-label">Visits Today</div><div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div></div>
          <div class="stat-val" id="as-today">0</div>
          <div class="stat-sub">Scheduled for today</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-top"><div class="stat-label">Pending</div><div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div></div>
          <div class="stat-val" id="as-pending">0</div>
          <div class="stat-sub">Awaiting action</div>
        </div>
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-label">Confirmed</div><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div></div>
          <div class="stat-val" id="as-approved">0</div>
          <div class="stat-sub">Appointments confirmed</div>
        </div>
        <div class="stat-card red">
          <div class="stat-top"><div class="stat-label">Urgent</div><div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div></div>
          <div class="stat-val" id="as-urgent">0</div>
          <div class="stat-sub">Flagged as urgent</div>
        </div>
      </div>

      <div class="chart-grid">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-chart-column"></i></div>Appointments This Week</div>
          </div>
          <div class="card-body"><div class="chart-wrap"><canvas id="chartWeek"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-chart-pie"></i></div>Status Breakdown</div>
          </div>
          <div class="card-body"><div class="chart-wrap"><canvas id="chartStatus"></canvas></div></div>
        </div>
      </div>

      <!-- Service Stats + Today's Schedule side by side -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;margin-bottom:1.2rem">
        <div class="card" style="margin-bottom:0">
          <div class="card-header">
            <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-grid-2"></i></div>Visits by Service</div>
          </div>
          <div class="card-body"><div id="serviceStatsList"></div></div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-header">
            <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-calendar-day"></i></div>Today's Schedule</div>
          </div>
          <div class="card-body"><div class="timeline" id="todayTimeline"></div></div>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: APPOINTMENTS ════════════════════════════════ -->
    <div class="page" id="pg-a-appointments">
      <div class="page-header">
        <div>
          <div class="page-title">Appointments</div>
          <div class="page-sub">Manage all clinic appointment requests</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="toolbar">
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" placeholder="Search patient, service, reference…" id="aSearch" oninput="renderAdminApts()" />
            </div>
            <select class="filter-select" id="aStatusF" onchange="renderAdminApts()">
              <option value="">All Status</option>
              <option>Pending</option><option>Approved</option>
              <option>Cancelled</option><option>Rescheduled</option>
            </select>
            <select class="filter-select" id="aDateF" onchange="renderAdminApts()">
              <option value="">All Dates</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
            </select>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Ref. No.</th><th>Patient</th><th>Date</th><th>Time</th><th>Service</th><th>Priority</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="adminAptTbody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: PATIENT RECORDS ══════════════════════════════ -->
    <div class="page" id="pg-a-students">
      <div class="page-header">
        <div><div class="page-title">Patient Records</div><div class="page-sub">Registered patients and visit history</div></div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search patient name, no., email…" id="stuSearch" oninput="renderStudents()" />
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Patient No.</th><th>Name</th><th>Email</th><th>Total Visits</th><th>Action</th></tr>
            </thead>
            <tbody id="stuTbody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: CLINIC STAFF ══════════════════════════════════ -->
    <div class="page" id="pg-a-staff">
      <div class="page-header">
        <div><div class="page-title">Clinic Staff</div><div class="page-sub">Manage clinic nurses and administrator accounts</div></div>
        <button class="btn btn-accent" onclick="openAddStaff()"><i class="fa-solid fa-user-plus"></i> Add Staff</button>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Position</th><th>License No.</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="staffTbody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: SERVICES ══════════════════════════════════════ -->
    <div class="page" id="pg-a-services">
      <div class="page-header">
        <div><div class="page-title">Clinic Services</div><div class="page-sub">Manage available services shown during appointment booking</div></div>
        <button class="btn btn-accent" onclick="openAddService()"><i class="fa-solid fa-plus"></i> Add Service</button>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Service</th><th>Icon Class</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="servicesTbody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: TIME SLOTS ════════════════════════════════════ -->
    <div class="page" id="pg-a-slots">
      <div class="page-header">
        <div><div class="page-title">Appointment Time Slots</div><div class="page-sub">Configure available appointment times</div></div>
        <button class="btn btn-accent" onclick="openAddSlot()"><i class="fa-solid fa-plus"></i> Add Slot</button>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Time</th><th>Max Bookings / Day</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="slotsTbody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ══ ADMIN: REPORTS ══════════════════════════════════════ -->
    <div class="page" id="pg-a-reports">
      <div class="page-header">
        <div><div class="page-title">Reports & Analytics</div><div class="page-sub">Appointment statistics and health trend monitoring</div></div>
        <button class="btn btn-ghost btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
      </div>

      <div class="report-grid">
        <div class="report-box"><div class="rval" id="rTotal">0</div><div class="rlbl">Total Appointments</div></div>
        <div class="report-box"><div class="rval" id="rApproved">0</div><div class="rlbl">Confirmed</div></div>
        <div class="report-box"><div class="rval" id="rCancelled">0</div><div class="rlbl">Cancelled</div></div>
      </div>

      <div class="chart-grid">
        <div class="card">
          <div class="card-header"><div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-chart-bar"></i></div>Appointments by Service</div></div>
          <div class="card-body"><div class="chart-wrap"><canvas id="chartReason"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-chart-line"></i></div>Monthly Trend</div></div>
          <div class="card-body"><div class="chart-wrap"><canvas id="chartTrend"></canvas></div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><div class="card-title-icon"><i class="fa-solid fa-rectangle-list"></i></div>Audit Trail</div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Timestamp</th><th>Action</th><th>Performed By</th><th>Details</th></tr></thead>
            <tbody id="auditTbody"></tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div><!-- end #app -->


<!-- MODAL -->
<div class="modal-overlay hidden" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="mTitle">Details</span>
      <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body"   id="mBody"></div>
    <div class="modal-footer" id="mFooter"></div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/helpers.js"></script>
<script src="js/auth.js"></script>
<script src="js/app.js"></script>
<script src="js/student.js"></script>
<script src="js/admin.js"></script>

</body>
</html>
