<?php
$nav_page = basename($_SERVER['PHP_SELF']);

$nav_pending = dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor WHERE status = 'menunggu'");
$nav_kritis  = dbOne($conn, 'SELECT COUNT(*) n FROM stok_darah WHERE jumlah_kantong <= batas_kritis');
$nav_pending_count = (int) ($nav_pending['n'] ?? 0);
$nav_kritis_count  = (int) ($nav_kritis['n'] ?? 0);
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <a href="../admin/dashboard.php" class="sidebar-brand">PMI Sleman</a>
        <span class="sidebar-badge">Admin Panel</span>
    </div>

    <nav class="admin-sidebar-menu">
        <span class="sidebar-section-label">Menu Utama</span>

        <a href="../admin/dashboard.php" class="sidebar-link <?= $nav_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="../admin/donor.php" class="sidebar-link <?= $nav_page === 'donor.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar2-week"></i> Jadwal Donor
            <?php if ($nav_pending_count > 0): ?>
                <span class="badge text-bg-warning ms-auto"><?= e($nav_pending_count) ?></span>
            <?php endif; ?>
        </a>

        <a href="../admin/pendonor.php" class="sidebar-link <?= $nav_page === 'pendonor.php' ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill"></i> Pendonor
        </a>

        <a href="../admin/stok.php" class="sidebar-link <?= $nav_page === 'stok.php' ? 'active' : '' ?>">
            <i class="bi bi-droplet-fill"></i> Stok Darah
            <?php if ($nav_kritis_count > 0): ?>
                <span class="badge text-bg-danger ms-auto"><?= e($nav_kritis_count) ?></span>
            <?php endif; ?>
        </a>

        <span class="sidebar-section-label mt-3">Sistem</span>

        <a href="../index.php" class="sidebar-link">
            <i class="bi bi-house"></i> Lihat Website
        </a>
    </nav>

    <div class="admin-sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar"><?= e(inisial($_SESSION['nama'] ?? 'A')) ?></div>
            <div>
                <div class="admin-name"><?= e($_SESSION['nama'] ?? 'Admin') ?></div>
                <small>Administrator</small>
            </div>
        </div>

        <a href="../logout.php" class="sidebar-link logout-link">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>
</aside>