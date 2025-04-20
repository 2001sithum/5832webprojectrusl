<?php
require_once '../includes/db.php'; // Relative path now
require_once '../includes/functions.php';

requireAdmin(); // Only admins

$pageTitle = "Admin Dashboard";

$events = []; // Initialize events array
// Initialize stats array with default values
$stats = ['total' => 0, 'upcoming' => 0, 'ongoing' => 0, 'users' => 0];

try {
    // Fetch all events for listing, order by date and time descending
    $stmtEvents = $db->query("SELECT * FROM events ORDER BY date DESC, time DESC");
    if ($stmtEvents) {
        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Handle query error if needed
        error_log("Admin Dashboard: Failed to fetch events.");
    }


    // Calculate stats
    $stats['total'] = count($events); // Total events fetched

    // Count upcoming events (status 'Upcoming' and date is today or later)
    // Use prepared statement for safety, though 'now' is usually safe in SQLite/MySQL context
    $stmtUpcoming = $db->query("SELECT COUNT(*) FROM events WHERE status='Upcoming' AND date >= date('now')");
    $stats['upcoming'] = $stmtUpcoming ? $stmtUpcoming->fetchColumn() : 0;

    // Count ongoing events
    $stmtOngoing = $db->query("SELECT COUNT(*) FROM events WHERE status='Ongoing'");
    $stats['ongoing'] = $stmtOngoing ? $stmtOngoing->fetchColumn() : 0;

    // Count total users
    $stmtUsers = $db->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmtUsers ? $stmtUsers->fetchColumn() : 0;


} catch (PDOException $e) {
    error_log("Admin Dashboard Fetch Error: " . $e->getMessage());
    // Set a session message about the error, which will be displayed by the header template
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load admin dashboard data.'];
    // Keep default stats, the page will still render
}

