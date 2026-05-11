<?php
/**
 * LittleLands — Browse playgrounds
 * Basic User page: search, filter by Category, view playground cards, click to reserve.
 */
require_once __DIR__ . '/../includes/auth_check.php';

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'browse_playgrounds';
$pageTitle = 'Browse playgrounds';
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
                <h1 class="page-title">Browse playgrounds</h1>
                <p class="page-subtitle">Discover and book your next playground adventure</p>

                <!-- Filters -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <div class="search-input-wrap">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search playgrounds..." class="search-input">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="typeFilter" class="filter-label">Filter by Type</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-filter input-icon"></i>
                            <select id="typeFilter" onchange="loadplaygrounds()">
                                <option value="">All Types</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- playground Grid -->
                <div class="playground-grid" id="playgroundGrid">
                    <div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading playgrounds...</div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

async function loadplaygrounds() {
    const search = document.getElementById('searchInput').value;
    const type = document.getElementById('typeFilter').value;
    const grid = document.getElementById('playgroundGrid');
    grid.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (type) params.append('type', type);
        const res = await fetch(`${BASE_URL}/php/database/playgrounds_list.php?${params}`);
        const data = await res.json();

        if (!data.success || !data.playgrounds.length) {
            grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-tent" style="font-size:2rem;margin-bottom:0.75rem;color:var(--text-muted);"></i><h3>No playgrounds found</h3><p>Try adjusting your search or filters.</p></div>';
            return;
        }

        // Populate Type filter
        if (data.types) {
            const tf = document.getElementById('typeFilter');
            const current = tf.value;
            tf.innerHTML = '<option value="">All Types</option>';
            data.types.forEach(c => {
                tf.innerHTML += `<option value="${c}" ${c === current ? 'selected' : ''}>${c}</option>`;
            });
        }

        grid.innerHTML = data.playgrounds.map(r => `
            <div class="playground-card card-interactive">
                <div class="playground-card-img">
                    ${r.image_path ? `<img src="${BASE_URL}/${r.image_path}" alt="${r.name}">` : '<div class="no-img"><i class="fa-solid fa-shapes"></i></div>'}
                </div>
                <div class="playground-card-body">
                    <div class="playground-card-header">
                        <h3>${r.name}</h3>
                        <span class="playground-rating"><i class="fa-solid fa-star"></i> ${parseFloat(r.rating).toFixed(1)}</span>
                    </div>
                    <p class="playground-type"><i class="fa-solid fa-tag"></i> ${r.playground_type || 'General'}</p>
                    <p class="playground-address"><i class="fa-solid fa-location-dot"></i> ${r.address || 'Address not available'}</p>
                    <div class="playground-meta">
                        <span><i class="fa-solid fa-clock"></i> ${r.opening_time?.slice(0,5)} – ${r.closing_time?.slice(0,5)}</span>
                        <span class="price-range">${r.price_range || '$$'}</span>
                    </div>
                    <a href="${BASE_URL}/php/forms/make_booking.php?playground_id=${r.id}" class="btn-primary" style="width:100%;margin-top:1rem;text-decoration:none;text-align:center;display:block;">
                        <i class="fa-solid fa-calendar-plus"></i> Book Now
                    </a>
                </div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = '<div class="empty-state"><p>Error loading playgrounds.</p></div>';
    }
}

document.getElementById('searchInput').addEventListener('input', debounce(loadplaygrounds, 400));
document.getElementById('typeFilter').addEventListener('change', loadplaygrounds);

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

loadplaygrounds();

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




