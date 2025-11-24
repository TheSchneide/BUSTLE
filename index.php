<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$trip = $_SESSION['recent_trip'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bustle</title>
  <link rel="stylesheet" href="index.css">
  <link rel="icon" type="image/x-icon" href="busFavicon.png">
  <script src="index.js" defer></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Caprasimo&family=DM+Serif+Display:ital@0;1&family=Outfit:wght@200&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

    header {
      position: relative;
      z-index: 1000;
      background-color: #fff; 
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

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

    .menu-toggle.active span:nth-child(1) {
      transform: rotate(45deg) translate(4px, 4px);
    }
    .menu-toggle.active span:nth-child(2) {
      opacity: 0;
    }
    .menu-toggle.active span:nth-child(3) {
      transform: rotate(-45deg) translate(4px, -4px);
    }

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

      #navBar.active {
        max-height: 300px; 
        padding-top: 20px;
        padding-bottom: 20px;
      }

      .menu-toggle {
        display: flex;
      }

      .Content {
        padding: 40px 20px;
        text-align: center;
        gap: 2rem;
      }

      .Content img {
        width: 80%;
        height: auto;
      }

      .info {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 20px;
      }

      .info-text, .info-img {
        width: 100%;
      }

      .info-img img {
        width: 90%;
        height: auto;
      }

      .aboutUs {
        padding: 40px 20px;
      }

      footer {
        flex-direction: column;
        gap: 8px;
        padding: 15px 0;
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
          <a href="Tracker.html" class="tracker">Tracker</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.html" class="tracker">Tracker</a>
          <a href="login.html">Login</a>
          <a href="Register.html" class="SignUp">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="Content">
        <!--if logged in-->
      <?php if($isLoggedIn): ?>
        <h2 class="hidden-text" data-anim="fade-up"><span>Welcome, <?php echo htmlspecialchars($username); ?>!</span></h2>
        <div class="bussin">
          <div class="busInfo">
              <h2>Recently Viewed</h2>
              <p><strong>Pickup:</strong> 
                  <?= isset($_SESSION['recent_trip']) ? $_SESSION['recent_trip']['pickup_name'] : "No recent trip"; ?>
              </p>
              <p><strong>Dropoff:</strong> 
                  <?= isset($_SESSION['recent_trip']) ? $_SESSION['recent_trip']['dropoff_name'] : "No recent trip"; ?>
              </p>
              <p><strong>Fare:</strong> ₱ 
                  <?= isset($_SESSION['recent_trip']) ? number_format($_SESSION['recent_trip']['fare'], 2) : "0.00"; ?>
              </p>
          </div>
          <div class="currentBus">    
            <h2>Current Location</h2>
            <p>Location</p>
            <p>Fare: ₱</p>
          </div>
        </div>
      <?php else: ?>
        <h1 class="hidden-text" data-anim="fade-up">Your <span class="highlight">No.1</span> Tracking Solution</h1>
        <p class="hidden-text" data-anim="fade-up">“Real-time rides, real-time ease.”</p>
        <img src="bustle.png" id="bustleImg" class="hidden-text" data-anim="fade-up">
        <section class="info">
    <div class="info-text">
      <h1 class="hidden-text" data-anim="fade-left">Longer waiting time?</h1>
      <h2 class="hidden-text" data-anim="fade-left">no longer a problem for <span class="highlight">Bustle!</span></h2>
      <p class="hidden-text" data-anim="fade-left"> 
        Bustle takes the guesswork out of commuting. Instead of wasting time waiting or
        running around looking for buses, students can track arrivals in real time.
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
      <p class="hidden-text" data-anim="fade-up">shows where the bus is, when it will reach the stop, and how long the ride will 
      take—saving time, reducing stress, and making sure you never miss class again</p>
    </div>
  </section>
      <?php endif; ?>
  </div>
  <footer>
    <a href="index.php" class="hidden-text" data-anim="fade-up">@Bustle.com</a>
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
});
  // Make currentBus stop above the footer
const bus = document.querySelector('.currentBus');
const footer = document.querySelector('footer');

window.addEventListener('scroll', () => {
  const footerTop = footer.getBoundingClientRect().top;
  const busHeight = bus.offsetHeight;
  
  if (footerTop <= busHeight + 20) { // 20 = top distance
    bus.style.position = 'absolute';
    bus.style.top = `${window.scrollY + footerTop - busHeight}px`;
  } else {
    bus.style.position = 'fixed';
    bus.style.top = '20px';
  }
});

  </script>
</body>
</html>
