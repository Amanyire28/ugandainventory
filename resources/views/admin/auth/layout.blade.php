<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#0f172a">
  <style>
    :root{
      --bg: #f8fafc;
      --panel: #ffffff;
      --text: #0f172a;
      --muted: #475569;
      --border: #e2e8f0;
      --primary: #4f46e5;
      --primary-600: #4338ca;
      --overlay: rgba(15,23,42,0.45);
      --link: #0ea5e9;
      --link-hover: #0284c7;
    }
    @media (prefers-color-scheme: dark){
      :root{
        --bg: #0b1220;
        --panel: #0f172a;
        --text: #e2e8f0;
        --muted: #94a3b8;
        --border: #1f2a44;
        --primary: #6366f1;
        --primary-600: #818cf8;
        --overlay: rgba(2,6,23,0.65);
        --link: #38bdf8;
        --link-hover: #7dd3fc;
      }
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Noto Sans, Ubuntu, Cantarell, Helvetica Neue, Arial, "Apple Color Emoji","Segoe UI Emoji";
      background:var(--bg);
      color:var(--text);
      line-height:1.5;
    }

    /* Layout */
    .app{
      min-height:100%;
      display:grid;
      grid-template-columns: 1fr;
    }

    /* Topbar */
    .topbar{
      position:sticky;
      top:0;
      z-index:50;
      background:var(--panel);
      border-bottom:1px solid var(--border);
      height:56px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:0 12px;
    }
    .top-actions{
      display:flex; align-items:center; gap:8px;
    }
    .menu-btn{
      appearance:none;
      border:1px solid var(--border);
      background:transparent;
      color:inherit;
      border-radius:8px;
      height:32px;
      padding:0 10px;
      display:inline-flex;
      align-items:center;
      gap:8px;
      cursor:pointer;
    }
    .menu-icon{
      width:18px; height:18px; display:inline-block; position:relative;
    }
    .menu-icon::before,
    .menu-icon::after,
    .menu-icon span{
      content:""; position:absolute; left:0; right:0; height:2px; background:currentColor; border-radius:2px;
    }
    .menu-icon::before{ top:3px; }
    .menu-icon span{ top:8px; }
    .menu-icon::after{ top:13px; }

    /* Sidebar */
    .sidebar{
      position:fixed;
      inset:56px auto 0 0; /* below topbar, left aligned */
      width:270px;
      background:var(--panel);
      border-right:1px solid var(--border);
      transform:translateX(-100%);
      transition:transform .22s ease;
      z-index:60;
      display:flex; flex-direction:column;
    }
    .sidebar.open{ transform:translateX(0); }
    .sidebar-header{
      padding:14px 12px;
      border-bottom:1px solid var(--border);
      font-weight:700;
      letter-spacing:-0.01em;
    }
    .nav{
      padding:10px;
      display:flex; flex-direction:column; gap:6px;
      overflow:auto;
    }
    .nav a{
      display:flex; align-items:center; gap:10px;
      padding:10px 10px;
      border-radius:10px;
      color:var(--text);
      text-decoration:none;
      border:1px solid transparent;
    }
    .nav a:hover{
      background:rgba(2,6,23,0.03);
      border-color:var(--border);
    }
    .nav a.active{
      background:rgba(79,70,229,0.08);
      border-color:rgba(79,70,229,0.30);
      color:var(--primary-600);
      font-weight:600;
    }
    .nav .section-title{
      margin:12px 10px 6px;
      font-size:12px;
      color:var(--muted);
      text-transform:uppercase; letter-spacing:.06em;
    }
    .sidebar-footer{
      margin-top:auto;
      padding:10px;
      border-top:1px solid var(--border);
    }
    .logout-btn{
      width:100%; border:1px solid var(--border); background:transparent; color:inherit;
      border-radius:10px; height:36px; cursor:pointer;
    }

    /* Overlay */
    .overlay{
      position:fixed; inset:56px 0 0 0;
      background:var(--overlay);
      opacity:0; pointer-events:none;
      transition:opacity .18s ease;
      z-index:55;
    }
    .overlay.show{ opacity:1; pointer-events:auto; }

    /* Main content */
    .main{
      max-width:1200px;
      margin:0 auto;
      padding:16px;
    }

    /* Desktop enhancements */
    @media (min-width: 1024px){
      .sidebar{
        position:sticky;
        top:56px; inset:auto; height:calc(100vh - 56px);
        transform:none;
      }
      .overlay{ display:none; }
      .layout{
        display:grid; grid-template-columns:270px 1fr;
        align-items:start;
      }
      .main{ padding:20px; }
      .topbar .menu-btn{ display:none; }
    }

    /* Utilities */
    .card{
      background:var(--panel); border:1px solid var(--border); border-radius:12px;
      padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
    }
    .muted{ color:var(--muted); }
    .link{ color:var(--link); text-decoration:none; }
    .link:hover{ color:var(--link-hover); }
  </style>
