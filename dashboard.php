<?php
session_start();
require_once __DIR__ . '/../../../includes/config/koneksi.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- Proteksi: Mahasiswa/Public melihat event
// Kalau Anda mau public tanpa login, hapus blok ini.
// Namun sesuai sistem Anda sudah ada autentikasi, jadi kita jaga session.
if (!isset($_SESSION['user'])) {
  header("Location: ../../auth/login/login.html?error=not_logged_in");
  exit;
}
if ($_SESSION['user']['role'] !== 'mahasiswa') {
  header("Location: ../../auth/login/login.html?error=forbidden");
  exit;
}

// --- Ambil kategori untuk filter
$categories = [];
$qCat = mysqli_query($conn, "SELECT id, name FROM event_categories ORDER BY name ASC");
if ($qCat) while ($r = mysqli_fetch_assoc($qCat)) $categories[] = $r;

// --- Ambil kontak admin dari DB
$contacts = [];
$qContacts = mysqli_query($conn, "SELECT name, phone FROM admin_contacts ORDER BY id ASC");
if ($qContacts) while ($r = mysqli_fetch_assoc($qContacts)) $contacts[] = $r;

// --- Filter input (tanggal, kategori, keyword)
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$catId   = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$date    = isset($_GET['date']) ? trim($_GET['date']) : ''; // format: YYYY-MM-DD

// Bulan kalender (default: bulan sekarang, bisa diganti via query ?month=2025-10)
$monthParam = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) $monthParam = date('Y-m');
$monthStart = $monthParam . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// --- Build query event (search/filter)
$where = [];
$params = [];
$types = '';

if ($keyword !== '') {
  $where[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
  $kw = "%{$keyword}%";
  $params[] = $kw; $params[] = $kw; $params[] = $kw;
  $types .= 'sss';
}
if ($catId > 0) {
  $where[] = "e.category_id = ?";
  $params[] = $catId;
  $types .= 'i';
}
if ($date !== '') {
  // validasi ringan tanggal
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $where[] = "e.event_date = ?";
    $params[] = $date;
    $types .= 's';
  }
}

// Query event list (untuk cards)
$sql = "
  SELECT
    e.id, e.title, e.description, e.location, e.event_date, e.event_time, e.image_path,
    c.name AS category_name
  FROM events e
  JOIN event_categories c ON c.id = e.category_id
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY e.event_date ASC, e.event_time ASC, e.id ASC LIMIT 30";

$stmt = mysqli_prepare($conn, $sql);
$events = [];
if ($stmt) {
  if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res) while ($r = mysqli_fetch_assoc($res)) $events[] = $r;
}

// --- Data kalender: ambil semua tanggal event di bulan kalender untuk penandaan
$calDates = [];
$calSql = "SELECT DISTINCT event_date FROM events WHERE event_date BETWEEN ? AND ?";
$calStmt = mysqli_prepare($conn, $calSql);
if ($calStmt) {
  mysqli_stmt_bind_param($calStmt, 'ss', $monthStart, $monthEnd);
  mysqli_stmt_execute($calStmt);
  $calRes = mysqli_stmt_get_result($calStmt);
  if ($calRes) while ($r = mysqli_fetch_assoc($calRes)) $calDates[$r['event_date']] = true;
}

// --- Detail event via modal (?detail=ID)
$detail = null;
$detailId = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;
if ($detailId > 0) {
  $dSql = "
    SELECT
      e.id, e.title, e.description, e.location, e.event_date, e.event_time, e.image_path,
      c.name AS category_name
    FROM events e
    JOIN event_categories c ON c.id = e.category_id
    WHERE e.id = ?
    LIMIT 1
  ";
  $dStmt = mysqli_prepare($conn, $dSql);
  if ($dStmt) {
    mysqli_stmt_bind_param($dStmt, 'i', $detailId);
    mysqli_stmt_execute($dStmt);
    $dRes = mysqli_stmt_get_result($dStmt);
    if ($dRes && mysqli_num_rows($dRes) === 1) $detail = mysqli_fetch_assoc($dRes);
  }
}

$userName = $_SESSION['user']['name'] ?? 'Mahasiswa';

// --- Helper kalender
$firstDayOfMonth = date('w', strtotime($monthStart)); // 0=Sun
$daysInMonth = (int)date('t', strtotime($monthStart));