// Include the header template
includeTemplate('header', ['pageTitle' => $pageTitle]);
?>
<?php // Inline CSS Block 1 (Theme - Spotify Dark) ?>
    <style>
        /* Define CSS variables for consistency */
        :root {
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-sm: 0.8rem;
            --spacing-xs: 0.5rem;
            --primary-color: #1DB954; /* Spotify green */
            --secondary-color: #FFFFFF;
            --background-dark: #121212; /* Spotify dark background */
            --text-color: #FFFFFF;
            --text-light: #FFFFFF; /* For numbers, potentially */
            --text-medium: #b3b3b3; /* For labels */
            --bg-light: #282828; /* Darker card background */
            --glass-bg: rgba(255, 255, 255, 0.1); /* Glass effect background */
            --blur-radius: 10px;
            --error-color: #E91429;
            --warning-color: #FFA000; /* Material Amber */
            --info-color: #03A9F4;    /* Material Light Blue */
            --success-color: var(--primary-color);
            --danger-color: #F44336; /* Material Red */
            --border-radius: 8px;
            --border-radius-md: 6px; /* Medium radius for cards/tables */
            --accent-color: var(--primary-color); /* Accent color for stats */
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Subtle shadow */
            --font-weight-bold: 600;
        }

        /* Apply dark theme styles directly without body class assumption */
        .content-section.admin-dashboard {
            background: var(--background-dark);
            color: var(--text-color);
            padding: var(--spacing-xl);
        }

        .page-title {
            color: var(--text-color);
            margin-bottom: var(--spacing-lg);
            font-weight: var(--font-weight-bold);
        }

        /* Stats Grid */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        .stat-card {
            background: var(--bg-light);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-md);
            text-align: center;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent-color);
            color: var(--text-color); /* Ensure text is white */
        }
        .stat-card i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: var(--spacing-sm);
            display: block;
        }
        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: var(--font-weight-bold);
            color: var(--text-light);
        }
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-medium);
        }

        /* Admin Actions Buttons */
        .admin-actions {
            margin-bottom: var(--spacing-lg); /* Use var */
            margin-top: var(--spacing-lg); /* Use var */
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap; /* Allow wrapping on small screens */
        }

        /* General Button Styles (Dark Theme) */
        .button {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none; /* Remove default border */
            display: inline-flex; /* Align icon and text */
            align-items: center;
            gap: var(--spacing-xs); /* Space between icon and text */
        }
        .button-primary {
            background: var(--primary-color);
            color: #000; /* Black text on primary green */
        }
        .button-primary:hover {
            background: #21CE62; /* Lighter green */
            box-shadow: 0 0 10px rgba(29, 185, 84, 0.5);
        }
        .button-secondary {
            background: transparent;
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        .button-secondary:hover {
            background: rgba(255, 255, 255, 0.1); /* Subtle white overlay */
        }

        /* Section Title */
        .section-title {
            color: var(--text-color);
            margin-top: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            font-size: 1.5rem;
            border-bottom: 1px solid var(--bg-light);
            padding-bottom: var(--spacing-sm);
        }

        /* Info Message */
        .message.info {
            background-color: rgba(3, 169, 244, 0.2); /* Light blue background */
            color: var(--info-color);
            border: 1px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .message.info a {
            color: var(--primary-color); /* Link color */
            text-decoration: underline;
        }

        /* Admin Table */
        .admin-table-container {
            overflow-x: auto; /* Allow horizontal scrolling on small screens */
            background: var(--bg-light);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-xs); /* Minimal padding around table */
            box-shadow: var(--shadow);
            margin-top: var(--spacing-lg);
        }
        .admin-table-container table {
            width: 100%;
            border-collapse: collapse;
            background: transparent; /* Avoid double background */
            color: var(--text-color); /* Ensure text is white */
        }
        .admin-table-container th,
        .admin-table-container td {
            padding: var(--spacing-sm) var(--spacing-lg); /* Consistent padding */
            text-align: left;
            white-space: nowrap; /* Prevent wrapping */
            font-size: 0.9rem;
            border-bottom: 1px solid #404040; /* Darker border */
        }
        .admin-table-container th {
            font-weight: var(--font-weight-bold);
            color: var(--text-medium); /* Slightly muted header text */
            background-color: #333333; /* Slightly darker header background */
        }
        .admin-table-container tbody tr:hover {
            background-color: #3a3a3a; /* Hover effect */
        }
        .admin-table-container td a {
            color: var(--primary-color); /* Link color in table */
            text-decoration: none;
        }
        .admin-table-container td a:hover {
            text-decoration: underline;
        }

        /* Status Indicator Styles */
        .status-upcoming { color: #03A9F4; /* Light Blue */ }
        .status-ongoing { color: #FF9800; /* Orange */ }
        .status-completed { color: #4CAF50; /* Green */ }
        .status-cancelled { color: #F44336; /* Red */ }
        /* Optional: add background pill style */
        /* td span[class^="status-"] { padding: 2px 6px; border-radius: 4px; background-color: #444; } */


        /* Action Cell in Table */
        .action-cell {
            display: flex;
            gap: var(--spacing-xs);
            align-items: center; /* Vertically center buttons */
        }
        .button-sm { /* Smaller buttons for table actions */
            padding: 5px 8px;
            font-size: 0.8rem;
            border-radius: 4px; /* Smaller radius */
        }
        .button-info { background-color: var(--info-color); border-color: var(--info-color); color: white; }
        .button-info:hover { background-color: #0288D1; border-color: #0288D1; }
        .button-warning { background-color: var(--warning-color); border-color: var(--warning-color); color: black; } /* Good contrast */
        .button-warning:hover { background-color: #F57C00; border-color: #F57C00; }
        .button-danger { background-color: var(--danger-color); border-color: var(--danger-color); color: white; }
        .button-danger:hover { background-color: #D32F2F; border-color: #D32F2F; }

        /* Inline form for delete button */
        .inline-form {
            display: inline-block; /* Keep button alignment */
            margin: 0; /* Reset margin */
            padding: 0; /* Reset padding */
        }

        /* Margins Utilities (if not globally defined) */
        .mb-3 { margin-bottom: var(--spacing-lg); }
        .mt-3 { margin-top: var(--spacing-lg); }

    </style>
<?php // Separate second <style> block as in original - could be merged ?>
    <style>
        /* This block seems redundant with the first one, but keeping it as per the original structure */
        /* General form container styling (if needed again or differently) */
        .form-container.admin-form { /* Potentially overrides if needed */
            /* ... styles ... */
        }
        /* ... other styles from the second block if they were different ... */
    </style>

    <section class="content-section admin-dashboard">
        <h1 class="page-title">Admin Dashboard</h1>
        <?php // displayMessages(); // Assumes this function is in header/footer includes to show session messages ?>

        <?php // ----- Admin Stats Section ----- ?>
        <div class="admin-stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <span class="stat-number"><?php echo (int)$stats['total']; ?></span>
                <span class="stat-label">Total Events</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-day"></i>
                <span class="stat-number"><?php echo (int)$stats['upcoming']; ?></span>
                <span class="stat-label">Upcoming Events</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-running"></i>
                <span class="stat-number"><?php echo (int)$stats['ongoing']; ?></span>
                <span class="stat-label">Ongoing Events</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <span class="stat-number"><?php echo (int)$stats['users']; ?></span>
                <span class="stat-label">Total Users</span>
            </div>
        </div>

        <?php // ----- Admin Actions Section ----- ?>
        <div class="admin-actions mb-3 mt-3">
            <a href="../edit_event.php" class="button button-primary">
                <i class="fas fa-plus"></i> Add New Event
            </a>
            <?php /* Add user management link (placeholder) */ ?>
            <a href="#" class="button button-secondary" onclick="alert('User Management not implemented yet.'); return false;">
                <i class="fas fa-users-cog"></i> Manage Users
            </a>
        </div>

        <?php // ----- Manage Events Section ----- ?>
        <h2 class="section-title mt-3">Manage Events</h2>

        <?php if (empty($events)): ?>
            <div class="message info">
                No events found. <a href="../edit_event.php">Add the first one!</a>
            </div>
        <?php else: ?>
            <div class="admin-table-container">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Capacity</th>
                        <th>Attendees</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <?php // Link to public event detail page ?>
                            <td><a href="../event_details.php?id=<?php echo (int)$event['id']; ?>" title="View Public Details"><?php e($event['name']); ?></a></td>
                            <td><?php echo escape(date('M j, Y @ g:i A', strtotime($event['date'] . ' ' . $event['time']))); ?></td>
                            <td><?php e($event['location']); ?></td>
                            <td><?php e($event['category'] ?: '-'); // Show dash if no category ?></td>
                            <td>
                                <?php // Display status with a class for potential styling ?>
                                <span class="status-<?php echo strtolower(escape($event['status'])); ?>">
                                <?php e($event['status']); ?>
                            </span>
                            </td>
                            <td><?php e($event['capacity']); ?></td>
                            <td><?php e($event['attendees_count']); ?></td>
                            <td class="action-cell">
                                <?php // View Button ?>
                                <a href="../event_details.php?id=<?php echo (int)$event['id']; ?>" class="button button-sm button-info" title="View Public Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php // Edit Button ?>
                                <a href="../edit_event.php?id=<?php echo (int)$event['id']; ?>" class="button button-sm button-warning" title="Edit Event">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php // Delete Button (within a form) ?>
                                <form action="../delete_event.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to permanently DELETE the event \'<?php e(addslashes($event['name'])); ?>\'? This cannot be undone!');">
                                    <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                                    <?php // No CSRF token input here ?>
                                    <button type="submit" class="button button-sm button-danger" title="Delete Event">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </section>

<?php includeTemplate('footer'); ?>