</head>
<body>
  <div class="app">
    <!-- Topbar -->
    <header class="topbar" role="banner">
      <div class="top-actions">
        <button class="menu-btn" id="menuToggle" aria-controls="adminSidebar" aria-expanded="false">
          <span class="menu-icon"><span></span></span>
          Menu
        </button>
      </div>
      <div style="font-weight:600">Admin Panel</div>
      <div class="top-actions">
        <a href="#" class="link">Profile</a>
      </div>
    </header>

    <!-- Body grid -->
    <div class="layout">
      <!-- Sidebar -->
      <aside id="adminSidebar" class="sidebar" role="navigation" aria-label="Sidebar">
        <div class="sidebar-header">Navigation</div>
        <nav class="nav">
          <a href="#" class="active">
            <span aria-hidden="true">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="#">
            <span aria-hidden="true">👥</span>
            <span>Users</span>
          </a>
          <a href="#">
            <span aria-hidden="true">🧾</span>
            <span>Plans</span>
          </a>
          <a href="#">
            <span aria-hidden="true">🔧</span>
            <span>Settings</span>
          </a>

          <div class="section-title">Management</div>
          <a href="#">
            <span aria-hidden="true">✉️</span>
            <span>Email Tools</span>
          </a>
          <a href="#">
            <span aria-hidden="true">🛡️</span>
            <span>Security</span>
          </a>
          <a href="#">
            <span aria-hidden="true">📈</span>
            <span>Reports</span>
          </a>
        </nav>
        <div class="sidebar-footer">
          <button class="logout-btn">Logout</button>
        </div>
      </aside>

      <!-- Overlay for mobile/tablet -->
      <div class="overlay" id="sidebarOverlay"></div>

      <!-- Main Content -->
      <main class="main" role="main">
        @if(session('success'))
          <div class="card" style="margin-bottom:12px;">
            <strong>Success:</strong> {{ session('success') }}
          </div>
        @endif
        @if($errors->any())
          <div class="card" style="margin-bottom:12px;">
            <strong>Error:</strong> {{ $errors->first() }}
          </div>
        @endif

        <!-- Page Content -->
        @yield('content')
      </main>
    </div>
  </div>

  <script>
    (function(){
      const sidebar = document.getElementById('adminSidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const toggle = document.getElementById('menuToggle');

      function openSidebar(){
        sidebar.classList.add('open');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
        // prevent background scroll on small screens
        if (window.innerWidth < 1024) document.body.style.overflow = 'hidden';
      }
      function closeSidebar(){
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
      function toggleSidebar(){
        if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
      }

      // Toggle button
      toggle.addEventListener('click', toggleSidebar);

      // Close when clicking overlay
      overlay.addEventListener('click', closeSidebar);

      // Close on ESC
      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
      });

      // Auto-close on small screens after navigation
      sidebar.addEventListener('click', (e) => {
        const target = e.target.closest('a,button');
        if (!target) return;
        // Only auto-close for links/buttons and for small screens
        if (window.innerWidth < 1024) closeSidebar();
      });

      // Ensure sidebar is visible on desktop and closed on load for mobile
      function syncOnResize(){
        if (window.innerWidth >= 1024){
          // desktop: keep sidebar open, remove overlay
          sidebar.classList.add('open');
          overlay.classList.remove('show');
          document.body.style.overflow = '';
          toggle.setAttribute('aria-expanded', 'true');
        } else {
          // mobile/tablet: closed by default
          sidebar.classList.remove('open');
          overlay.classList.remove('show');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }
      window.addEventListener('resize', syncOnResize);
      syncOnResize();
    })();
  </script>
</body>
</html>