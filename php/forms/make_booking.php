<?php
/**
 * LittleLands — Make booking
 * Multi-step: select date/time/party → pick Area → confirm.
 */
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('basic-user');

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'browse_playgrounds';
$pageTitle = 'Make a booking';

$playgroundId = intval($_GET['playground_id'] ?? 0);
$playground = null;
if ($playgroundId) {
    require_once __DIR__ . '/../database/db_connect.php';
    $stmt = $conn->prepare("SELECT * FROM playgrounds WHERE id = ? AND is_active = 1");
    $stmt->bind_param('i', $playgroundId);
    $stmt->execute();
    $playground = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
}
if (!$playground) {
    header('Location: browse_playgrounds.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - LittleLands</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=bookings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php $showSidebarToggle = true;
include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <a href="browse_playgrounds.php" style="color:var(--primary-color);font-weight:600;text-decoration:none;font-size:0.9rem;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:1rem;">
                    <i class="fa-solid fa-arrow-left"></i> Back to playgrounds
                </a>

                <div class="booking-form-container">
                    <!-- playground Info Header -->
                    <div class="playground-header">
                        <div class="playground-header-info">
                            <h1 class="page-title"><?php echo htmlspecialchars($playground['name']); ?></h1>
                            <p class="playground-details">
                                <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($playground['playground_type']); ?></span>
                                <span><i class="fa-solid fa-clock"></i> <?php echo substr($playground['opening_time'], 0, 5); ?> – <?php echo substr($playground['closing_time'], 0, 5); ?></span>
                                <span><i class="fa-solid fa-star" style="color:#c8a951;"></i> <?php echo number_format($playground['rating'], 1); ?></span>
                                <span><?php echo $playground['price_range']; ?></span>
                            </p>
                            <?php if ($playground['description']): ?>
                                <p style="color:var(--text-muted);margin-top:0.5rem;font-size:0.9rem;"><?php echo htmlspecialchars($playground['description']); ?></p>
                            <?php
    endif; ?>
                        </div>
                    </div>

                    <!-- booking Form -->
                    <form id="bookingForm" class="booking-form">
                        <input type="hidden" name="playground_id" value="<?php echo $playground['id']; ?>">

                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                            <div class="form-group">
                                <label for="resDate">Date <span style="color:#dc2626;">*</span></label>
                                <input type="date" id="resDate" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="resTime">Time <span style="color:#dc2626;">*</span></label>
                                <input type="time" id="resTime" name="booking_time" required
                                       min="<?php echo substr($playground['opening_time'], 0, 5); ?>"
                                       max="<?php echo substr($playground['closing_time'], 0, 5); ?>">
                            </div>
                            <div class="form-group">
                                <label for="partySize">Party Size <span style="color:#dc2626;">*</span></label>
                                <input type="number" id="partySize" name="group_size" min="1" max="20" value="2" required>
                            </div>
                        </div>

                        <button type="button" id="checkAreasBtn" class="btn-secondary" style="margin-bottom:1.5rem;">
                            <i class="fa-solid fa-magnifying-glass"></i> Check Available Areas
                        </button>

                        <!-- Available Areas -->
                        <div id="AreasSection" style="display:none;">
                            <h3 style="font-family:var(--font-heading);margin-bottom:1rem;">Available Areas</h3>
                            <div id="AreasGrid" class="Areas-grid"></div>
                            <input type="hidden" id="selectedAreaId" name="area_id">
                        </div>

                        <div class="form-group" style="margin-top:1.5rem;">
                            <label for="specialRequests">Special Requests <span style="color:var(--primary-color);font-size:0.8rem;">(optional)</span></label>
                            <textarea id="specialRequests" name="special_requests" rows="3" placeholder="Any special requests for your play session?"></textarea>
                        </div>

                        <div id="formMessage" style="margin-bottom:1rem;"></div>

                        <button type="submit" id="submitbooking" class="btn-primary" style="width:100%;padding:0.85rem;" disabled>
                            <i class="fa-solid fa-calendar-check"></i> Confirm booking
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
const playgroundId = <?php echo $playground['id']; ?>;
let selectedAreaId = null;

// Check available Areas
document.getElementById('checkAreasBtn').addEventListener('click', async () => {
    const date = document.getElementById('resDate').value;
    const time = document.getElementById('resTime').value;
    const partySize = document.getElementById('partySize').value;
    const msg = document.getElementById('formMessage');
    msg.innerHTML = '';

    if (!date || !time || !partySize) {
        msg.innerHTML = '<span style="color:var(--error-color);">Please fill in date, time, and party size.</span>';
        return;
    }

    const section = document.getElementById('AreasSection');
    const grid = document.getElementById('AreasGrid');
    grid.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Checking...</div>';
    section.style.display = 'block';
    selectedAreaId = null;
    document.getElementById('selectedAreaId').value = '';
    document.getElementById('submitbooking').disabled = true;

    try {
        const params = new URLSearchParams({ playground_id: playgroundId, date, time, group_size: partySize });
        const res = await fetch(`${BASE_URL}/php/database/available_play_areas.php?${params}`);
        const data = await res.json();

        if (!data.success || !data.Areas.length) {
            grid.innerHTML = '<div class="empty-state"><p>No Areas available for your selection. Try a different date, time, or party size.</p></div>';
            return;
        }

        grid.innerHTML = data.Areas.map(t => `
            <div class="Area-option" data-id="${t.id}" onclick="selectArea(${t.id}, this)">
                <div class="Area-option-icon"><i class="fa-solid fa-shapes"></i></div>
                <div class="Area-option-info">
                    <strong>Area ${t.area_name}</strong>
                    <span>${t.location_type} · ${t.capacity} kids</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = '<div class="empty-state"><p>Error checking availability.</p></div>';
    }
});

function selectArea(id, el) {
    document.querySelectorAll('.Area-option').forEach(t => t.classList.remove('selected'));
    el.classList.add('selected');
    selectedAreaId = id;
    document.getElementById('selectedAreaId').value = id;
    document.getElementById('submitbooking').disabled = false;
}

// Submit booking
document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('formMessage');
    msg.innerHTML = '';
    const btn = document.getElementById('submitbooking');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';

    const formData = new FormData(e.target);

    try {
        const res = await fetch(`${BASE_URL}/php/database/booking_create.php`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            msg.innerHTML = '<span style="color:var(--success-color);font-weight:600;"><i class="fa-solid fa-circle-check"></i> booking created successfully! Redirecting...</span>';
            setTimeout(() => {
                window.location.href = `${BASE_URL}/php/forms/my_bookings.php`;
            }, 2000);
        } else {
            msg.innerHTML = `<span style="color:var(--error-color);">${data.error || 'Error creating booking.'}</span>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Confirm booking';
        }
    } catch (e) {
        msg.innerHTML = '<span style="color:var(--error-color);">Network error. Please try again.</span>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Confirm booking';
    }
});

// Sidebar toggle
const toggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.dashboard-sidebar');
const overlay = document.getElementById('sidebarOverlay');
if (toggle && sidebar) {
    toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
}
</script>
</body>
</html>



