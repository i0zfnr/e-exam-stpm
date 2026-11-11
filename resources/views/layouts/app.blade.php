<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'e-Exam STPM')</title>
    <style>
        :root{font-family:Inter,"Segoe UI",sans-serif;color:#172033;background:#f3f6fa}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh}
        .top{height:72px;background:white;border-bottom:1px solid #e5eaf1;display:flex;align-items:center;justify-content:space-between;padding:0 max(24px,calc((100% - 1120px)/2))}
        .brand{display:flex;align-items:center;gap:12px;font-weight:750;color:#142746}
        .logo{width:40px;height:40px;background:#173b67;color:white;border-radius:12px;display:grid;place-items:center;font-weight:800}
        .container{max-width:1120px;margin:54px auto;padding:0 24px}
        .card{background:white;border:1px solid #e4eaf2;border-radius:18px;padding:32px;box-shadow:0 10px 30px #173b670d}
        .muted{color:#718096}
        .btn{display:inline-flex;align-items:center;justify-content:center;background:#173b67;color:white;text-decoration:none;border:0;border-radius:10px;padding:12px 18px;font-weight:650;cursor:pointer}
        .btn:hover{background:#0e2d52}
        .field{display:grid;gap:8px;margin:18px 0}
        .field input{width:100%;height:48px;border:1px solid #d9e1eb;border-radius:10px;padding:0 14px;font:inherit}
        .field input:focus{outline:3px solid #173b671c;border-color:#173b67}
        .alert{padding:12px 14px;background:#fff1f0;color:#9d2822;border-radius:10px;margin:16px 0}
        .row{display:flex;align-items:center;justify-content:space-between;gap:16px}
        .pill{background:#eaf0f7;color:#173b67;padding:7px 11px;border-radius:99px;font-size:13px;font-weight:650}
        @media(max-width:640px){.container{margin:26px auto;padding:0 16px}.card{padding:24px}.top{padding:0 16px}.row{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<header class="top">
    <div class="brand">
        <div class="logo">ST</div>
        <span>e-Exam STPM</span>
    </div>
    @auth
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn" type="submit">Log keluar</button>
        </form>
    @endauth
</header>
<main class="container">
    @yield('content')
</main>
</body>
</html>