function monthLabel($ym) {
  $ts = strtotime($ym . "-01");
  $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  return $bulan[(int)date('n',$ts)-1] . " " . date('Y',$ts);
}

$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Informasi Event Kampus</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/dashboard_mahasiswa.css">
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column align-items-center text-center">
    <img src="../../assets/img/poltek.png" alt="Logo Polibatam">
    <nav class="nav flex-column w-100 text-start">
      <a href="dashboard.php" class="nav-link active">Dashboard</a>
    </nav>
    <button class="logout-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
  </div>

  <div class="content">
    <!-- Navbar -->
    <div class="navbar">
      <h4 class="mb-0">Informasi Event Kampus</h4>

      <div class="d-flex align-items-center gap-3">
        <!-- Kontak Admin dari DB -->
        <div class="dropdown">
          <button class="dropdown-toggle d-flex align-items-center" type="button" id="dropdownKontak" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-telephone-fill me-2 fs-5"></i> Kontak Admin
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownKontak">
            <li class="dropdown-header text-primary fw-bold">Admin Event</li>
            <?php if (!$contacts): ?>
              <li><span class="dropdown-item-text text-muted">Kontak belum tersedia</span></li>
            <?php else: ?>
              <?php foreach ($contacts as $c):
                $phoneDigits = preg_replace('/\D+/', '', $c['phone']);
                $wa = "https://wa.me/62" . ltrim($phoneDigits, '0');
              ?>
                <li>
                  <a class="dropdown-item" href="<?= e($wa) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-person-circle me-2"></i><?= e($c['name']) ?>: <?= e($c['phone']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <!-- User -->
        <div class="dropdown">
          <button class="dropdown-toggle d-flex align-items-center" type="button" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2 fs-5"></i> <?= e($userName) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
            <li class="dropdown-header fw-bold"><?= e($userName) ?></li>
            <li><span class="dropdown-item-text text-muted"><?= e($_SESSION['user']['email'] ?? '') ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- SEARCH & FILTER (sesuai dokumen) -->
    <div class="mt-4">
      <h6 class="mb-2">Pencarian & Filter Event</h6>

      <form class="row g-2 align-items-end" method="GET" action="dashboard.php">
        <div class="col-12 col-md-4">
          <label class="form-label">Kata Kunci</label>
          <input type="text" class="form-control" name="q" value="<?= e($keyword) ?>" placeholder="Cari judul/deskripsi/lokasi">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Kategori</label>
          <select class="form-select" name="category_id">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $catId===(int)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control" name="date" value="<?= e($date) ?>">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Cari</button>
          <a class="btn btn-outline-secondary w-100" href="dashboard.php">Reset</a>
        </div>
      </form>
    </div>

    <!-- LIST EVENT (cards) + detail -->
    <div class="mt-4">
      <div class="d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Daftar Event</h6>
        <span class="text-muted small">Menampilkan max 30 event</span>
      </div>

      <div class="row g-3 mt-2">
        <?php if (!$events): ?>
          <div class="col-12">
            <div class="alert alert-secondary mb-0">Tidak ada event yang sesuai filter.</div>
          </div>
        <?php else: ?>
          <?php foreach ($events as $ev):
            $d = date('d M Y', strtotime($ev['event_date']));
            $t = $ev['event_time'] ? date('H:i', strtotime($ev['event_time'])) : '-';
            $img = $ev['image_path'] ? "../../assets/uploads/" . $ev['image_path'] : "../../assets/img/event-placeholder.jpg";

            // Pertahankan filter saat membuka detail
            $qs = $_GET;
            $qs['detail'] = $ev['id'];
            $detailUrl = 'dashboard.php?' . http_build_query($qs);
          ?>
            <div class="col-12 col-md-4">
              <div class="event-card">
                <img src="<?= e($img) ?>" alt="Event Image" onerror="this.src='../../assets/img/event-placeholder.jpg'">
                <p class="mb-1"><b><?= e($ev['title']) ?></b></p>
                <p class="text-muted mb-1 small"><?= e($ev['category_name']) ?></p>
                <p class="mb-1"><?= e($ev['description']) ?></p>
                <p class="mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($ev['location']) ?></p>
                <p class="mb-1"><i class="bi bi-calendar-event me-1"></i><?= e($d) ?></p>
                <p class="mb-2"><i class="bi bi-clock me-1"></i><?= e($t) ?></p>
                <a class="btn btn-outline-primary btn-sm w-100" href="<?= e($detailUrl) ?>">
                  Lihat Detail
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- KALENDER EVENT (sesuai dokumen) -->
    <div class="calendar text-center">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <a class="btn btn-outline-secondary btn-sm"
           href="dashboard.php?<?= e(http_build_query(array_merge($_GET, ['month'=>$prevMonth]))) ?>">
          <i class="bi bi-chevron-left"></i>
        </a>

        <h5 class="mb-0"><?= e(strtoupper(monthLabel($monthParam))) ?></h5>

        <a class="btn btn-outline-secondary btn-sm"
           href="dashboard.php?<?= e(http_build_query(array_merge($_GET, ['month'=>$nextMonth]))) ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>

      <table>
        <thead>
          <tr>
            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $day = 1;
          $cell = 0;
          for ($row=0; $row<6; $row++) {
            echo "<tr>";
            for ($col=0; $col<7; $col++) {
              $cell++;
              if ($row === 0 && $col < $firstDayOfMonth) {
                echo "<td></td>";
                continue;
              }
              if ($day > $daysInMonth) {
                echo "<td></td>";
                continue;
              }

              $fullDate = $monthParam . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
              $isEventDay = isset($calDates[$fullDate]);

              // klik tanggal -> set filter date
              $qs = $_GET;
              $qs['date'] = $fullDate;
              unset($qs['detail']); // jangan bawa detail
              $dateUrl = 'dashboard.php?' . http_build_query($qs);

              $cls = $isEventDay ? 'event-day' : '';
              echo "<td class='{$cls}'>";
              echo "<a href='".e($dateUrl)."' style='text-decoration:none;color:inherit;display:block;'>";
              echo e($day);
              if ($isEventDay) echo "<br><span style='font-size:12px;font-weight:600;'>Event</span>";
              echo "</a>";
              echo "</td>";

              $day++;
            }
            echo "</tr>";
            if ($day > $daysInMonth) break;
          }
          ?>
        </tbody>
      </table>
      <div class="text-muted small mt-2">Klik tanggal untuk memfilter event pada tanggal tersebut.</div>
    </div>

  </div>

  <!-- Modal Logout -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <h5 class="mb-3">Yakin mau keluar?</h5>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-tidak" data-bs-dismiss="modal">Tidak</button>
          <a href="../../../includes/procces/logout.php" class="btn btn-ya">Ya</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail Event -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Event</h5>
          <a class="btn-close" href="dashboard.php?<?= e(http_build_query(array_diff_key($_GET, ['detail'=>true]))) ?>"></a>
        </div>
        <div class="modal-body">
          <?php if ($detail): 
            $d = date('d M Y', strtotime($detail['event_date']));
            $t = $detail['event_time'] ? date('H:i', strtotime($detail['event_time'])) : '-';
            $img = $detail['image_path'] ? "../../assets/uploads/" . $detail['image_path'] : "../../assets/img/event-placeholder.jpg";
          ?>
            <div class="row g-3">
              <div class="col-12 col-md-5">
                <img src="<?= e($img) ?>" alt="Event" class="img-fluid rounded" onerror="this.src='../../assets/img/event-placeholder.jpg'">
              </div>
              <div class="col-12 col-md-7">
                <div class="badge text-bg-primary mb-2"><?= e($detail['category_name']) ?></div>
                <h5 class="mb-2"><?= e($detail['title']) ?></h5>
                <p class="mb-2"><?= e($detail['description']) ?></p>
                <p class="mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($detail['location']) ?></p>
                <p class="mb-1"><i class="bi bi-calendar-event me-1"></i><?= e($d) ?></p>
                <p class="mb-0"><i class="bi bi-clock me-1"></i><?= e($t) ?></p>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-secondary mb-0">Detail event tidak ditemukan.</div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <a class="btn btn-outline-secondary" href="dashboard.php?<?= e(http_build_query(array_diff_key($_GET, ['detail'=>true]))) ?>">Tutup</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <?php if ($detail): ?>
    <script>
      const m = new bootstrap.Modal(document.getElementById('detailModal'));
      m.show();
    </script>
  <?php endif; ?>
</body>
</html>
