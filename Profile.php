<?php
session_start();
include 'Database.php';
include 'Trip.php';
include 'User.php';
include 'BusStop.php';
include 'SavedRoute.php'; 
include 'Logger.php';
include 'Analytics.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$message = "";
$messageType = "";

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

$logger = new Logger($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. USER: UPDATE PROFILE
    if (isset($_POST['update_profile']) && !$isAdmin) {
        $userObj = new User($conn);
        $res = $userObj->updateProfile(
            $_SESSION['user_id'],
            $_POST['confirm_email'],
            $_POST['confirm_password'],
            $_POST['new_username'],
            !empty($_POST['new_password']) ? $_POST['new_password'] : null
        );
        $message = $res['message'];
        $messageType = $res['status'];
        if($res['status'] == 'success') {
            $_SESSION['username'] = $_POST['new_username']; 
        }
    }

    // 2. USER: REMOVE SAVED ROUTE
    if (isset($_POST['delete_route']) && !$isAdmin) {
        $savedObj = new SavedRoute($conn);
        if ($savedObj->remove($_SESSION['user_id'], $_POST['pickup_id'], $_POST['dropoff_id'])) {
            $message = "Route removed from favorites.";
            $messageType = "success";
        } else {
            $message = "Failed to remove route.";
            $messageType = "error";
        }
    }

    // 3. ADMIN: ADD STOP
    if (isset($_POST['add_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        $name = $_POST['stop_name'];
        if ($stopObj->addStop($name, $_POST['latitude'], $_POST['longitude'])) {
            $message = "Bus stop added successfully!";
            $messageType = "success";
            $logger->log("ADD_STOP", "Added new bus stop: $name");
        } else {
            $message = "Failed to add bus stop.";
            $messageType = "error";
        }
    }

    // 4. ADMIN: MODIFY STOP
    if (isset($_POST['edit_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        $name = $_POST['stop_name'];
        if ($stopObj->updateStop($_POST['stop_id'], $name, $_POST['latitude'], $_POST['longitude'])) {
            $message = "Bus stop updated successfully!";
            $messageType = "success";
            $logger->log("UPDATE_STOP", "Updated details for stop ID: " . $_POST['stop_id'] . " ($name)");
        } else {
            $message = "Failed to update bus stop.";
            $messageType = "error";
        }
    }

    // 5. ADMIN: DELETE STOP
    if (isset($_POST['delete_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        $id = $_POST['stop_id'];
        $name = $stopObj->getName($id);
        
        if ($stopObj->deleteStop($id)) {
            $message = "Bus stop deleted successfully!";
            $messageType = "success";
            $logger->log("DELETE_STOP", "Deleted bus stop: $name (ID: $id)");
        } else {
            $message = "Failed to delete stop. It might be in use.";
            $messageType = "error";
            $logger->log("ERROR", "Failed attempt to delete stop ID: $id");
        }
    }
}

// --- FETCH DATA ---
$recent_trip = null;
$mySavedRoutes = [];
$stops = [];
$users = [];
$logs = [];
$analyticsData = [];

if ($isAdmin) {
    $stopObj = new BusStop($conn);
    $stops = $stopObj->getAll();
    $userObj = new User($conn);
    $users = $userObj->getAllUsers();
    $logs = $logger->getLogs();
    $analytics = new Analytics($conn);
    $analyticsData['saved'] = $analytics->getTopSavedRoutes();
    $analyticsData['trips'] = $analytics->getFrequentTrips();
    $analyticsData['ages'] = $analytics->getUserDemographics();
    $analyticsData['total_users'] = $analytics->getTotalUsers();
} else {
    $trip = new Trip($conn);
    $recent_trip = $trip->getMostRecent($_SESSION['user_id']);
    $savedObj = new SavedRoute($conn);
    $mySavedRoutes = $savedObj->getAll($_SESSION['user_id']);
    $rawEmail = $_SESSION['email'];
    $atPos = strpos($rawEmail, "@");
    $maskedEmail = substr($rawEmail, 0, 1) . "*****" . substr($rawEmail, $atPos);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Bustle</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="profile_animation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        /* --- GLOBAL RESET FOR LAYOUT SAFETY --- */
        * { box-sizing: border-box; }

        /* --- General Layout --- */
        body { background-color: #f4f6f8; }

        /* --- Saved Routes List Styles --- */
        .saved-route-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff8e1;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #ffe0b2;
            transition: transform 0.2s;
        }
        .saved-route-item:hover {
            transform: translateX(5px);
            border-color: #FF9A00;
        }
        .route-names {
            font-weight: bold;
            color: #4F200D;
        }
        .route-arrow {
            color: #FF9A00;
            margin: 0 8px;
        }
        .remove-star-btn {
            background: none;
            border: none;
            color: #FF9A00;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            transition: transform 0.2s, color 0.2s;
        }
        .remove-star-btn:hover {
            transform: scale(1.2);
            color: #e65100;
        }

        /* --- Analytics Styles (FIXED FOR MOBILE) --- */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            border-left: 6px solid #FF9A00;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0; 
        }
        .stat-card h4 { 
            margin-top: 0; 
            color: #4F200D; 
            font-size: 1.1rem; 
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            display: block; 
        }
        .bar-container { margin-top: 10px; }
        .bar-row { display: flex; align-items: center; margin-bottom: 12px; font-size: 0.9rem; }
        .bar-label { width: 110px; color: #555; }
        .bar-bg { flex: 1; background: #f0f0f0; height: 12px; border-radius: 6px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #FF9A00, #ffb74d); border-radius: 6px; }
        .bar-val { width: 30px; text-align: right; font-weight: bold; margin-left: 8px; color: #4F200D; }

        /* --- Log Styles --- */
        .log-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .log-item:last-child { border-bottom: none; }
        .log-action { font-weight: 800; color: #4F200D; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .log-desc { color: #555; font-size: 0.95rem; margin-top: 4px; }
        .log-date { font-size: 0.75rem; color: #999; white-space: nowrap; margin-left: 10px; }

        /* --- Lively Bus Stop Management Styles --- */
        .add-stop-panel {
            background: linear-gradient(135deg, #ffffff 0%, #fff8e1 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 1px solid #ffe0b2;
            position: relative;
            overflow: hidden;
        }
        .add-stop-panel::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 100px; height: 100px;
            background: #FF9A00;
            opacity: 0.1;
            border-radius: 0 0 0 100%;
        }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-header h3 { margin: 0; color: #4F200D; font-size: 1.3rem; }
        
        .modern-form { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .modern-input-group { position: relative; flex: 1; min-width: 200px; }
        .modern-input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #FF9A00; }
        .modern-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #eee;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s;
            outline: none;
        }
        .modern-input:focus { border-color: #FF9A00; box-shadow: 0 4px 10px rgba(255,154,0,0.1); }
        .btn-add {
            background: #4F200D;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .btn-add:hover { transform: translateY(-2px); background: #333; }

        .search-bar-container {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stop-count-badge {
            background: #FF9A00; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;
        }

        .lively-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .lively-table thead th {
            text-align: left;
            padding: 15px;
            color: #888;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .lively-table tbody tr {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .lively-table tbody tr:hover {
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            z-index: 10;
            position: relative;
        }
        .lively-table td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid #f9f9f9;
            border-bottom: 1px solid #f9f9f9;
        }
        .lively-table td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid #f9f9f9; }
        .lively-table td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid #f9f9f9; }

        .coord-badge {
            background: #eee;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8rem;
            color: #555;
        }
        .action-icon-btn {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .action-icon-btn.edit { color: #2196F3; }
        .action-icon-btn.edit:hover { background: rgba(33, 150, 243, 0.1); }
        .action-icon-btn.delete { color: #f44336; }
        .action-icon-btn.delete:hover { background: rgba(244, 67, 54, 0.1); }
        .action-icon-btn.save { color: #4CAF50; display: none; }
        
        /* Edit Mode Styles */
        .editable-group { display: none; flex-direction: column; gap: 5px; }
        
        tr.editing .editable-group { display: flex; }
        tr.editing .editable-input-field { 
            border: 1px solid #ccc; background: white; padding: 5px; border-radius: 4px; width: 100%; box-sizing: border-box;
        }
        tr.editing .static-text { display: none; }
        tr:not(.editing) .editable-input-field { display: none; } 
        tr.editing .action-icon-btn.edit { display: none; }
        tr.editing .action-icon-btn.save { display: inline-block; }


        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            #navBar {
                position: absolute; top: 100%; left: 0; width: 100%; background: white;
                flex-direction: column; max-height: 0; overflow: hidden;
                transition: 0.4s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.15); z-index: 9999; padding: 0;
            }
            #navBar.active { max-height: 400px; padding: 20px 0; }
            
            .tab-nav { flex-wrap: wrap; justify-content: center; }
            .tab-btn { margin-bottom: 5px; flex: 1 1 40%; text-align: center; }
            
            .modern-form { flex-direction: column; }
            
            .modern-input-group { width: 100%; margin-bottom: 15px; } 
            .btn-add { width: 100%; }
            
            /* FORCE SINGLE COLUMN FOR ANALYTICS ON MOBILE */
            .stat-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <div class="Navigation">
      <div id="logo">
        <a href="index.php">Bustle</a>
      </div>

      <div class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <div id="navBar">
        <a href="index.php" class="Home">Home</a>
        <?php if(!$isAdmin): ?>
            <a href="Tracker.php" class="tracker">Calculator</a>
        <?php endif; ?>
        
        <div class="dropdown">
            <button class="dropbtn">
                <i class="fa-solid fa-user"></i> 
                <?php echo htmlspecialchars($_SESSION['username']); ?> 
                <i class="fa-solid fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="Profile.php"><?php echo $isAdmin ? 'Dashboard' : 'Profile'; ?></a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
      </div>
    </div>
</header>

<div class="profile-container">
    
    <?php if(!empty($message)): ?>
        <div class="anim-enter pop-in" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align:center; color: white; background: <?php echo $messageType == 'success' ? '#4CAF50' : '#f44336'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if($isAdmin): ?>
        <div class="profile-card anim-enter slide-up delay-1">
            <div class="profile-header">
                <div class="profile-avatar" style="background:#4F200D;">A</div>
                <div class="profile-details">
                    <h2>Administrator Dashboard</h2>
                    <p>Manage System Data & View Analytics</p>
                </div>
            </div>

            <div class="tab-nav">
                <button class="tab-btn active" onclick="openTab('stops')">Bus Stops</button>
                <button class="tab-btn" onclick="openTab('users')">Users</button>
                <button class="tab-btn" onclick="openTab('analytics')">Datasheet</button>
                <button class="tab-btn" onclick="openTab('logs')">History</button>
            </div>

            <div id="stops" class="tab-content active">
                
                <div class="add-stop-panel">
                    <div class="panel-header">
                        <h3>Add New Stop</h3>
                        <i class="fa-solid fa-map-location-dot" style="font-size: 1.5rem; color:#FF9A00; opacity:0.5;"></i>
                    </div>
                    <form method="POST" class="modern-form">
                        <div class="modern-input-group">
                            <i class="fa-solid fa-signature"></i>
                            <input type="text" name="stop_name" class="modern-input" placeholder="Stop Name (e.g. IT Park)" required>
                        </div>
                        <div class="modern-input-group">
                            <i class="fa-solid fa-location-crosshairs"></i>
                            <input type="text" name="latitude" class="modern-input" placeholder="Latitude" required>
                        </div>
                        <div class="modern-input-group">
                            <i class="fa-solid fa-location-crosshairs"></i>
                            <input type="text" name="longitude" class="modern-input" placeholder="Longitude" required>
                        </div>
                        <button type="submit" name="add_stop" class="btn-add">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </form>
                </div>

                <div class="search-bar-container">
                    <div style="position:relative; width: 100%; max-width: 300px;">
                        <input type="text" id="stopSearch" onkeyup="filterStops()" placeholder="Search stops..." 
                               style="padding:10px 10px 10px 35px; border-radius:20px; border:1px solid #ccc; width:100%; font-family:'Outfit';">
                        <i class="fa-solid fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#999;"></i>
                    </div>
                    <span class="stop-count-badge"><?php echo count($stops); ?> Stops</span>
                </div>

                <div style="overflow-x:auto;">
                    <table class="lively-table" id="stopsTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Stop Name</th>
                                <th>Coordinates</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stops as $stop): ?>
                            <tr id="row-<?php echo $stop['stop_id']; ?>">
                                <form method="POST">
                                    <td style="color:#aaa; font-weight:bold;">#<?php echo $stop['stop_id']; ?></td>
                                    
                                    <td>
                                        <span class="static-text" style="font-weight:600; color:#333;"><?php echo htmlspecialchars($stop['stop_name']); ?></span>
                                        <input type="text" name="stop_name" class="editable-input-field" value="<?php echo htmlspecialchars($stop['stop_name']); ?>">
                                    </td>
                                    
                                    <td>
                                        <div class="static-text">
                                            <span class="coord-badge">Lat: <?php echo $stop['latitude']; ?></span>
                                            <span class="coord-badge">Lng: <?php echo $stop['longitude']; ?></span>
                                        </div>
                                        <div class="editable-group"> 
                                            <input type="text" name="latitude" class="editable-input-field" value="<?php echo $stop['latitude']; ?>" placeholder="Lat">
                                            <input type="text" name="longitude" class="editable-input-field" value="<?php echo $stop['longitude']; ?>" placeholder="Lng">
                                        </div>
                                    </td>
                                    
                                    <td style="text-align:right;">
                                        <input type="hidden" name="stop_id" value="<?php echo $stop['stop_id']; ?>">
                                        
                                        <button type="button" class="action-icon-btn edit" onclick="toggleEdit(<?php echo $stop['stop_id']; ?>)" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="submit" name="edit_stop" class="action-icon-btn save" title="Save Changes">
                                            <i class="fa-solid fa-check"></i>
                                        </button>

                                        <button type="submit" name="delete_stop" class="action-icon-btn delete" onclick="return confirm('Delete this stop?');" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="users" class="tab-content">
                <h3>Registered Users (<?php echo count($users); ?>)</h3>
                <div class="table-responsive">
                    <table class="lively-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Birthdate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td style="color:#aaa; font-weight:bold;">#<?php echo $u['user_id']; ?></td>
                                <td style="font-weight:bold;"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['birthdate']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="analytics" class="tab-content">
                <h3>System Datasheet</h3>
                
                <div class="stat-grid">
                    <div class="stat-card">
                        <h4>User Demographics</h4>
                        <div class="bar-container">
                            <?php 
                            $total = $analyticsData['total_users'] > 0 ? $analyticsData['total_users'] : 1;
                            foreach($analyticsData['ages'] as $label => $count): 
                                $pct = ($count / $total) * 100;
                            ?>
                                <div class="bar-row">
                                    <div class="bar-label"><?php echo $label; ?></div>
                                    <div class="bar-bg">
                                        <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                    <div class="bar-val"><?php echo $count; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h4>Most Frequent Routes</h4>
                        <div class="table-responsive">
                            <?php if(empty($analyticsData['trips'])): ?>
                                <p style="color:#888;">No data available yet.</p>
                            <?php else: ?>
                                <table class="lively-table" style="font-size:0.85rem;">
                                    <thead><tr><th>Start</th><th>End</th><th>Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach($analyticsData['trips'] as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['pickup']) ?></td>
                                            <td><?= htmlspecialchars($t['dropoff']) ?></td>
                                            <td style="font-weight:bold;"><?= $t['count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h4>Most Saved Routes</h4>
                        <div class="table-responsive">
                            <?php if(empty($analyticsData['saved'])): ?>
                                <p style="color:#888;">No data available yet.</p>
                            <?php else: ?>
                                <table class="lively-table" style="font-size:0.85rem;">
                                    <thead><tr><th>Start</th><th>End</th><th>Saves</th></tr></thead>
                                    <tbody>
                                    <?php foreach($analyticsData['saved'] as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['pickup']) ?></td>
                                            <td><?= htmlspecialchars($s['dropoff']) ?></td>
                                            <td style="font-weight:bold; color:#FF9A00;">★ <?= $s['count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="logs" class="tab-content">
                <h3>History of Changes</h3>
                <div style="background:white; border:1px solid #eee; padding:20px; border-radius:10px; max-height:400px; overflow-y:auto;">
                    <?php if(empty($logs)): ?>
                        <p style="color:#999; text-align:center;">No history recorded yet.</p>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <div class="log-item">
                                <div>
                                    <div class="log-action">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                        <span style="font-weight:normal; color:#ccc; font-size:0.8rem; margin-left:5px;">●</span>
                                        <span style="font-weight:normal; color:#888; font-size:0.8rem; text-transform:none;"> <?php echo htmlspecialchars($log['admin_username']); ?></span>
                                    </div>
                                    <div class="log-desc"><?php echo htmlspecialchars($log['description']); ?></div>
                                </div>
                                <div class="log-date">
                                    <?php echo date('M j, H:i', strtotime($log['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <?php else: ?>
        
        <div class="profile-card anim-enter slide-up delay-1">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($maskedEmail); ?></p>
                    <p><i class="fa-solid fa-cake-candles"></i> <?php echo htmlspecialchars($_SESSION['birthdate']); ?></p>
                    <p><span style="background:#eee; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><?php echo isset($_SESSION['discount_type']) ? $_SESSION['discount_type'] : 'Regular'; ?></span></p>
                </div>
            </div>
        </div>
        
         <div class="profile-card anim-enter slide-up delay-2">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Recently Viewed Trip</h3>
            <?php if($recent_trip): ?>
                <div style="background:#FFF8E1; padding:20px; border-radius:10px; border-left: 5px solid #FF9A00;">
                    <p><strong>From:</strong> <?php echo htmlspecialchars($recent_trip['pickup_name']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($recent_trip['dropoff_name']); ?></p>
                    <p><strong>Fare:</strong> <span style="font-size:1.2rem; font-weight:bold; color:#4F200D;">₱ <?php echo number_format($recent_trip['fare_amount'], 0); ?></span></p>
                </div>
            <?php else: ?>
                <p style="color:#888; font-style:italic;">No recent trips found.</p>
            <?php endif; ?>
        </div>

        <div class="profile-card anim-enter slide-up delay-3">
            <h3><i class="fa-solid fa-star"></i> My Saved Routes</h3>
            <?php if(!empty($mySavedRoutes)): ?>
                <div style="margin-top: 15px;">
                    <?php foreach($mySavedRoutes as $route): ?>
                        <div class="saved-route-item">
                            <span class="route-names">
                                <?= htmlspecialchars($route['pickup_name']) ?> 
                                <i class="fa-solid fa-arrow-right route-arrow"></i> 
                                <?= htmlspecialchars($route['dropoff_name']) ?>
                            </span>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="pickup_id" value="<?= $route['pickup_stop_id'] ?>">
                                <input type="hidden" name="dropoff_id" value="<?= $route['dropoff_stop_id'] ?>">
                                <button type="submit" name="delete_route" class="remove-star-btn" title="Click to remove from saved">
                                    <i class="fa-solid fa-star"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#888; font-style:italic; margin-top:10px;">You haven't saved any routes yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="profile-card anim-enter slide-up delay-4">
            <h3>Update Profile</h3>
            <form method="POST">
                <div class="form-group">
                    <label>New Username</label>
                    <input type="text" name="new_username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>New Password (Optional)</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
                <hr style="margin: 20px 0; border:0; border-top:1px solid #eee;">
                <div class="form-group">
                    <label>Confirm Email</label>
                    <input type="email" name="confirm_email" placeholder="Enter your registered email" required>
                </div>
                <div class="form-group">
                    <label>Confirm Current Password</label>
                    <input type="password" name="confirm_password" placeholder="Enter current password" required>
                </div>
                <button type="submit" name="update_profile" class="action-btn">Save Changes</button>
            </form>
        </div>

    <?php endif; ?>
</div>

<script>
    const menuToggle = document.getElementById('menu-toggle');
    const navBar = document.getElementById('navBar');
    
    if(menuToggle && navBar) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navBar.classList.toggle('active');
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            openTab(tab);
        }
    });

    function openTab(tabName) {
        var i;
        var x = document.getElementsByClassName("tab-content");
        var btns = document.getElementsByClassName("tab-btn");
        for (i = 0; i < x.length; i++) {
            x[i].style.display = "none"; 
            x[i].classList.remove("active");
        }
        for (i = 0; i < btns.length; i++) {
            btns[i].classList.remove("active");
        }
        var target = document.getElementById(tabName);
        if(target) { target.style.display = "block"; target.classList.add("active"); }
        
        for (i = 0; i < btns.length; i++) {
            if(btns[i].getAttribute('onclick').includes("'" + tabName + "'")) {
                btns[i].classList.add("active");
            }
        }
    }

    function filterStops() {
        const input = document.getElementById("stopSearch");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("stopsTable");
        const tr = table.getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName("td")[1];
            if (tdName) {
                const txtValue = tdName.textContent || tdName.innerText;
                tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }       
        }
    }

    function toggleEdit(stopId) {
        const row = document.getElementById('row-' + stopId);
        row.classList.toggle('editing');
    }

    document.addEventListener('click', (e) => {
        const dropBtn = e.target.closest('.dropbtn');
        const dropContent = e.target.closest('.dropdown-content');
        if (dropBtn) {
            const dropdown = dropBtn.closest('.dropdown');
            dropdown.classList.toggle('active');
            e.stopPropagation();
        } else if (!dropContent) {
            document.querySelectorAll('.dropdown.active').forEach(d => d.classList.remove('active'));
        }
    });
</script>

</body>
</html>