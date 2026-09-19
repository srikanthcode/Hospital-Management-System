<?php
/**
 * Shared layout for dashboards with real-time support
 */
require_once __DIR__ . '/auth.php';
require_login();

$page_title = $page_title ?? 'Dashboard';
$active = $active ?? '';
$base = $base ?? '../';
$user_name = $_SESSION['name'] ?? 'User';
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($page_title); ?> - Lotus Women's Hospital</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
<style>
  body { background:#FFF5F8; }
  .app-shell { display:flex; min-height:100vh; }
  .sidebar {
    width:240px; background:#E91E63; color:#fff; padding:20px 0;
    position:sticky; top:0; height:100vh; overflow-y:auto;
  }
  .sidebar h4 { color:#fff; text-align:center; padding:0 15px 15px; border-bottom:1px solid rgba(255,255,255,.2); }
  .sidebar a {
    display:block; color:#fff; text-decoration:none; padding:10px 20px;
    font-weight:500; border-left:4px solid transparent;
  }
  .sidebar a:hover, .sidebar a.active { background:rgba(0,0,0,.15); border-left-color:#fff; }
  .sidebar .group-title { padding:15px 20px 5px; font-size:12px; text-transform:uppercase; opacity:.7; }
  .main { flex:1; padding:0; }
  .topbar {
    background:#fff; padding:12px 25px; box-shadow:0 2px 6px rgba(0,0,0,.05);
    display:flex; justify-content:space-between; align-items:center;
  }
  .topbar .user { font-weight:600; color:#E91E63; }
  .content { padding:25px; }
  .stat-card { background:#fff; border-radius:15px; padding:20px; box-shadow:0 6px 14px rgba(0,0,0,.06); transition: transform 0.2s; }
  .stat-card:hover { transform: translateY(-2px); }
  .stat-card h6 { color:#666; font-size:13px; margin-bottom:8px; }
  .stat-card h2 { color:#E91E63; margin:0; }
  @media (max-width:768px){
    .app-shell{ flex-direction:column; }
    .sidebar{ width:100%; height:auto; position:relative; }
  }

  /* Pulse animation for live updates */
  @keyframes pulse { 0%{transform:scale(1)} 50%{transform:scale(1.08)} 100%{transform:scale(1)} }
  .pulse { animation: pulse 0.6s ease-in-out; }

  /* Notification bell */
  .notif-wrapper { position:relative; cursor:pointer; }
  .notif-badge {
    position:absolute; top:-6px; right:-6px; background:#F44336; color:#fff;
    font-size:10px; font-weight:700; border-radius:50%; padding:2px 5px;
    min-width:18px; text-align:center; display:none;
  }
  .notif-dropdown {
    display:none; position:absolute; right:0; top:100%; margin-top:8px;
    width:340px; max-height:400px; overflow-y:auto;
    background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.15);
    z-index:9999;
  }
  .notif-dropdown.show { display:block; }
  .notif-header { padding:12px 15px; border-bottom:1px solid #eee; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
  .notif-item { cursor:pointer; transition:background 0.2s; }
  .notif-item:hover { background:#f8f9fa; }
  .notif-icon { font-size:8px; margin-top:4px; }

  /* Activity timeline */
  .activity-section { max-height:400px; overflow-y:auto; }
</style>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <h4>Lotus Hospital</h4>
    <?php if ($user_role === 'admin'): ?>
      <div class="group-title">Admin</div>
      <a href="<?php echo $base; ?>admin_dashboard.php" class="<?php echo $active==='admin_home'?'active':''; ?>">Dashboard</a>
      <a href="<?php echo $base; ?>admin/manage_patients.php" class="<?php echo $active==='patients'?'active':''; ?>">Patients</a>
      <a href="<?php echo $base; ?>admin/manage_appointments.php" class="<?php echo $active==='appointments'?'active':''; ?>">Appointments</a>
      <a href="<?php echo $base; ?>admin/manage_nurses.php" class="<?php echo $active==='nurses'?'active':''; ?>">Nurses</a>
      <a href="<?php echo $base; ?>admin/manage_beds.php" class="<?php echo $active==='beds'?'active':''; ?>">Beds &amp; Wards</a>
      <a href="<?php echo $base; ?>admin/manage_admissions.php" class="<?php echo $active==='admissions'?'active':''; ?>">Admissions</a>
      <a href="<?php echo $base; ?>admin/manage_emergency.php" class="<?php echo $active==='emergency'?'active':''; ?>">Emergency</a>
      <a href="<?php echo $base; ?>admin/manage_ambulance.php" class="<?php echo $active==='ambulance'?'active':''; ?>">Ambulance</a>
      <a href="<?php echo $base; ?>admin/manage_services.php" class="<?php echo $active==='services'?'active':''; ?>">Services</a>
      <div class="group-title">Reports</div>
      <a href="<?php echo $base; ?>reports/index.php" class="<?php echo $active==='reports'?'active':''; ?>">All Reports</a>
    <?php elseif ($user_role === 'doctor'): ?>
      <div class="group-title">Doctor</div>
      <a href="<?php echo $base; ?>doctor/dashboard.php" class="<?php echo $active==='doc_home'?'active':''; ?>">Dashboard</a>
      <a href="<?php echo $base; ?>doctor/patients.php" class="<?php echo $active==='doc_patients'?'active':''; ?>">My Patients</a>
      <a href="<?php echo $base; ?>doctor/appointments.php" class="<?php echo $active==='doc_appointments'?'active':''; ?>">Appointments</a>
      <a href="<?php echo $base; ?>doctor/medical_records.php" class="<?php echo $active==='doc_records'?'active':''; ?>">Medical Records</a>
      <a href="<?php echo $base; ?>doctor/followups.php" class="<?php echo $active==='doc_followups'?'active':''; ?>">Follow-ups</a>
    <?php elseif ($user_role === 'patient'): ?>
      <div class="group-title">Patient</div>
      <a href="<?php echo $base; ?>patient/dashboard.php" class="<?php echo $active==='pt_home'?'active':''; ?>">Dashboard</a>
      <a href="<?php echo $base; ?>patient/profile.php" class="<?php echo $active==='pt_profile'?'active':''; ?>">My Profile</a>
      <a href="<?php echo $base; ?>patient/book_appointment.php" class="<?php echo $active==='pt_book'?'active':''; ?>">Book Appointment</a>
      <a href="<?php echo $base; ?>patient/appointments.php" class="<?php echo $active==='pt_appts'?'active':''; ?>">My Appointments</a>
      <a href="<?php echo $base; ?>patient/medical_records.php" class="<?php echo $active==='pt_records'?'active':''; ?>">Medical Records</a>
      <a href="<?php echo $base; ?>patient/followups.php" class="<?php echo $active==='pt_followups'?'active':''; ?>">Follow-ups</a>
    <?php elseif ($user_role === 'nurse'): ?>
      <div class="group-title">Nurse</div>
      <a href="<?php echo $base; ?>nurse/dashboard.php" class="<?php echo $active==='nurse_home'?'active':''; ?>">Dashboard</a>
    <?php endif; ?>
    <div class="group-title">Account</div>
    <a href="<?php echo $base; ?>logout.php">Logout</a>
  </aside>

  <main class="main">
    <div class="topbar">
      <div><strong><?php echo e($page_title); ?></strong></div>
      <div class="d-flex align-items-center gap-3">
        <!-- Notification Bell -->
        <div class="notif-wrapper" onclick="Notifications.toggleDropdown()">
          <span style="font-size:20px; cursor:pointer">&#128276;</span>
          <span class="notif-badge" id="notifBadge">0</span>
          <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-header">
              <span>Notifications</span>
              <button class="btn btn-sm btn-link" onclick="event.stopPropagation(); Notifications.markAllRead()">Mark all read</button>
            </div>
            <div id="notifList">
              <div class="text-center text-muted p-3">Loading...</div>
            </div>
          </div>
        </div>
        <div class="user"><?php echo e($user_name); ?> (<?php echo e(ucfirst($user_role)); ?>)</div>
      </div>
    </div>
    <div class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show">
          <?php echo e($flash['msg']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
