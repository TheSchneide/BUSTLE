<?php
session_start();
include 'Database.php';
include 'Trip.php'; 
include 'SavedRoute.php'; 
include_once 'Analytics.php';
include_once 'Logger.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

$recent_trip = null;
$saved_route = null;
$adminStats = [];
$recentLogs = [];

$db = new Database();
$conn = $db->getConnection(); 

if ($isLoggedIn) {
    if (!$isAdmin) {
        $trip = new Trip($conn);
        $recent_trip = $trip->getMostRecent($_SESSION['user_id']);
        $savedRouteObj = new SavedRoute($conn);
        $all_saved_routes = $savedRouteObj->getAll($_SESSION['user_id']);
    } else {
        $analytics = new Analytics($conn);
        $logger = new Logger($conn);

        $adminStats['total_users'] = $analytics->getTotalUsers();
        
        $topRoutes = $analytics->getTopSavedRoutes();
        
        // --- UPDATED LOGIC HERE ---
        if (!empty($topRoutes)) {
            $adminStats['top_route_count'] = $topRoutes[0]['count'];
            // Create a string like "IT Park ➝ Mactan Newtown"
            $adminStats['top_route_desc'] = htmlspecialchars($topRoutes[0]['pickup']) . " <span style='color:#FF9A00'>➝</span> " . htmlspecialchars($topRoutes[0]['dropoff']);
        } else {
            $adminStats['top_route_count'] = 0;
            $adminStats['top_route_desc'] = "No saved routes yet";
        }
        // --------------------------
        
        $allLogs = $logger->getLogs();
        $recentLogs = array_slice($allLogs, 0, 3);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bustle</title>
  <link rel="stylesheet" href="index.css">
  <link rel="icon" type="image/x-icon" href="busFavicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="index.js" defer></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Caprasimo&family=DM+Serif+Display:ital@0;1&family=Outfit:wght@200&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

    header {
      position: relative;
      z-index: 1000;
      background-color: #fff; 
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    /* --- ADMIN DASHBOARD STYLES --- */
    .admin-dashboard {
        width: 90%;
        max-width: 1000px;
        margin: 40px auto;
        font-family: 'Outfit', sans-serif;
    }
    
    .admin-welcome {
        text-align: left;
        margin-bottom: 30px;
    }
    
    .admin-welcome h1 {
        font-family: 'Caprasimo', cursive;
        color: #4F200D;
        font-size: 2.5rem;
        margin-bottom: 5px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .dash-card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .dash-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(255, 154, 0, 0.15);
        border-color: #FF9A00;
    }

    .dash-icon {
        font-size: 2rem;
        color: #FF9A00;
        margin-bottom: 15px;
    }

    .dash-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #4F200D;
        line-height: 1;
    }

    .dash-label {
        color: #666;
        font-size: 1rem;
        font-weight: 500;
    }

    .activity-feed {
        background: #fff8e1;
        border-radius: 16px;
        padding: 25px;
        border: 1px solid #ffe0b2;
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        color: #4F200D;
        font-weight: bold;
    }

    .feed-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(79, 32, 13, 0.1);
    }
    
    .feed-item:last-child { border-bottom: none; }

    .feed-dot {
        width: 10px;
        height: 10px;
        background: #FF9A00;
        border-radius: 50%;
    }

    .feed-text { font-size: 0.95rem; color: #333; flex: 1; }
    .feed-time { font-size: 0.8rem; color: #888; }

    .quick-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .qa-btn {
        background: #4F200D;
        color: white;
        padding: 15px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .qa-btn:hover { background: #333; color: white; }
    
    /* --- EXISTING STYLES REUSED --- */
    .menu-toggle {
      display: none;
      flex-direction: column;
      justify-content: space-between;
      width: 25px;
      height: 18px;
      cursor: pointer;
      z-index: 1101;
    }

    .menu-toggle span {
      display: block;
      height: 3px;
      width: 100%;
      background-color: #333;
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    .menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(4px, 4px); }
    .menu-toggle.active span:nth-child(2) { opacity: 0; }
    .menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(4px, -4px); }
    
    .currentBus {
      background-color: #fefefe;
      margin: 30px auto;
      padding: 50px;
      border: 1px solid #888;
      border-radius: 2rem;
      width: 80%;
      text-align: left;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .currentBus:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      border-color: #FF9A00;
    }

    .savedRouteBtn {
        background-color: #FF9A00; 
        color: white;
        margin: 20px auto;
        padding: 30px;
        border-radius: 2rem;
        width: 80%;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.2s;
        border: 2px solid #e08900;
    }
    .savedRouteBtn:hover { transform: scale(1.02); background-color: #ff8c00; }
    .savedRouteBtn h3 { margin: 0; font-family: Caprasimo; font-size: 1.5rem; }
    .savedRouteBtn p { font-size: 1rem; margin-top: 5px; color: #4F200D; font-weight: bold; }

    @media (max-width: 768px) {
      #navBar {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        background-color: white;
        position: absolute;
        top: 100%; 
        left: 0;
        width: 100%;
        overflow: hidden;
        max-height: 0;
        transition: 0.4s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      }

      #navBar.active { max-height: 300px; padding-top: 20px; padding-bottom: 20px; }
      .menu-toggle { display: flex; }
      .Content { padding: 40px 20px; text-align: center; gap: 2rem; }
      .Content img { width: 80%; height: auto; }
      .info { flex-direction: column; align-items: center; text-align: center; padding: 20px; }
      .info-text, .info-img { width: 100%; }
      .info-img img { width: 90%; height: auto; }
      .busInfo, .currentBus { padding: 25px; border-radius: 1.5rem; }
      .aboutUs { padding: 40px 20px; }
      footer { flex-direction: column; gap: 8px; padding: 15px 0; }
    }

    /* --- NEW USER DASHBOARD STYLES --- */
    .user-dashboard {
        width: 100%;
        max-width: 1100px;
        margin: 20px auto;
        padding: 0 15px; /* Slightly reduced padding */
        text-align: left;
        box-sizing: border-box; /* PREVENTS OVERFLOW */
    }

    /* Apply border-box to all dashboard elements to prevent size errors */
    .user-dashboard * {
        box-sizing: border-box;
    }

    .welcome-header {
        margin-bottom: 30px;
        position: relative;
    }

    .welcome-header h1 {
        font-family: 'Caprasimo', cursive;
        font-size: 3rem;
        color: #4F200D;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .welcome-header p {
        color: #888;
        font-size: 1.1rem;
        font-weight: 400;
    }

    .highlight-name {
        color: #FF9A00;
        position: relative;
        display: inline-block;
    }

    .dashboard-grid-user {
        display: grid;
        /* Changed 300px to 280px to fit smaller phones */
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 20px;
        margin-bottom: 40px;
        width: 100%; /* Ensure it stays within parent */
    }

    .action-card {
        background: white;
        border-radius: 20px;
        padding: 25px; /* Slightly reduced for mobile fit */
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 180px;
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(255, 154, 0, 0.15);
        border-color: #FF9A00;
    }

    .card-icon-bg {
        position: absolute;
        top: -10px;
        right: -10px;
        font-size: 8rem;
        color: rgba(255, 154, 0, 0.05);
        transform: rotate(-15deg);
        z-index: 0;
    }

    .card-content {
        position: relative;
        z-index: 1;
    }

    .card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #4F200D;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-data {
        font-size: 1.1rem;
        color: #555;
        line-height: 1.5;
    }

    .card-data strong { color: #333; }

    .card-action {
        margin-top: 20px;
        color: #FF9A00;
        font-weight: bold;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Saved Routes Section */
    .saved-routes-section h3 {
        font-family: 'Outfit', sans-serif;
        color: #4F200D;
        font-size: 1.5rem;
        margin-bottom: 20px;
        border-left: 5px solid #FF9A00;
        padding-left: 15px;
    }

    .routes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .route-ticket {
        background: white;
        border: 2px dashed #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }

    .route-ticket::before, .route-ticket::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 20px;
        height: 20px;
        background-color: #f4f6f8; /* Matches body bg if needed */
        border-radius: 50%;
        transform: translateY(-50%);
    }

    .route-ticket::before { left: -12px; }
    .route-ticket::after { right: -12px; }

    .route-ticket:hover {
        border-color: #FF9A00;
        background: #fff8e1;
        transform: scale(1.02);
    }

    .route-names {
        font-weight: 700;
        color: #4F200D;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .route-arrow { color: #FF9A00; font-size: 0.9rem; margin: 0 5px; }

    @media (max-width: 768px) {
        .Content {
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
            overflow-x: hidden; /* Hide any accidental scroll */
        }
        
        .user-dashboard {
            width: 95%; /* Leave a tiny gap on edges */
            margin: 10px auto;
        }
        .welcome-header h1 { font-size: 2.2rem; }
        .dashboard-grid-user { grid-template-columns: 1fr; }
        #navBar {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        background-color: white;
        position: absolute;
        top: 100%; 
        left: 0;
        width: 100%;
        overflow: hidden;
        max-height: 0;
        transition: 0.4s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        z-index: 9999;
        padding: 0; /* Remove container padding */
        gap: 0;     /* Remove flex gap, use item padding instead */
      }

      #navBar.active {
        max-height: 500px;
      }

      /* 1. Make direct links (Home, Calculator) look exactly like dropdown items */
      #navBar > a {
        display: block;
        width: 100%;
        padding: 15px 0;
        color: #4F200D;
        text-decoration: none;
        font-weight: 700;
      }

      /* 2. Flatten the Dropdown Container */
      .dropdown {
        width: 100%;
        display: block;
        margin: 0;
        padding: 0;
      }

      .dropbtn {
        display: none !important; /* Hide the User Button */
      }

      .dropdown-content {
        display: block !important; /* Force show content */
        position: static;
        width: 100%;
        background: transparent;
        box-shadow: none;
      }

      /* 3. Ensure dropdown links match the direct links */
      .dropdown-content a {
        display: block;
        width: 100%;
        padding: 15px 0;
        text-align: center;
        color: #4F200D;
        font-weight: 700;
      }
    }
  </style>
</head>
<body>
  <div id="loading-screen">
    <div class="loading"></div>
  </div>

  <header>
    <div class="Navigation">
      <div id="logo" class="hidden-text" data-anim="fade-up">
        <a href="#index.php">Bustle</a>
      </div>

      <div class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <div id="navBar" class="hidden-text" data-anim="fade-up">
        <a href="#index.php" class="Home">Home</a>

        <?php if($isLoggedIn): ?>
          
          <?php if(!$isAdmin): ?>
            <a href="Tracker.php" class="tracker">Calculator</a>
          <?php endif; ?>

          <div class="dropdown">
            <button class="dropbtn">
                <i class="fa-solid fa-user"></i> 
                <?php echo htmlspecialchars($username); ?> 
                <i class="fa-solid fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="Profile.php"><?php echo $isAdmin ? 'Dashboard' : 'Profile'; ?></a>
                <a href="logout.php">Logout</a>
            </div>
          </div>

        <?php else: ?>
          <a href="login.html" class="tracker">Calculator</a>
          <a href="login.html">Login</a>
          <a href="Register.html" class="SignUp">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="Content">
      <?php if($isLoggedIn): ?>
        
        <?php if($isAdmin): ?>
            <div class="admin-dashboard hidden-text" data-anim="fade-up">
                
                <div class="admin-welcome">
                    <h1>Welcome Back, Admin.</h1>
                    <p>Here is your system overview for today.</p>
                </div>

                <div class="dashboard-grid">
                    <div class="dash-card">
                        <div>
                            <i class="fa-solid fa-users dash-icon"></i>
                            <div class="dash-value"><?php echo $adminStats['total_users']; ?></div>
                            <div class="dash-label">Registered Users</div>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div>
                            <div>
                                <i class="fa-solid fa-route dash-icon"></i>
                                <div class="dash-value"><?php echo $adminStats['top_route_count']; ?></div>
                                <div class="dash-label">Saves on Top Route</div>
                            </div>
                            
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; font-size: 0.9rem; color: #4F200D; font-weight: bold;">
                                <?php echo $adminStats['top_route_desc']; ?>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="activity-feed">
                        <div class="activity-header">
                            <span>Recent Activity</span>
                            <i class="fa-solid fa-history"></i>
                        </div>
                        <?php if(empty($recentLogs)): ?>
                            <p style="color:#888; font-style:italic;">No recent changes.</p>
                        <?php else: ?>
                            <?php foreach($recentLogs as $log): ?>
                            <div class="feed-item">
                                <div class="feed-dot"></div>
                                <div class="feed-text">
                                    <strong><?php echo htmlspecialchars($log['action_type']); ?></strong>
                                    <br>
                                    <span style="font-size:0.8rem; color:#666;"><?php echo htmlspecialchars($log['description']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div style="text-align:right; margin-top:10px;">
                            <a href="Profile.php?tab=logs" style="font-size:0.8rem; color:#4F200D; font-weight:bold;">View All History →</a>
                        </div>
                    </div>
                </div>

                <div class="quick-actions">
                    <a href="Profile.php" class="qa-btn"><i class="fa-solid fa-map-pin"></i> Manage Stops</a>
                    <a href="Profile.php?tab=analytics" class="qa-btn"><i class="fa-solid fa-chart-line"></i> View Datasheet</a>
                </div>

            </div>
        
        <?php else: ?>
            
            <div class="user-dashboard hidden-text" data-anim="fade-up">
                
                <div class="welcome-header">
                    <h1>Hello, <span class="highlight-name"><?php echo htmlspecialchars($username); ?></span>!</h1>
                    <p>Where would you like to go today?</p>
                </div>

                <div class="dashboard-grid-user">
                    
                    <div class="action-card" onclick="window.location.href='Tracker.php?autoLocate=true'">
                        <i class="fa-solid fa-location-dot card-icon-bg"></i>
                        <div class="card-content">
                            <div class="card-title">
                                <i class="fa-solid fa-location-crosshairs" style="color:#FF9A00;"></i> Current Location
                            </div>
                            <div class="card-data">
                                <span id="userAddress">Locating...</span>
                                <br>
                                <span id="userCoords" style="font-size: 0.8rem; color: #999;">Waiting for permission...</span>
                            </div>
                        </div>
                        <div class="card-action">
                            Use as Pick-up Point <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>

                    <div class="action-card" onclick="<?php echo $recent_trip ? "window.location.href='Tracker.php?savedPickup=" . $recent_trip['pickup_stop_id'] . "&savedDropoff=" . $recent_trip['dropoff_stop_id'] . "'" : ""; ?>" style="<?php echo !$recent_trip ? "opacity:0.7; cursor:default;" : ""; ?>">
                        <i class="fa-solid fa-clock-rotate-left card-icon-bg"></i>
                        <div class="card-content">
                            <div class="card-title">
                                <i class="fa-solid fa-bus" style="color:#FF9A00;"></i> Last Trip
                            </div>
                            <?php if($recent_trip): ?>
                                <div class="card-data">
                                    <strong>From:</strong> <?php echo htmlspecialchars($recent_trip['pickup_name']); ?><br>
                                    <strong>To:</strong> <?php echo htmlspecialchars($recent_trip['dropoff_name']); ?><br>
                                    <span style="font-size:1.4rem; font-weight:800; color:#4F200D;">₱ <?php echo number_format($recent_trip['fare_amount'], 0); ?></span>
                                </div>
                                <div class="card-action">
                                    Book Again <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            <?php else: ?>
                                <div class="card-data" style="font-style:italic; color:#999; margin-top:10px;">
                                    No recent trips found.<br>
                                    Start calculating your first trip!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <?php if(!empty($all_saved_routes)): ?>
                    <div class="saved-routes-section">
                        <h3><i class="fa-solid fa-star" style="color:#FF9A00; margin-right:10px;"></i> Saved Routes</h3>
                        
                        <div class="routes-grid">
                            <?php foreach($all_saved_routes as $route): ?>
                                <div class="route-ticket" onclick="window.location.href='Tracker.php?savedPickup=<?= $route['pickup_stop_id'] ?>&savedDropoff=<?= $route['dropoff_stop_id'] ?>'">
                                    <div class="route-names">
                                        <?= htmlspecialchars($route['pickup_name']) ?>
                                        <i class="fa-solid fa-arrow-right route-arrow"></i>
                                        <?= htmlspecialchars($route['dropoff_name']) ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:#888; margin-top:5px; text-transform:uppercase; letter-spacing:1px; font-weight:bold;">
                                        Click to Load
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

      <?php else: ?>
        <h1 class="hidden-text" data-anim="fade-up">Your <span class="highlight">No.1</span> Budgetting Solution</h1>
        <p class="hidden-text" data-anim="fade-up">“Plan Your Trip. Perfect Your Budget”</p>
        <img src="bustle.png" id="bustleImg" class="hidden-text" data-anim="fade-up">
        <section class="info">
          <div class="info-text">
            <h1 class="hidden-text" data-anim="fade-left">Don't know how to budget?</h1>
            <h2 class="hidden-text" data-anim="fade-left">Save time and money with <span class="highlight">Bustle!</span></h2>
            <p class="hidden-text" data-anim="fade-left"> 
              Our easy-to-use tool quickly calculates the exact fare for any bus route. 
              Know your budget, plan your journey, and ride with confidence.
            </p>
          </div>
          <div class="info-img">
            <img src="bustle(2).png" alt="students illustration" class="hidden-text" data-anim="fade-left">
          </div>
        </section>
        <hr class="hidden-text" data-anim="fade-up">
        <section class="aboutUs">
          <div class="about">
            <h1 class="hidden-text" data-anim="fade-up">Who are we?</h1>
            <h2 class="hidden-text" data-anim="fade-up">Bustle</h2>
            <p class="hidden-text" data-anim="fade-up">
              Stop guessing your bus fare and start calculating! 
              Bustle provides a fast solution for your daily commute. 
              Enter your route and receive an accurate and precise fare estimate instantly. 
              With just a click of a button, all your budgeting problems are easily solved, 
              letting you ride with complete confidence.
            </p>
          </div>
        </section>
      <?php endif; ?>
  </div>

  <footer>
    <a href="index.php" class="hidden-text" data-anim="fade-up">@Bustle.dcism.org</a>
    <a href="" class="hidden-text" data-anim="fade-up">BustleCrew@gmail.com</a>
    <a href="" class="hidden-text" data-anim="fade-up">+091234567</a>
  </footer>

  <script>
    const toggle = document.getElementById('menu-toggle');
    const navBar = document.getElementById('navBar');

    toggle.addEventListener('click', () => {
      toggle.classList.toggle('active');
      navBar.classList.toggle('active');
    });

    document.addEventListener("DOMContentLoaded", () => {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add("show");
          }
        });
      }, { threshold: 0.2 });

      document.querySelectorAll(".hidden-text").forEach(el => observer.observe(el));
      
      if(document.getElementById('userAddress')) {
          if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(successLoc, errorLoc);
          } else {
              document.getElementById('userAddress').innerText = "Geolocation not supported";
          }
      }
    });

    async function successLoc(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        document.getElementById('userCoords').innerText = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        document.getElementById('userAddress').innerText = "Fetching address...";

        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
            const response = await fetch(url);
            const data = await response.json();
            
            let address = data.display_name;
            if(data.address) {
                 address = (data.address.road || "") + ", " + (data.address.city || data.address.town || data.address.municipality || "");
            }
            document.getElementById('userAddress').innerText = address || data.display_name;
        } catch(e) {
            document.getElementById('userAddress').innerText = "Address unavailable";
        }
    }

    function errorLoc() {
        document.getElementById('userAddress').innerText = "Location permission denied";
        document.getElementById('userCoords').innerText = "";
    }
  </script>
</body>
</html>