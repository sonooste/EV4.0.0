<?php
// Set page title
$pageTitle = 'Dashboard';

// Include configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth-functions.php';
require_once dirname(__DIR__) . '/includes/booking-functions.php';

// Require login
requireLogin();

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $userId = $_SESSION['user_id'];
    return fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
}

// Get user data
$user = getCurrentUser();
$stats = getUserChargingStats($_SESSION['user_id']);
if (!$stats) {
    $stats = [
        'total' => ['charges' => 0, 'energy' => 0, 'cost' => 0],
        'monthly' => ['energy' => 0, 'cost' => 0]
    ];
}

// Get upcoming bookings
$upcomingBookings = getUserUpcomingBookings($_SESSION['user_id']);

// Include header
require_once dirname(__DIR__) . '/includes/header.php';

// Add dashboard.js to extra scripts
$extraScripts = ['dashboard.js'];
?>

    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Welcome, <?= htmlspecialchars($user['name']) ?></h1>
            <p class="dashboard-subtitle">Here's an overview of your charging activities</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-title">Total Charges</div>
                <div class="stat-card-value" data-value="<?= $stats['total']['charges'] ?>"><?= $stats['total']['charges'] ?></div>
                <div class="stat-card-info">Lifetime charging sessions</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-title">Total Energy</div>
                <div class="stat-card-value" data-value="<?= $stats['total']['energy'] ?>" data-suffix=" kWh" data-decimals="2"><?= formatEnergy($stats['total']['energy']) ?></div>
                <div class="stat-card-info">Total energy consumed</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-title">Total Cost</div>
                <div class="stat-card-value" data-value="<?= $stats['total']['cost'] ?>" data-prefix="€" data-decimals="2"><?= formatCurrency($stats['total']['cost']) ?></div>
                <div class="stat-card-info">Total amount spent</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-title">This Month</div>
                <div class="stat-card-value" data-value="<?= $stats['monthly']['energy'] ?>" data-suffix=" kWh" data-decimals="2"><?= formatEnergy($stats['monthly']['energy']) ?></div>
                <div class="stat-card-info"><?= formatCurrency($stats['monthly']['cost']) ?> spent this month</div>
            </div>
        </div>

        <!-- Upcoming Bookings Section -->
        <div class="card mb-6">
            <div class="card-header">
                <h2 class="card-title">Your Upcoming Bookings</h2>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingBookings)): ?>
                    <div class="alert alert-info">
                        <p>You have no upcoming bookings.</p>
                        <a href="<?= APP_URL ?>/pages/bookings.php" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-calendar-plus"></i> Make a Booking
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <tr>
                                    <td>
                                        <?= date('M j, Y', strtotime($booking['booking_datetime'])) ?><br>
                                        <small class="text-muted">
                                            <?= date('g:i A', strtotime($booking['booking_datetime'])) ?> -
                                            <?= isset($booking['booking_end_datetime']) ? date('g:i A', strtotime($booking['booking_end_datetime'])) : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['address_street']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['address_city']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($booking['booking_end_datetime'])) {
                                            $duration = strtotime($booking['booking_end_datetime']) - strtotime($booking['booking_datetime']);
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            echo $hours . 'h ' . $minutes . 'm';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="<?= APP_URL ?>/pages/cancel-booking.php"
                                              onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                            <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-main">
                <div class="card mb-6">
                    <div class="card-header">
                        <h2 class="card-title">Charging Activity</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3 class="chart-title">Energy Consumption</h3>
                                <div class="chart-filters">
                                    <button class="chart-filter" data-period="weekly">Weekly</button>
                                    <button class="chart-filter active" data-period="monthly">Monthly</button>
                                    <button class="chart-filter" data-period="yearly">Yearly</button>
                                </div>
                            </div>
                            <div class="chart-body" id="energy-consumption-chart" data-chart-type="Energy Consumption"></div>
                        </div>

                        <div class="chart-container mt-6">
                            <div class="chart-header">
                                <h3 class="chart-title">Charging Costs</h3>
                                <div class="chart-filters">
                                    <button class="chart-filter" data-period="weekly">Weekly</button>
                                    <button class="chart-filter active" data-period="monthly">Monthly</button>
                                    <button class="chart-filter" data-period="yearly">Yearly</button>
                                </div>
                            </div>
                            <div class="chart-body" id="charging-cost-chart" data-chart-type="Charging Costs"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="<?= APP_URL ?>/pages/stations.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h3 class="quick-action-title">Find Stations</h3>
                                <p class="quick-action-desc">Locate charging stations near you</p>
                            </a>

                            <a href="<?= APP_URL ?>/pages/bookings.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <h3 class="quick-action-title">New Booking</h3>
                                <p class="quick-action-desc">Reserve a charging session</p>
                            </a>

                            <a href="<?= APP_URL ?>/pages/history.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h3 class="quick-action-title">History</h3>
                                <p class="quick-action-desc">View your charging history</p>
                            </a>

                            <a href="<?= APP_URL ?>/pages/profile.php" class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <h3 class="quick-action-title">Profile</h3>
                                <p class="quick-action-desc">Manage your account settings</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>

        .dashboard-header {
            margin-bottom: var(--space-8);
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--space-2);
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            color: var(--gray-600);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }

        .stat-card {
            background-color: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: var(--space-4);
            text-align: center;
            transition: all var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
        }

        .stat-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-2);
        }

        .stat-card-info {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .dashboard-grid {
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* Centra le card orizzontalmente */
            gap: var(--space-4);
            width: 100%;
        }

        .quick-action-card {
            background-color: var(--gray-100);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            text-align: center;
            transition: all var(--transition-fast);
            color: var(--gray-800);
        }

        .quick-action-card:hover {
            background-color: var(--primary);
            color: var(--white);
            transform: translateY(-3px);
        }

        .quick-action-icon {
            width: 50px;
            height: 50px;
            background-color: var(--white);
            color: var(--primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-3);
            font-size: 1.2rem;
            transition: all var(--transition-fast);
        }

        .quick-action-card:hover .quick-action-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
        }

        .quick-action-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: var(--space-2);
        }

        .quick-action-desc {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Booking Card */
        .booking-card {
            background-color: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
            transition: all var(--transition);
        }

        .booking-card:last-child {
            margin-bottom: 0;
        }

        .booking-date {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: var(--space-2);
        }

        .booking-details {
            margin-bottom: var(--space-3);
        }

        .booking-time, .booking-location, .booking-info {
            margin-bottom: var(--space-1);
        }

        .booking-time i, .booking-location i {
            width: 20px;
            color: var(--gray-600);
        }

        .booking-info {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .booking-actions {
            display: flex;
            justify-content: flex-end;
        }

        /* Table styles */
        .table td {
            vertical-align: middle;
        }

        .btn-danger {
            background-color: var(--error);
            border-color: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>

<?php
// Include footer
require_once dirname(__DIR__) . '/includes/footer.php';
?>