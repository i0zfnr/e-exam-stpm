<?php
// ======================================================
// FAIL AKSES TIDAK DIBENARKAN (MODERN 403 UI)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';

// Dapatkan maklumat pengguna jika ada
$currentUser = getCurrentUser();

$dashboardUrl = 'login.php';
$roleName = 'Tetamu';
$roleBadgeClass = 'badge-guest';
$avatarLetter = 'G';

if ($currentUser) {
    $avatarLetter = strtoupper(substr($currentUser['full_name'] ?? $currentUser['username'] ?? 'U', 0, 1));
    if ($currentUser['role'] === 'pelajar') {
        $dashboardUrl = 'student.php';
        $roleName = 'Pelajar';
        $roleBadgeClass = 'badge-student';
    } elseif ($currentUser['role'] === 'guru') {
        if ($currentUser['sub_role'] === 'guru_kelas') {
            $dashboardUrl = 'gurukelas.php';
            $roleName = 'Guru Kelas';
            $roleBadgeClass = 'badge-guru-kelas';
        } else {
            $dashboardUrl = 'guruSubjek.php';
            $roleName = 'Guru Subjek';
            $roleBadgeClass = 'badge-guru-subjek';
        }
    } elseif ($currentUser['role'] === 'admin') {
        if ($currentUser['sub_role'] === 'super_admin') {
            $dashboardUrl = 'superAdmin.php';
            $roleName = 'Super Admin';
            $roleBadgeClass = 'badge-super';
        } else {
            $dashboardUrl = 'admin.php';
            $roleName = 'Pentadbir Sistem';
            $roleBadgeClass = 'badge-admin';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Tidak Dibenarkan | Sistem Peperiksaan STPM</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #090d16;
            --card-bg: rgba(17, 24, 39, 0.75);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --danger-glow: rgba(239, 68, 68, 0.25);
            --danger-color: #ef4444;
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
            padding: 24px;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient glowing gradient mesh background */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.45;
            pointer-events: none;
            z-index: 0;
            animation: drift 14s ease-in-out infinite alternate;
        }

        .ambient-glow-1 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, #7c3aed 0%, rgba(124, 58, 237, 0) 70%);
            top: -100px;
            left: -100px;
        }

        .ambient-glow-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #ef4444 0%, rgba(239, 68, 68, 0) 70%);
            bottom: -150px;
            right: -100px;
            animation-duration: 18s;
        }

        .ambient-glow-3 {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #3b82f6 0%, rgba(59, 130, 246, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.2;
        }

        @keyframes drift {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(30px) scale(1.08); }
            100% { transform: translateY(-20px) scale(0.95); }
        }

        /* Main Container */
        .error-wrapper {
            position: relative;
            z-index: 1;
            max-width: 540px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Glass Card */
        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 2.8rem 2.4rem;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 
                        0 0 0 1px rgba(255, 255, 255, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.15);
            animation: cardEntrance 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Status Badge */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fca5a5;
            margin-bottom: 1.5rem;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #ef4444;
            box-shadow: 0 0 8px #ef4444;
            animation: ringPulse 2s infinite;
        }

        @keyframes ringPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Icon Container */
        .icon-box {
            position: relative;
            width: 104px;
            height: 104px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-halo {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.3) 0%, rgba(239, 68, 68, 0.02) 70%);
            animation: haloExpand 3s infinite alternate ease-in-out;
        }

        @keyframes haloExpand {
            0% { transform: scale(0.95); opacity: 0.6; }
            100% { transform: scale(1.15); opacity: 1; }
        }

        .icon-circle {
            position: relative;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #f87171;
            box-shadow: 0 10px 25px -5px var(--danger-glow);
        }

        /* Typography */
        h1 {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.25;
            margin-bottom: 0.75rem;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        /* User Capsule Card */
        .user-capsule {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 18px;
            padding: 1.1rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            text-align: left;
            transition: border-color 0.2s ease;
        }

        .user-capsule:hover {
            border-color: rgba(255, 255, 255, 0.14);
        }

        .user-info-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
            flex-shrink: 0;
        }

        .user-text-details {
            min-width: 0;
        }

        .user-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-meta {
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        /* Role Badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .badge-super {
            background: rgba(217, 70, 239, 0.15);
            color: #f0abfc;
            border: 1px solid rgba(217, 70, 239, 0.3);
        }

        .badge-admin {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-guru-kelas {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-guru-subjek {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-student {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .badge-guest {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        /* Guest Notice */
        .guest-notice {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1.75rem;
            font-size: 0.88rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .guest-notice i {
            color: #f59e0b;
        }

        /* Action Buttons */
        .btn-stack {
            display: flex;
            gap: 12px;
            width: 100%;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 22px;
            border-radius: 14px;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            flex: 1;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            filter: brightness(1.08);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .btn-danger-outline {
            background: rgba(239, 68, 68, 0.08);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .btn-danger-outline:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #ffffff;
            border-color: rgba(239, 68, 68, 0.45);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Footer */
        .footer-note {
            margin-top: 2rem;
            color: #475569;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-note i {
            color: #64748b;
        }

        @media (max-width: 540px) {
            .error-card {
                padding: 2rem 1.5rem;
                border-radius: 22px;
            }
            .btn-stack {
                flex-direction: column;
            }
            .user-capsule {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Ambient Glow Orbs -->
    <div class="ambient-glow ambient-glow-1"></div>
    <div class="ambient-glow ambient-glow-2"></div>
    <div class="ambient-glow ambient-glow-3"></div>

    <div class="error-wrapper">
        <div class="error-card">
            <!-- 403 Status Pill -->
            <div class="status-pill">
                <span class="pulse-dot"></span>
                <span>Ralat 403 • Akses Disekat</span>
            </div>

            <!-- Glowing Icon -->
            <div class="icon-box">
                <div class="icon-halo"></div>
                <div class="icon-circle">
                    <i class="fas fa-shield-halved"></i>
                </div>
            </div>

            <!-- Main Heading -->
            <h1>Akses Tidak Dibenarkan</h1>
            
            <p class="error-desc">
                Halaman yang cuba anda akses memerlukan kebenaran peranan khusus. Sistem telah menyekat permintaan ini untuk melindungi keselamatan data peperiksaan.
            </p>

            <!-- User Session Capsule -->
            <?php if ($currentUser) { ?>
            <div class="user-capsule">
                <div class="user-info-left">
                    <div class="user-avatar">
                        <?php echo $avatarLetter; ?>
                    </div>
                    <div class="user-text-details">
                        <div class="user-name">
                            <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?>
                        </div>
                        <div class="user-meta">
                            <i class="fas fa-id-badge"></i>
                            <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="role-badge <?php echo $roleBadgeClass; ?>">
                    <i class="fas fa-user-check"></i>
                    <span><?php echo $roleName; ?></span>
                </div>
            </div>
            <?php } else { ?>
            <div class="guest-notice">
                <i class="fas fa-circle-exclamation"></i>
                <span>Anda belum log masuk ke dalam sistem peperiksaan.</span>
            </div>
            <?php } ?>

            <!-- Action Buttons -->
            <div class="btn-stack">
                <?php if ($currentUser) { ?>
                    <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary" id="btnGoDashboard">
                        <i class="fas fa-gauge-high"></i>
                        <span>Ke Papan Pemuka</span>
                    </a>
                    <a href="logout.php" class="btn btn-danger-outline" id="btnLogout">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Log Keluar</span>
                    </a>
                <?php } else { ?>
                    <a href="javascript:history.back()" class="btn btn-secondary" id="btnBack">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    <a href="login.php" class="btn btn-primary" id="btnLogin">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        <span>Log Masuk</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <i class="fas fa-graduation-cap"></i>
            <span>Sistem Peperiksaan STPM &bull; Modul Keselamatan & Kawalan Akses</span>
        </div>
    </div>
</body>
</html>