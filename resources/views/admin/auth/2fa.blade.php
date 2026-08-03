<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .card-header {
            background-color: #667eea;
            color: white;
        }
        input[type="text"] {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Two Factor Verification</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">A verification code has been sent to your email.</p>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.auth.twofactor.verify') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="code" class="form-label">Verification Code</label>
                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" 
                                       placeholder="000000" maxlength="6" inputmode="numeric" required autofocus>
                                @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Verify</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <form method="POST" action="{{ route('admin.auth.twofactor.resend') }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-link" type="submit">Resend code</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>