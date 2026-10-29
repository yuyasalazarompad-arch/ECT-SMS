<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
if (($_SESSION['role_name'] ?? '') !== 'Student') {
    header('Location: ../auth/login.php');
    exit;
}

// Load the latest school branding saved by Admin > School Settings.
$school = [
    'school_name' => 'Fullbright College',
    'school_code' => 'FBC',
    'school_tagline' => 'School Management System',
    'logo_filename' => '',
    'banner_filename' => '',
];
$schoolResult = $conn->query("SELECT school_name, school_code, school_tagline, logo_filename, banner_filename FROM school_settings ORDER BY setting_id ASC LIMIT 1");
if ($schoolResult && $schoolResult->num_rows > 0) {
    $school = array_merge($school, $schoolResult->fetch_assoc());
}
$schoolName = trim((string)($school['school_name'] ?? 'Fullbright College')) ?: 'Fullbright College';
$schoolCode = trim((string)($school['school_code'] ?? 'FBC')) ?: 'FBC';
$schoolTagline = trim((string)($school['school_tagline'] ?? 'School Management System')) ?: 'School Management System';
// Relative URLs work in both the local /SMS folder and the deployed project folder.
$schoolLogoUrl = !empty($school['logo_filename']) ? '../assets/uploads/school/' . rawurlencode(basename((string)$school['logo_filename'])) : '';
$schoolBannerUrl = !empty($school['banner_filename']) ? '../assets/uploads/school/' . rawurlencode(basename((string)$school['banner_filename'])) : '';

