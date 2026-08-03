<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Setup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#0f172a">
  <style>
    :root{
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #475569;
      --border: #e2e8f0;
      --primary: #4f46e5;
      --primary-600: #4338ca;
      --success-bg: #ecfdf5;
      --success-text: #065f46;
      --error-bg: #fef2f2;
      --error-text: #991b1b;
    }
    @media (prefers-color-scheme: dark) {
      :root{
        --bg: #0b1220;
        --card: #0f172a;
        --text: #e2e8f0;
        --muted: #94a3b8;
        --border: #1f2a44;
        --primary: #6366f1;
        --primary-600: #818cf8;
        --success-bg: #052e21;
        --success-text: #a7f3d0;
        --error-bg: #3b0a0a;
        --error-text: #fecaca;
      }
    }
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Noto Sans, Ubuntu, Cantarell, Helvetica Neue, Arial, "Apple Color Emoji","Segoe UI Emoji";
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    .wrap {
      min-height: 100%;
      display: grid;
      place-items: center;
      padding: 20px;
    }
    .card {
      width: 100%;
      max-width: 520px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      box-shadow:
        0 1px 2px rgba(0,0,0,0.04),
        0 8px 24px rgba(0,0,0,0.06);
    }
    .card h1 {
      margin: 0 0 8px 0;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    .subtitle {
      margin: 0 0 16px 0;
      font-size: 14px;
      color: var(--muted);
    }
    .alert {
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 14px;
      margin-bottom: 12px;
    }
    .alert-success { background: var(--success-bg); color: var(--success-text); border: 1px solid rgba(16,185,129,0.25); }
    .alert-error { background: var(--error-bg); color: var(--error-text); border: 1px solid rgba(239,68,68,0.25); }

    .field { margin-bottom: 12px; }
    label { display: block; font-size: 13px; margin-bottom: 6px; }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      border: 1px solid var(--border);
      background: transparent;
      color: inherit;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 15px;
      outline: none;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,0.12);
    }
    .hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
    .error-text { font-size: 12px; color: var(--error-text); margin-top: 6px; }

    .btn {
      width: 100%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: 0;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: transform .02s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .btn-primary {
      background: var(--primary);
      color: white;
      box-shadow: 0 6px 20px rgba(79,70,229,0.28);
    }
    .btn-primary:active { transform: translateY(1px); }
    .meta {
      margin-top: 14px;
      font-size: 12px;
      color: var(--muted);
    }
    .footer-link {
      display: block;
      text-align: center;
      margin-top: 14px;
      font-size: 13px;
      color: var(--muted);
      text-decoration: none;
    }
    .footer-link:hover { color: var(--text); }
    @media (min-width: 640px) {
      .card { padding: 22px; }
      .card h1 { font-size: 24px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card" role="region" aria-labelledby="title">
      <h1 id="title">Setup Admin Account</h1>
      <p class="subtitle">Create the first administrator. You’ll verify with the existing 2FA after this step.</p>

      @if(session('success'))
        <div class="alert alert-success" role="status" aria-live="polite">
          {{ session('success') }}
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-error" role="alert" aria-live="assertive">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.setup.store') }}" novalidate>
        @csrf

        <div class="field">
          <label for="name">Full name</label>
          <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
          @error('name') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
          @error('email') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div style="position: relative;">
            <input id="password" type="password" name="password" autocomplete="new-password" required style="padding-right: 40px;">
            <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;" title="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="hint">At least 8 characters recommended with letters, numbers, and symbols.</div>
          @error('password') <div class="error-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm password</label>
          <div style="position: relative;">
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required style="padding-right: 40px;">
            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b;" title="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Create Admin</button>
      </form>

      <p class="meta">
        After you submit, we’ll redirect you to your existing two-factor verification page. A code will be emailed to the address above.
      </p>

      <a class="footer-link" href="{{ route('admin.login') }}">Already have an admin? Sign in</a>
    </div>
  </div>

  <script>
    function togglePasswordVisibility(inputId, btn) {
        let input = inputId ? document.getElementById(inputId) : null;
        if (!input && btn) {
            input = btn.closest('div') ? btn.closest('div').querySelector('input') : btn.previousElementSibling;
        }
        if (!input) return;

        const icon = btn ? btn.querySelector('i') : null;
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        } else {
            input.type = 'password';
            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }
  </script>
</body>
</html>