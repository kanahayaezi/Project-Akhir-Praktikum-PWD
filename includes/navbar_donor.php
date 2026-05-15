<?php $halaman = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top donor-navbar">
    <div class="container-lg">
        <a class="navbar-brand fw-bold text-pmi" href="../index.php">PMI Sleman</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navDonor" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navDonor">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $halaman === 'dashboard.php' ? 'active' : '' ?>" href="../donor/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman === 'kuesioner.php' ? 'active' : '' ?>" href="../donor/kuesioner.php">
                        <i class="bi bi-clipboard2-pulse"></i> Kuesioner
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman === 'daftar.php' ? 'active' : '' ?>" href="../donor/daftar.php">
                        <i class="bi bi-calendar-plus"></i> Daftar Donor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman === 'riwayat.php' ? 'active' : '' ?>" href="../donor/riwayat.php">
                        <i class="bi bi-clock-history"></i> Riwayat
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-outline-danger btn-sm" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>