$fullName = trim((string)($_SESSION['full_name'] ?? 'Student'));
$username = trim((string)($_SESSION['username'] ?? ''));
$firstName = preg_split('/\s+/', $fullName)[0] ?: 'Student';
$initials = '';
foreach (array_slice(preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY), 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
if ($initials === '') $initials = 'ST';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | <?= h($schoolName) ?></title>
<style>
:root{--navy:#071a3d;--navy2:#0d2a5c;--blue:#1769d1;--blue-soft:#eaf3ff;--bg:#f4f7fb;--text:#172033;--muted:#718096;--border:#e3e9f2;--sidebar:250px;--sidebar-small:78px;--white:#fff}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}a{text-decoration:none;color:inherit}button{font:inherit}.sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar);background:var(--navy);color:#fff;display:flex;flex-direction:column;z-index:20;transition:width .22s ease,transform .22s ease;box-shadow:4px 0 20px rgba(7,26,61,.08)}
.brand{display:flex;align-items:center;gap:11px;padding:23px 18px 21px;border-bottom:1px solid rgba(255,255,255,.12);min-height:91px}.brand-mark{width:42px;height:42px;flex-shrink:0;border-radius:12px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:800;font-size:13px;letter-spacing:-.5px;overflow:hidden}.brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px}.brand-copy{min-width:0}.brand-copy strong{display:block;font-size:14px;line-height:1.35}.brand-copy span{display:block;color:#b9cce5;font-size:11px;margin-top:4px}.nav-scroll{overflow-y:auto;flex:1;padding:20px 12px}.nav-label{font-size:10px;letter-spacing:1.5px;font-weight:700;color:#9fb6d5;padding:0 12px;margin:5px 0 10px}.nav-link{min-height:44px;display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:9px;color:#dce8f8;font-size:13px;margin-bottom:5px;transition:background .18s,color .18s}.nav-link:hover{background:rgba(255,255,255,.09);color:#fff}.nav-link.active{background:var(--blue);color:#fff;font-weight:700}.nav-icon{width:20px;flex-shrink:0;text-align:center;font-size:17px;line-height:1}.nav-text{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nav-link.disabled{opacity:.55;cursor:default}.nav-link.disabled:hover{background:transparent}.soon{margin-left:auto;font-size:9px;color:#c4d4e9;border:1px solid rgba(255,255,255,.2);border-radius:4px;padding:3px 5px;white-space:nowrap}.sidebar-bottom{padding:12px;border-top:1px solid rgba(255,255,255,.12)}.collapse-btn{width:100%;min-height:40px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:9px;cursor:pointer;font-size:12px}.collapse-btn:hover{background:rgba(255,255,255,.13)}.student-mini{display:flex;align-items:center;gap:10px;padding:12px 4px 8px;min-width:0}.avatar{width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#dceaff;color:var(--navy);font-weight:800;font-size:12px;flex-shrink:0;overflow:hidden}.mini-copy{min-width:0}.mini-copy strong{display:block;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.mini-copy span{display:block;color:#a9bdd9;font-size:10px;margin-top:4px}.logout{margin-top:3px;color:#ffdada}.logout:hover{background:rgba(214,69,69,.18)}
body.collapsed .sidebar{width:var(--sidebar-small)}body.collapsed .brand{justify-content:center;padding-inline:10px}body.collapsed .brand-copy,body.collapsed .nav-label,body.collapsed .nav-text,body.collapsed .soon,body.collapsed .collapse-text,body.collapsed .mini-copy{display:none}body.collapsed .nav-link{justify-content:center;padding-inline:0}body.collapsed .student-mini{justify-content:center}body.collapsed .collapse-btn{width:42px;margin:auto}
.main{margin-left:var(--sidebar);min-height:100vh;padding:28px 32px 20px;transition:margin-left .22s ease}body.collapsed .main{margin-left:var(--sidebar-small)}.mobile-top{display:none}.topbar{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:25px}.eyebrow{font-size:11px;letter-spacing:1.2px;color:var(--blue);font-weight:700;text-transform:uppercase;margin-bottom:7px}.page-title{font-size:26px;letter-spacing:-.5px;color:var(--navy);margin-bottom:6px}.page-subtitle{font-size:13px;color:var(--muted)}.top-profile{display:flex;align-items:center;gap:10px;padding:9px 13px;background:#fff;border:1px solid var(--border);border-radius:12px;max-width:260px}.top-profile .avatar{width:34px;height:34px}.top-profile-copy{min-width:0}.top-profile-copy strong{font-size:12px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.top-profile-copy span{font-size:10px;color:var(--muted);display:block;margin-top:3px}.welcome{position:relative;isolation:isolate;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:28px 30px;border-radius:16px;background:linear-gradient(115deg,#0b2450,#174b88);color:#fff;margin-bottom:25px;min-height:190px}.welcome-banner{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;z-index:-2}.welcome-shade{position:absolute;inset:0;background:linear-gradient(105deg,rgba(7,26,61,.91),rgba(23,75,136,.68));z-index:-1}/* High-visibility Fullbright navy/blue/gold ribbon. The inline SVG below draws the curves reliably. */
.welcome:before,.welcome:after{content:none !important;display:none !important}
.welcome-ribbon{position:absolute;right:0;bottom:0;width:58%;height:100%;z-index:1;pointer-events:none;overflow:hidden}
.welcome-ribbon svg{display:block;width:100%;height:100%}
.welcome-copy,.welcome-badge{position:relative;z-index:2}
@media(max-width:760px){.welcome-ribbon{width:75%;height:88%;opacity:.95}.welcome-copy{max-width:100%}}.welcome-copy{position:relative;z-index:1;max-width:650px}.welcome-kicker{font-size:11px;letter-spacing:1.1px;text-transform:uppercase;color:#c8ddfa;margin-bottom:11px}.welcome h2{font-size:24px;line-height:1.3;margin-bottom:10px}.welcome p{font-size:13px;line-height:1.8;color:#e1ecfb;max-width:610px}.welcome-badge{position:relative;z-index:1;flex-shrink:0;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.08);padding:12px 15px;border-radius:11px;font-size:11px;color:#e5efff;white-space:nowrap}.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.section-head h2{font-size:16px;color:var(--navy)}.section-head span{font-size:11px;color:var(--muted)}.overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:25px}.stat-card{background:#fff;border:1px solid var(--border);border-radius:13px;padding:19px;min-width:0}.stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:17px}.stat-label{font-size:12px;color:var(--muted)}.stat-icon{width:35px;height:35px;border-radius:10px;display:grid;place-items:center;background:var(--blue-soft);color:var(--blue);font-size:17px}.stat-value{font-size:17px;font-weight:700;color:var(--navy);margin-bottom:6px}.stat-note{font-size:11px;color:var(--muted);line-height:1.5}.lower-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(250px,.7fr);gap:16px}.panel{background:#fff;border:1px solid var(--border);border-radius:13px;padding:22px;min-width:0}.panel h3{font-size:14px;color:var(--navy);margin-bottom:8px}.panel-intro{font-size:12px;color:var(--muted);line-height:1.7}.empty-state{margin-top:18px;padding:18px;border:1px dashed #cbd7e8;background:#f9fbfe;border-radius:10px;display:flex;gap:12px;align-items:flex-start}.empty-icon{width:36px;height:36px;flex-shrink:0;border-radius:10px;display:grid;place-items:center;background:var(--blue-soft);color:var(--blue);font-size:17px}.empty-state strong{display:block;font-size:12px;color:var(--text);margin:2px 0 5px}.empty-state p{font-size:11px;color:var(--muted);line-height:1.6}.info-list{margin-top:13px}.info-row{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid #edf1f6;font-size:12px}.info-row:last-child{border-bottom:0}.info-row span{color:var(--muted)}.info-row strong{color:var(--text);text-align:right;overflow-wrap:anywhere}.footer{text-align:center;font-size:10px;color:#8a98ac;padding:24px 0 5px}
@media(max-width:1050px){.main{padding:24px 22px}.lower-grid{grid-template-columns:1fr}.welcome-badge{display:none}}
@media(max-width:760px){.sidebar{width:250px;transform:translateX(-100%);box-shadow:none}.sidebar.mobile-open{transform:translateX(0);box-shadow:8px 0 28px rgba(7,26,61,.25)}body.collapsed .sidebar{width:250px}body.collapsed .brand{justify-content:flex-start;padding:23px 18px 21px}body.collapsed .brand-copy,body.collapsed .nav-label,body.collapsed .nav-text,body.collapsed .soon,body.collapsed .collapse-text,body.collapsed .mini-copy{display:initial}body.collapsed .nav-label{display:block}body.collapsed .nav-link{justify-content:flex-start;padding:11px 12px}body.collapsed .student-mini{justify-content:flex-start}body .main,body.collapsed .main{margin-left:0;padding:18px 16px}.mobile-top{display:flex;align-items:center;gap:12px;margin-bottom:22px}.mobile-menu{width:42px;height:42px;border:1px solid var(--border);border-radius:10px;background:#fff;color:var(--navy);font-size:20px;cursor:pointer}.mobile-brand{font-size:13px;color:var(--navy);font-weight:700}.topbar{align-items:flex-start;flex-direction:column;margin-bottom:20px}.top-profile{max-width:100%;width:100%}.page-title{font-size:23px}.welcome{padding:23px 21px}.welcome h2{font-size:21px}.overview{grid-template-columns:1fr;gap:11px}.stat-card{padding:16px}.stat-top{margin-bottom:11px}.lower-grid{grid-template-columns:1fr}.panel{padding:18px}}
@media(min-width:761px){.sidebar.mobile-open{transform:none}}
</style>
</head>
<body>
<aside class="sidebar" id="studentSidebar" aria-label="Student navigation">
  <div class="brand"><div class="brand-mark"><?php if ($schoolLogoUrl !== ''): ?><img src="<?= h($schoolLogoUrl) ?>" alt="<?= h($schoolName) ?> logo"><?php else: ?><?= h($schoolCode) ?><?php endif; ?></div><div class="brand-copy"><strong><?= h($schoolName) ?></strong><span><?= h($schoolTagline) ?></span></div></div>
  <nav class="nav-scroll">
    <div class="nav-label">STUDENT PORTAL</div>
    <a class="nav-link active" href="dashboard.php" aria-current="page"><span class="nav-icon">⌂</span><span class="nav-text">Dashboard</span></a>
    <a class="nav-link disabled" href="#" aria-disabled="true" title="My Profile is not available yet"><span class="nav-icon">♙</span><span class="nav-text">My Profile</span><span class="soon">Soon</span></a>
    <a class="nav-link disabled" href="#" aria-disabled="true" title="My Classes is not available yet"><span class="nav-icon">▤</span><span class="nav-text">My Classes</span><span class="soon">Soon</span></a>
    <a class="nav-link disabled" href="#" aria-disabled="true" title="Announcements is not available yet"><span class="nav-icon">◉</span><span class="nav-text">Announcements</span><span class="soon">Soon</span></a>
    <div class="nav-label" style="margin-top:25px">ACCOUNT</div>
    <a class="nav-link logout" href="../auth/logout.php"><span class="nav-icon">↪</span><span class="nav-text">Log Out</span></a>
  </nav>
  <div class="sidebar-bottom">
    <button type="button" class="collapse-btn" id="collapseButton" aria-label="Collapse or expand sidebar"><span id="collapseIcon">◀</span><span class="collapse-text" id="collapseText">Collapse sidebar</span></button>
    <div class="student-mini"><div class="avatar"><?= h($initials) ?></div><div class="mini-copy"><strong><?= h($fullName) ?></strong><span>Student account</span></div></div>
  </div>
</aside>
<main class="main">
  <div class="mobile-top"><button class="mobile-menu" type="button" id="mobileMenu" aria-label="Open navigation" aria-expanded="false">☰</button><div class="mobile-brand"><?= h($schoolCode) ?> <span style="font-weight:400;color:#718096">/ Student Portal</span></div></div>
  <header class="topbar"><div><div class="eyebrow">Student portal</div><h1 class="page-title">Student Dashboard</h1><p class="page-subtitle">Your school information, organized in one place.</p></div><div class="top-profile"><div class="avatar"><?= h($initials) ?></div><div class="top-profile-copy"><strong><?= h($fullName) ?></strong><span><?= $username !== '' ? '@' . h($username) : 'Student account' ?></span></div></div></header>
  <section class="welcome"><?php if ($schoolBannerUrl !== ''): ?><img class="welcome-banner" src="<?= h($schoolBannerUrl) ?>?v=<?= @filemtime(__DIR__ . '/../assets/uploads/school/' . basename((string)$school['banner_filename'])) ?: time() ?>" alt="" aria-hidden="true" onerror="this.remove();"><?php endif; ?><div class="welcome-shade" aria-hidden="true"></div>
  <div class="welcome-ribbon" aria-hidden="true">
    <svg viewBox="0 0 620 240" preserveAspectRatio="none" focusable="false" xmlns="http://www.w3.org/2000/svg">
      <path d="M150 240 C235 175 250 75 370 54 C470 36 525 70 620 5 L620 240 Z" fill="#12355B" fill-opacity="0.92"/>
      <path d="M245 240 C330 190 355 120 440 102 C515 86 560 110 620 72 L620 240 Z" fill="#1E5A96" fill-opacity="0.98"/>
      <path d="M390 240 C450 204 475 168 525 159 C563 152 592 164 620 146 L620 240 Z" fill="#D9A441" fill-opacity="0.98"/>
    </svg>
  </div>
  <div class="welcome-copy"><div class="welcome-kicker"><?= h($schoolName) ?> · <?= h($schoolTagline) ?></div><h2>Welcome back, <?= h($firstName) ?>!</h2><p>This is your student portal. As student features become available, you’ll be able to use this space to access your profile, class information, and school announcements.</p></div><div class="welcome-badge">✦ &nbsp; Student workspace</div>
</section>

<?php require __DIR__ . '/bible_verse.php'; ?>

<div class="section-head">
    <h2>My Overview</h2>
    <span>Quick access</span>
</div>
  <section class="overview" aria-label="Student overview">
    <article class="stat-card"><div class="stat-top"><span class="stat-label">My Classes</span><span class="stat-icon">▤</span></div><div class="stat-value">Not available yet</div><p class="stat-note">Class schedules and subjects will appear here once connected.</p></article>
    <article class="stat-card"><div class="stat-top"><span class="stat-label">Announcements</span><span class="stat-icon">◉</span></div><div class="stat-value">Not available yet</div><p class="stat-note">School notices will be shown here when the feature is ready.</p></article>
    <article class="stat-card"><div class="stat-top"><span class="stat-label">Student Profile</span><span class="stat-icon">♙</span></div><div class="stat-value">Account active</div><p class="stat-note">You are signed in to your student account.</p></article>
  </section>
  <section class="lower-grid">
    <article class="panel"><h3>School Updates</h3><p class="panel-intro">Important school notices and announcements will be available in this section.</p><div class="empty-state"><div class="empty-icon">◉</div><div><strong>Announcements are not connected yet</strong><p>This area is ready for school announcements. We’ll connect it after confirming the announcements table and fields in your database.</p></div></div></article>
    <article class="panel"><h3>My Account</h3><p class="panel-intro">Basic information from your current signed-in session.</p><div class="info-list"><div class="info-row"><span>Full name</span><strong><?= h($fullName) ?></strong></div><div class="info-row"><span>Username</span><strong><?= $username !== '' ? h($username) : 'Not set' ?></strong></div><div class="info-row"><span>Account role</span><strong>Student</strong></div></div></article>
  </section>
  <footer class="footer">&copy; <?= date('Y') ?> <?= h($schoolName) ?> · School Management System</footer>
</main>
<script>
(function(){
 const body=document.body, sidebar=document.getElementById('studentSidebar'), collapse=document.getElementById('collapseButton'), icon=document.getElementById('collapseIcon'), label=document.getElementById('collapseText'), mobile=document.getElementById('mobileMenu');
 function updateCollapse(){const isCollapsed=body.classList.contains('collapsed');icon.textContent=isCollapsed?'▶':'◀';label.textContent=isCollapsed?'Expand sidebar':'Collapse sidebar';collapse.setAttribute('aria-label',isCollapsed?'Expand sidebar':'Collapse sidebar');}
 collapse.addEventListener('click',function(){if(window.matchMedia('(max-width: 760px)').matches){sidebar.classList.remove('mobile-open');mobile.setAttribute('aria-expanded','false');return;}body.classList.toggle('collapsed');updateCollapse();});
 mobile.addEventListener('click',function(){const open=sidebar.classList.toggle('mobile-open');mobile.setAttribute('aria-expanded',String(open));mobile.setAttribute('aria-label',open?'Close navigation':'Open navigation');mobile.textContent=open?'×':'☰';});
 document.addEventListener('click',function(e){if(window.matchMedia('(max-width: 760px)').matches && sidebar.classList.contains('mobile-open') && !sidebar.contains(e.target) && !mobile.contains(e.target)){sidebar.classList.remove('mobile-open');mobile.setAttribute('aria-expanded','false');mobile.textContent='☰';}});
 window.addEventListener('resize',function(){if(!window.matchMedia('(max-width: 760px)').matches){sidebar.classList.remove('mobile-open');mobile.setAttribute('aria-expanded','false');mobile.textContent='☰';}});
 updateCollapse();
})();
</script>
</body>
</html>
