<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>@yield('title', 'Admin Panel')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#0f172a">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    :root{
      --bg:  #f8fafc;
      --panel:  #ffffff;
      --text:  #0f172a;
      --muted: #475569;
      --border: #e2e8f0;
      --primary: #4f46e5;
      --primary-600: #4338ca;
      --overlay: rgba(15,23,42,0.45);
      --link: #0ea5e9;
      --link-hover: #0284c7;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #3b82f6;
    }
    @media (prefers-color-scheme: dark){
      :root{
        --bg: #0b1220;
        --panel: #0f172a;
        --text:  #e2e8f0;
        --muted: #94a3b8;
        --border: #1f2a44;
        --primary: #6366f1;
        --primary-600: #818cf8;
        --overlay:  rgba(2,6,23,0.65);
        --link: #38bdf8;
        --link-hover: #7dd3fc;
        --success: #34d399;
        --warning:  #fbbf24;
        --danger:  #f87171;
        --info: #60a5fa;
      }
    }
    *{box-sizing: border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Noto Sans, Ubuntu, Cantarell, Helvetica Neue, Arial, "Apple Color Emoji","Segoe UI Emoji";
      background: var(--bg);
      color:var(--text);
      line-height:1.5;
    }

    /* Layout */
    .app{
      min-height:100%;
      display:grid;
      grid-template-columns:  1fr;
    }

    /* Topbar */
    .topbar{
      position:sticky;
      top:0;
      z-index:50;
      background:var(--panel);
      border-bottom: 1px solid var(--border);
      height:64px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:0 20px;
      box-shadow:  0 1px 3px rgba(0,0,0,0.1);
    }
    .top-actions{
      display:flex; align-items:center; gap:16px;
    }
    .menu-btn{
      appearance:none;
      border:1px solid var(--border);
      background:transparent;
      color:inherit;
      border-radius:8px;
      height:40px;
      padding:0 12px;
      display:inline-flex;
      align-items:center;
      gap:8px;
      cursor:pointer;
      transition:  all 0.2s ease;
    }
    .menu-btn:hover{
      background:rgba(79,70,229,0.08);
      border-color:var(--primary);
    }
    .menu-icon{
      width:20px; height:20px; display:inline-block; position:relative;
    }
    .menu-icon::before,
    .menu-icon::after,
    .menu-icon span{
      content:""; position:absolute; left:0; right:0; height:2px; background:currentColor; border-radius:2px;
    }
    .menu-icon::before{ top: 4px; }
    .menu-icon span{ top: 9px; }
    .menu-icon::after{ top:14px; }

    .topbar-title{
      font-weight:700;
      font-size:18px;
      letter-spacing:-0.01em;
    }

    .topbar-user{
      display:flex; align-items:center; gap:8px; font-size:14px;
    }

    /* Sidebar */
    .sidebar{
      position:fixed;
      inset:64px auto 0 0;
      width:280px;
      background:var(--panel);
      border-right:1px solid var(--border);
      transform:translateX(-100%);
      transition:transform .22s ease;
      z-index:60;
      display:flex; flex-direction:column;
      box-shadow: 1px 0 3px rgba(0,0,0,0.1);
    }
    .sidebar.open{ transform:translateX(0); }
    .sidebar-header{
      padding:18px 16px;
      border-bottom:1px solid var(--border);
      font-weight:700;
      letter-spacing:-0.01em;
      font-size:16px;
    }
    .nav{
      padding:12px;
      display:flex; flex-direction:column; gap:8px;
      overflow: auto;
      flex: 1;
    }
    .nav a{
      display:flex; align-items:center; gap:12px;
      padding:12px 14px;
      border-radius: 10px;
      color:var(--text);
      text-decoration:none;
      border:1px solid transparent;
      transition:  all 0.2s ease;
      font-size:15px;
    }
    .nav a:hover{
      background:rgba(79,70,229,0.06);
      border-color:rgba(79,70,229,0.2);
      color:var(--primary);
    }
    .nav a.active{
      background:rgba(79,70,229,0.12);
      border-color:var(--primary);
      color:var(--primary-600);
      font-weight:600;
    }
    .nav i{
      width:20px;
      text-align:center;
      font-size:16px;
    }
    .nav .section-title{
      margin: 14px 14px 8px;
      font-size: 11px;
      color:var(--muted);
      text-transform:uppercase; letter-spacing:.08em;
      font-weight:700;
    }
    .sidebar-footer{
      margin-top:auto;
      padding:12px;
      border-top:1px solid var(--border);
    }
    .logout-btn{
      width:100%;
      border:1px solid var(--border);
      background:transparent;
      color:var(--danger);
      border-radius:10px;
      height:40px;
      cursor:pointer;
      transition: all 0.2s ease;
      font-weight:600;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
    }
    .logout-btn:hover{
      background:rgba(239,68,68,0.1);
      border-color:var(--danger);
    }

    /* Overlay */
    .overlay{
      position:fixed; inset:64px 0 0 0;
      background:var(--overlay);
      opacity:0; pointer-events:none;
      transition:opacity .18s ease;
      z-index:55;
    }
    .overlay.show{ opacity:1; pointer-events:auto; }

    /* Main content */
    .main{
      max-width: 100%;
      width: 100%;
      margin: 0;
      padding: 24px;
      transition: opacity 0.25s ease-in-out;
    }

    /* Alert Messages */
    .alert{
      display:flex;
      align-items:flex-start;
      gap:12px;
      padding:14px 16px;
      border-radius:10px;
      margin-bottom: 16px;
      border:1px solid;
      animation:  slideIn 0.3s ease;
    }
    @keyframes slideIn{
      from{ opacity:0; transform:translateY(-10px); }
      to{ opacity:1; transform:translateY(0); }
    }
    .alert-success{
      background:rgba(16,185,129,0.08);
      border-color:rgba(16,185,129,0.3);
      color:var(--success);
    }
    .alert-danger{
      background:rgba(239,68,68,0.08);
      border-color:rgba(239,68,68,0.3);
      color:var(--danger);
    }
    .alert i{
      font-size:18px;
      flex-shrink:0;
    }
    .alert-close{
      margin-left:auto;
      background:none;
      border:none;
      color:inherit;
      cursor:pointer;
      font-size:16px;
      opacity:0.6;
      transition:opacity 0.2s;
    }
    .alert-close:hover{
      opacity: 1;
    }

    /* Loading progress bar */
    .loading-bar {
      position: fixed;
      top: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--link));
      z-index: 99999;
      width: 0;
      transition: width 0.35s cubic-bezier(0.1, 0.8, 0.3, 1), opacity 0.3s ease;
      opacity: 0;
      pointer-events: none;
    }

    /* Desktop enhancements */
    @media (min-width:  1024px){
      .sidebar{
        position: fixed;
        top: 64px;
        left: 0;
        bottom: 0;
        width: 280px;
        height: calc(100vh - 64px);
        transform: none;
        box-shadow: 1px 0 5px rgba(0,0,0,0.04);
        z-index: 60;
      }
      .overlay{ display:none; }
      .layout{
        display: block;
        padding-left: 280px; /* Shift content area away from fixed sidebar */
        min-height: calc(100vh - 64px);
      }
      .main{
        padding: 32px;
      }
      .topbar .menu-btn{ display:none; }
    }

    /* Utilities */
    .card{
      background:var(--panel);
      border:1px solid var(--border);
      border-radius:12px;
      padding:20px;
      box-shadow:0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06);
      transition: all 0.2s ease;
    }
    .card:hover{
      box-shadow:0 2px 4px rgba(0,0,0,0.06), 0 12px 32px rgba(0,0,0,0.1);
    }
    .muted{ color:var(--muted); }
    .link{ color:var(--link); text-decoration:none; cursor:pointer; }
    .link:hover{ color:var(--link-hover); text-decoration:underline; }
  </style>
