<?php
session_start();
include 'Database.php';
include 'Trip.php';
include 'User.php';
include 'BusStop.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$message = "";
$messageType = "";

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// --- HANDLE FORM SUBMISSIONS ---
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
            $_SESSION['username'] = $_POST['new_username']; // Update session
        }
    }

    // 2. ADMIN: ADD STOP
    if (isset($_POST['add_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        if ($stopObj->addStop($_POST['stop_name'], $_POST['latitude'], $_POST['longitude'])) {
            $message = "Bus stop added successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add bus stop.";
            $messageType = "error";
        }
    }

    // 3. ADMIN: MODIFY STOP
    if (isset($_POST['edit_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        if ($stopObj->updateStop($_POST['stop_id'], $_POST['stop_name'], $_POST['latitude'], $_POST['longitude'])) {
            $message = "Bus stop updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update bus stop.";
            $messageType = "error";
        }
    }

    // 4. ADMIN: DELETE STOP
    if (isset($_POST['delete_stop']) && $isAdmin) {
        $stopObj = new BusStop($conn);
        if ($stopObj->deleteStop($_POST['stop_id'])) {
            $message = "Bus stop deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete stop. It might be in use by saved routes or history.";
            $messageType = "error";
        }
    }
}

// --- FETCH DATA ---
$recent_trip = null;
$stops = [];
$users = [];

if ($isAdmin) {
    $stopObj = new BusStop($conn);
    $stops = $stopObj->getAll();
    
    $userObj = new User($conn);
    $users = $userObj->getAllUsers();
} else {
    $trip = new Trip($conn);
    $recent_trip = $trip->getMostRecent($_SESSION['user_id']);
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
</head>
<body>

<!-- NAVBAR -->
<header>
    <div class="Navigation">
      <div id="logo">
        <a href="index.php">Bustle</a>
      </div>
      <div id="navBar">
        <a href="index.php" class="Home">Home</a>
        <?php if(!$isAdmin): ?>
            <a href="Tracker.php" class="tracker">Tracker</a>
        <?php endif; ?>
        
        <div class="dropdown">
            <button class="dropbtn">
                <i class="fa-solid fa-user"></i> 
                <?php echo htmlspecialchars($_SESSION['username']); ?> 
                <i class="fa-solid fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="Profile.php">Profile</a>
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

    <!-- ================= ADMIN VIEW ================= -->
    <?php if($isAdmin): ?>
        <div class="profile-card anim-enter slide-up delay-1">
            <div class="profile-header">
                <div class="profile-avatar" style="background:#4F200D;">A</div>
                <div class="profile-details">
                    <h2>Administrator Dashboard</h2>
                    <p>Manage System Data</p>
                </div>
            </div>

            <div class="tab-nav">
                <button class="tab-btn active" onclick="openTab('stops')">Bus Stops</button>
                <button class="tab-btn" onclick="openTab('users')">Users List</button>
            </div>

            <!-- TAB 1: BUS STOPS -->
            <div id="stops" class="tab-content active">
                <h3>Add New Stop</h3>
                <form method="POST" style="background:#f9f9f9; padding:20px; border-radius:10px;">
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" name="stop_name" placeholder="Stop Name" required style="flex:2; padding:10px;">
                        <input type="text" name="latitude" placeholder="Latitude" required style="flex:1; padding:10px;">
                        <input type="text" name="longitude" placeholder="Longitude" required style="flex:1; padding:10px;">
                        <button type="submit" name="add_stop" class="action-btn">Add</button>
                    </div>
                </form>

                <h3>Current Stops</h3>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Lat</th>
                                <th>Lng</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stops as $stop): ?>
                            <tr>
                                <form method="POST">
                                    <td><?php echo $stop['stop_id']; ?></td>
                                    <td><input type="text" name="stop_name" value="<?php echo htmlspecialchars($stop['stop_name']); ?>"></td>
                                    <td><input type="text" name="latitude" value="<?php echo $stop['latitude']; ?>" size="8"></td>
                                    <td><input type="text" name="longitude" value="<?php echo $stop['longitude']; ?>" size="8"></td>
                                    
                                    <!-- FIXED ALIGNMENT: Removed flex, added margins and vertical-align -->
                                    <td>
                                        <input type="hidden" name="stop_id" value="<?php echo $stop['stop_id']; ?>">
                                        <button type="submit" name="edit_stop" style="background:none; border:none; color:blue; cursor:pointer; margin-right: 10px; vertical-align: middle;">Update</button>
                                        <button type="submit" name="delete_stop" style="background:none; border:none; color:red; cursor:pointer; vertical-align: middle;" onclick="return confirm('Are you sure you want to delete this stop?');">Delete</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: USERS -->
            <div id="users" class="tab-content">
                <h3>Registered Users</h3>
                <table class="admin-table">
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
                            <td><?php echo $u['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['birthdate']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ================= USER VIEW ================= -->
    <?php else: ?>
        
        <!-- INFO CARD -->
        <div class="profile-card anim-enter slide-up delay-1">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><i class="fa-solid fa-cake-candles"></i> <?php echo htmlspecialchars($_SESSION['birthdate']); ?></p>
                    <p><span style="background:#eee; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><?php echo isset($_SESSION['discount_type']) ? $_SESSION['discount_type'] : 'Regular'; ?></span></p>
                </div>
            </div>
        </div>

        <!-- RECENT TRIP -->
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

        <!-- UPDATE SETTINGS -->
        <div class="profile-card anim-enter slide-up delay-3">
            <h3>Update Profile</h3>
            <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">Confirm your email and current password to make changes.</p>
            
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
        document.getElementById(tabName).style.display = "block";  
        document.getElementById(tabName).classList.add("active");
        event.currentTarget.classList.add("active");
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