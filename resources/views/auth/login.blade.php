<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Sistem Peperiksaan STPM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        :root {
            --bg-dark: #090d16;
            --card-bg: rgba(17, 24, 39, 0.78);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --primary-color: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Glowing Mesh Background */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.45;
            pointer-events: none;
            z-index: 0;
            animation: drift 15s ease-in-out infinite alternate;
        }

        .ambient-glow-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            top: -100px;
            left: -150px;
        }

        .ambient-glow-2 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #ec4899 0%, rgba(236, 72, 153, 0) 70%);
            bottom: -150px;
            right: -100px;
            animation-duration: 20s;
        }

        .ambient-glow-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #3b82f6 0%, rgba(59, 130, 246, 0) 70%);
            top: 40%;
            left: 60%;
            opacity: 0.2;
        }

        @keyframes drift {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(30px) scale(1.06); }
            100% { transform: translateY(-20px) scale(0.96); }
        }

        .container {
            width: 100%;
            max-width: 780px;
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .logo-icon-box {
            width: 64px;
            height: 64px;
            margin: 0 auto 12px;
            border-radius: 20px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px var(--primary-glow), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .logo h1 {
            color: #ffffff;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Frosted Glass Tab Container */
        .tab-container {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            border: 1px solid var(--card-border);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 
                        0 0 0 1px rgba(255, 255, 255, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.15);
            overflow: hidden;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .tabs {
            display: flex;
            background: rgba(0, 0, 0, 0.25);
            padding: 8px;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .tab-btn {
            flex: 1;
            padding: 0.85rem 1rem;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            color: #ffffff;
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
        }

        .tab-content {
            padding: 2.2rem;
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-title, .register-title {
            text-align: center;
            margin-bottom: 1.8rem;
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Role Selector Cards */
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .role-option:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .role-option.active {
            border-color: #818cf8;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px -4px var(--primary-glow);
        }

        .role-option i {
            font-size: 1.5rem;
            margin-bottom: 6px;
            display: block;
            color: #a5b4fc;
        }

        .role-option.active i {
            color: #ffffff;
        }

        .role-option span {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .subrole-container {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.4s ease;
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .subrole-label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 700;
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        .subrole-selector {
            display: flex;
            gap: 10px;
        }

        .subrole-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .subrole-option:hover {
            border-color: rgba(99, 102, 241, 0.4);
            color: #ffffff;
        }

        .subrole-option.active {
            border-color: #818cf8;
            background: rgba(99, 102, 241, 0.2);
            color: #ffffff;
        }

        .register-role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .register-role-option {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .register-role-option:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .register-role-option.active {
            border-color: #818cf8;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px -4px var(--primary-glow);
        }

        .register-role-option i {
            font-size: 1.5rem;
            margin-bottom: 6px;
            display: block;
            color: #a5b4fc;
        }

        .register-role-option.active i {
            color: #ffffff;
        }

        .register-role-option span {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #cbd5e1;
            font-size: 0.88rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            padding: 13px 16px 13px 46px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-control:focus {
            outline: none;
            border-color: #818cf8;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .input-group:focus-within i {
            color: #a5b4fc;
        }

        select.form-control option {
            background-color: #1e293b;
            color: #ffffff;
        }

        textarea.form-control {
            padding: 13px 16px;
            resize: vertical;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            filter: brightness(1.08);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 1.4rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .row-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .info-panel {
            margin-top: 1.75rem;
            padding: 1.2rem;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
        }

        .info-panel summary {
            cursor: pointer;
            font-weight: 700;
            color: #a5b4fc;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-panel table {
            width: 100%;
            margin-top: 0.8rem;
            font-size: 0.82rem;
            border-collapse: separate;
            border-spacing: 0 4px;
        }

        .info-panel td {
            padding: 6px 8px;
        }

        .demo-hint {
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
        }

        .demo-hint:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        .demo-hint td:last-child {
            font-family: monospace;
            color: #93c5fd;
        }

        .register-fields-group {
            transition: all 0.3s ease;
        }

        small {
            color: #64748b;
            font-size: 0.78rem;
            margin-top: 4px;
            display: block;
        }

        @media (max-width: 768px) {
            .tab-content {
                padding: 1.6rem;
            }
            .role-option span, .register-role-option span {
                font-size: 0.75rem;
            }
            .row-2cols {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow ambient-glow-1"></div>
    <div class="ambient-glow ambient-glow-2"></div>
    <div class="ambient-glow ambient-glow-3"></div>
    <main class="container">
        <div class="logo">
            <div class="logo-icon-box"><i class="fas fa-graduation-cap" aria-hidden="true"></i></div>
            <h1>Sistem Peperiksaan STPM</h1>
            <p>Portal Pengurusan Peperiksaan Berpusat STPM</p>
        </div>
        <div class="tab-container">
            <div class="tabs"><div class="tab-btn active">Log Masuk</div></div>
            <div class="tab-content active">
                <h2 class="login-title"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Log Masuk</h2>
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="identity"><i class="fas fa-id-card" aria-hidden="true"></i> ID Pengguna atau Emel</label>
                        <div class="input-group"><i class="fas fa-user" aria-hidden="true"></i><input type="text" name="identity" id="identity" class="form-control" placeholder="No. Matrik / ID Guru / ID Admin" value="{{ old('identity') }}" autocomplete="username" required autofocus></div>
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock" aria-hidden="true"></i> Kata Laluan</label>
                        <div class="input-group"><i class="fas fa-key" aria-hidden="true"></i><input type="password" name="password" id="password" class="form-control" placeholder="Kata laluan" autocomplete="current-password" required></div>
                    </div>
                    <label style="display:block;margin-bottom:1rem;color:var(--text-muted)"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    <button type="submit" class="btn"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Log Masuk</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