</head>
<body>
  <div class="loading-bar" id="ajaxLoadingBar"></div>
  <div class="app">
    <!-- Topbar -->
    <header class="topbar" role="banner">
      <div class="top-actions">
        <button class="menu-btn" id="menuToggle" aria-controls="adminSidebar" aria-expanded="false">
          <span class="menu-icon"><span></span></span>
          Menu
        </button>
        <span class="topbar-title">Admin Panel</span>
      </div>
      <div class="top-actions">
        <span class="topbar-user">
          <i class="fas fa-user-circle" style="font-size: 24px; color:var(--primary);"></i>
          {{ Auth::guard('admin')->user()->name }}
        </span>
      </div>
    </header>

    <!-- Body grid -->
    <div class="layout">
      <!-- Sidebar -->
      <aside id="adminSidebar" class="sidebar" role="navigation" aria-label="Sidebar">
        <div class="sidebar-header">
          <i class="fas fa-compass" style="margin-right:8px;"></i> Navigation
        </div>
        <nav class="nav">
          <a href="{{ route('admin.dashboard') }}" class="@if(Route::currentRouteName() == 'admin.dashboard') active @endif">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
          </a>
          <a href="{{ route('admin.users.index') }}" class="@if(Route::currentRouteName() == 'admin.users.index') active @endif">
            <i class="fas fa-users"></i>
            <span>Users</span>
          </a>
          <a href="{{ route('admin.businesses.index') }}" class="@if(request()->routeIs('admin.businesses.*')) active @endif">
            <i class="fas fa-building"></i>
            <span>Businesses</span>
          </a>
          <a href="{{ route('admin.packages.index') }}" class="@if(request()->routeIs('admin.packages.*')) active @endif">
            <i class="fas fa-box"></i>
            <span>Packages</span>
          </a>
          <a href="#">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
          </a>

          <div class="section-title">Management</div>
          <a href="#">
            <i class="fas fa-envelope"></i>
            <span>Email Tools</span>
          </a>
          <a href="#">
            <i class="fas fa-shield-alt"></i>
            <span>Security</span>
          </a>
          <a href="{{ route('admin.reports.revenue') }}" class="@if(request()->routeIs('admin.reports.*')) active @endif">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
          </a>
        </nav>
        <div class="sidebar-footer">
          <form method="POST" action="{{ route('admin.logout') }}" style="width:100%;">
            @csrf
            <button type="submit" class="logout-btn">
              <i class="fas fa-sign-out-alt"></i>
              Logout
            </button>
          </form>
        </div>
      </aside>

      <!-- Overlay for mobile/tablet -->
      <div class="overlay" id="sidebarOverlay"></div>

      <!-- Main Content -->
      <main class="main" role="main">
        @if(session('success'))
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div style="flex: 1;">
              <strong>Success! </strong> {{ session('success') }}
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none';">
              <i class="fas fa-times"></i>
            </button>
          </div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div style="flex:1;">
              <strong>Error!</strong> {{ session('error') }}
            </div>
            <button class="alert-close" onclick="this. parentElement.style.display='none';">
              <i class="fas fa-times"></i>
            </button>
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
      const loadingBar = document.getElementById('ajaxLoadingBar');

      function openSidebar(){
        sidebar.classList.add('open');
        overlay.classList. add('show');
        toggle.setAttribute('aria-expanded', 'true');
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

      toggle.addEventListener('click', toggleSidebar);
      overlay.addEventListener('click', closeSidebar);

      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
      });

      sidebar.addEventListener('click', (e) => {
        const target = e.target. closest('a,button');
        if (! target) return;
        if (window.innerWidth < 1024) closeSidebar();
      });

      function syncOnResize(){
        if (window.innerWidth >= 1024){
          sidebar.classList.add('open');
          overlay.classList.remove('show');
          document.body.style.overflow = '';
          toggle.setAttribute('aria-expanded', 'true');
        } else {
          sidebar.classList.remove('open');
          overlay.classList.remove('show');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }
      window. addEventListener('resize', syncOnResize);
      syncOnResize();

      // ==========================================
      // AJAX/PJAX NAVIGATION ENGINE
      // ==========================================
      function showProgress() {
        loadingBar.style.opacity = '1';
        loadingBar.style.width = '75%';
      }

      function hideProgress() {
        loadingBar.style.width = '100%';
        setTimeout(() => {
          loadingBar.style.opacity = '0';
          setTimeout(() => {
            loadingBar.style.width = '0';
          }, 300);
        }, 150);
      }

      function updateActiveSidebar(url) {
        const path = new URL(url).pathname;
        const navLinks = sidebar.querySelectorAll('.nav a');
        navLinks.forEach(link => {
          const hrefAttr = link.getAttribute('href');
          if (!hrefAttr || hrefAttr === '#' || hrefAttr.startsWith('javascript:')) {
            link.classList.remove('active');
            return;
          }
          const linkPath = new URL(link.href).pathname;
          // Check exact match or sub-paths (except base admin /admin or dashboard route)
          if (linkPath === path || (linkPath !== '/admin' && linkPath !== '/admin/dashboard' && path.startsWith(linkPath))) {
            link.classList.add('active');
          } else {
            link.classList.remove('active');
          }
        });
      }

      function loadAdminPage(url, push = true) {
        showProgress();
        const mainContent = document.querySelector('main.main');
        mainContent.style.opacity = '0.5';

        fetch(url)
          .then(res => {
            if (!res.ok) throw new Error('Response failed');
            return res.text();
          })
          .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update page title
            document.title = doc.title || 'Admin Panel';

            // 2. Replace content
            const incomingMain = doc.querySelector('main.main');
            if (incomingMain) {
              mainContent.innerHTML = incomingMain.innerHTML;
            }

            // 3. Push History State
            if (push) {
              history.pushState({ url: url }, document.title, url);
            }

            // 4. Sync Sidebar active link
            updateActiveSidebar(url);

            // 5. Extract and evaluate script tags in loaded content
            const scripts = doc.querySelectorAll('main.main script, script');
            scripts.forEach(script => {
              if (!script.src && script.innerHTML.trim() !== '') {
                // Skip duplicating global framework layout setup scripts
                if (script.innerHTML.includes('syncOnResize') || script.innerHTML.includes('loadAdminPage') || script.innerHTML.includes('ajaxLoadingBar')) {
                  return;
                }
                const newScript = document.createElement('script');
                newScript.appendChild(document.createTextNode(script.innerHTML));
                document.body.appendChild(newScript);
                newScript.parentNode.removeChild(newScript);
              }
            });

            mainContent.style.opacity = '1';
            hideProgress();
          })
          .catch(err => {
            console.error('AJAX Nav Error:', err);
            hideProgress();
            window.location.href = url; // Fallback redirect on error
          });
      }

      // Intercept click events
      document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href) return;

        // Skip empty hashes, action buttons, dynamic links
        if (href.startsWith('#') || href.startsWith('javascript:') || link.getAttribute('target') === '_blank') {
          return;
        }

        // Limit routes to internal admin panel, bypass logout actions
        if (href.includes('logout') || !href.startsWith(window.location.origin + '/admin')) {
          return;
        }

        e.preventDefault();
        loadAdminPage(link.href);
      });

      // Handle history popstate events (browser back/forward)
      window.addEventListener('popstate', (e) => {
        if (e.state && e.state.url) {
          loadAdminPage(e.state.url, false);
        } else {
          loadAdminPage(window.location.href, false);
        }
      });

      // Init current state
      history.replaceState({ url: window.location.href }, document.title, window.location.href);

    })();
  </script>
</body>
</html>