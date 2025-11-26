<?php
session_start();
include 'Database.php';
include 'SavedRoute.php';
include 'Trip.php'; // Ensure Trip class is included for history

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// 1. Check Passenger Type (Calculated during Login)
$isDiscounted = isset($_SESSION['is_discounted']) && $_SESSION['is_discounted'] === true;
$discountLabel = isset($_SESSION['discount_type']) ? $_SESSION['discount_type'] : "Regular";

// 2. Fetch ALL Saved Routes for this user (For the Star button logic)
$db = new Database();
$savedObj = new SavedRoute($db->getConnection());
$mySavedRoutes = $savedObj->getAll($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bustle - Fare Calculator</title>
    <link rel="stylesheet" href="tracker.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="icon" type="image/x-icon" href="busFavicon.png">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="">
        </script>
    
    <style>
        /* --- CUSTOM STYLES FOR TRACKER PHP --- */
        
        /* 1. Header Row (Aligns "Pick-up" with Star) */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .header-row .input-label { margin-bottom: 0; }

        /* 2. Star Save Button (Outline vs Filled) */
        .star-btn {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: transform 0.2s;
            color: transparent; /* Transparent center */
            -webkit-text-stroke: 2px #FF9A00; /* Orange Outline */
        }
        .star-btn.active {
            color: #FF9A00; /* Solid Orange */
            -webkit-text-stroke: 0;
            transform: scale(1.2);
            filter: drop-shadow(0 0 2px rgba(255, 154, 0, 0.5));
        }
        .star-btn:disabled {
            -webkit-text-stroke: 2px #ccc; 
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* 3. Swap Button */
        .swap-container {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-top: -15px;
            margin-bottom: -15px;
            position: relative;
            z-index: 10;
            padding-right: 10px;
            box-sizing: border-box;
        }
        .swap-btn {
            background: white;
            border: 2px solid #eee;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, background 0.2s;
        }
        .swap-btn:hover {
            background: #f0f0f0;
            transform: rotate(180deg);
        }
        .swap-icon {
            font-size: 18px;
            color: #FF9A00;
            font-weight: bold;
        }

        /* 4. Info Badge (Replaces Toggle) */
        .info-badge-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            border: 1px solid #eee;
        }
        .info-badge {
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .badge-active { background: #FF9A00; color: white; }
        .badge-regular { background: #ccc; color: #666; }

        /* --- 5. RUSH HOUR NOTIFICATION STYLES --- */
        .rush-hour-banner {
            position: fixed;
            top: -100px; /* Start hidden above screen */
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #d32f2f, #f44336);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 2000; /* Above everything */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            transition: top 0.5s ease-in-out;
            font-family: 'Outfit', sans-serif;
            box-sizing: border-box;
        }
        
        .rush-hour-banner.show {
            top: 0;
        }

        .rush-content {
            flex: 1;
            text-align: center;
        }

        .rush-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1rem;
            display: block;
            margin-bottom: 4px;
        }

        .rush-msg {
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .rush-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rush-close:hover {
            background: rgba(255,255,255,0.4);
        }
    </style>
</head>

<body>
    <!-- RUSH HOUR BANNER -->
    <div id="rushHourBanner" class="rush-hour-banner">
        <div style="font-size: 24px;">⚠️</div>
        <div class="rush-content">
            <span class="rush-title">Rush Hour Alert</span>
            <span class="rush-msg">Expect heavy traffic. Seats may not be available immediately.</span>
        </div>
        <button class="rush-close" onclick="closeRushBanner()">✕</button>
    </div>

    <header>
        <div class="Navigation">
            <div id="logo" data-anim="fade-up">
                <a href="index.php">Bustle</a>
            </div>
            <div id="navBar" data-anim="fade-up">
                <a href="index.php">Home</a>
                <a href="logout.php" class="tracker">Logout</a>
            </div>
        </div>
        <div id="map"></div>
    </header>

    <div id="sheetOverlay" class="sheet-overlay"></div>

    <div id="bottomSheet" class="bottom-sheet">
        <div class="sheet-header" id="sheetHeader">
            <div class="sheet-handle-bar"></div>
            <h2 class="sheet-title" id="sheetTitle">BUS</h2>
        </div>

        <div class="sheet-content">

            <div class="input-group">
                <div class="header-row">
                    <div>
                        <label class="input-label" style="display:inline;">Pick-up Point</label>
                        <button class="map-pick-btn" onclick="startPickingLocation('pickup')">📍 Map</button>
                    </div>
                    <button id="saveRouteBtn" class="star-btn" title="Save this Route" disabled>★</button>
                </div>

                <input type="text" id="pickupInput" class="clean-input" placeholder="Search location..." autocomplete="off">
                <div id="pickupSuggestions" class="suggestions-list"></div>
                <div class="divider"><span>OR SELECT LANDMARK</span></div>
                <select id="pickupSelect" class="styled-select">
                    <option value="" disabled selected>-- Loading Stops... --</option>
                </select>
                <div id="pickupMsg" class="helper-text"></div>
            </div>

            <div class="swap-container">
                <button class="swap-btn" id="swapBtn" title="Swap Locations">
                    <span class="swap-icon">⇅</span>
                </button>
            </div>

            <div class="input-group">
                <div class="header-row">
                    <div>
                        <label class="input-label" style="display:inline;">Destination</label>
                        <button class="map-pick-btn" onclick="startPickingLocation('dest')">📍 Map</button>
                    </div>
                </div>
                <input type="text" id="destInput" class="clean-input" placeholder="Search location..." autocomplete="off">
                <div id="destSuggestions" class="suggestions-list"></div>
                <div class="divider"><span>OR SELECT LANDMARK</span></div>
                <select id="destSelect" class="styled-select">
                    <option value="" disabled selected>-- Loading Stops... --</option>
                </select>
                <div id="destMsg" class="helper-text"></div>
            </div>

            <div class="info-badge-container">
                <div class="toggle-label">
                    Passenger Type
                    <span class="toggle-sublabel">
                        <?php echo htmlspecialchars($discountLabel); ?>
                    </span>
                </div>
                <div class="info-badge <?php echo $isDiscounted ? 'badge-active' : 'badge-regular'; ?>">
                    <?php echo $isDiscounted ? '20% OFF' : 'STANDARD'; ?>
                </div>
            </div>

            <div id="fareResult" class="fare-display">
                ₱ 0
                <span class="fare-details">Select locations to calculate</span>
            </div>
        </div>
    </div>

    <!-- Map Confirmation Modal -->
    <div id="mapConfirmOverlay" class="confirm-overlay">
        <div class="confirm-modal">
            <h3>Confirm Stop</h3>
            <p id="confirmText">Use this stop as your location?</p>
            <div class="confirm-actions">
                <button class="btn-cancel" onclick="cancelStopSelection()">Cancel</button>
                <button class="btn-confirm" onclick="confirmStopSelection()">Confirm</button>
            </div>
        </div>
    </div>

    <footer>
        <a href="index.php">@Bustle.dcism.org</a>
        <a href="">BustleCrew@gmail.com</a>
        <a href="">+091234567</a>
    </footer>

    <script>
        // --- CONFIGURATION ---
        const STUDENT_BASE_FARE = 13.00;
        const STUDENT_BASE_DIST = 7.4;
        const STUDENT_EXCESS_RATE = 2.56;
        const REGULAR_BASE_FARE = 13.00;
        const REGULAR_EXCESS_RATE = STUDENT_EXCESS_RATE / 0.8;

        // --- PHP INJECTION ---
        const IS_DISCOUNTED = <?php echo $isDiscounted ? 'true' : 'false'; ?>;
        
        // Load all saved routes into JS Array: [{pickup_stop_id: 1, dropoff_stop_id: 5}, ...]
        const USER_SAVED_ROUTES = <?php echo json_encode($mySavedRoutes); ?>;

        // --- MAP SETUP ---
        const map = L.map('map').setView([10.32853, 123.9089], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

        let stops = [];
        const pickupSelect = document.getElementById('pickupSelect');
        const destSelect = document.getElementById('destSelect');
        const pickupInput = document.getElementById('pickupInput');
        const destInput = document.getElementById('destInput');
        const saveRouteBtn = document.getElementById('saveRouteBtn');
        const swapBtn = document.getElementById('swapBtn');
        const sheetTitle = document.getElementById('sheetTitle');
        const bottomSheet = document.getElementById('bottomSheet');
        const sheetHeader = document.getElementById('sheetHeader');
        const sheetOverlay = document.getElementById('sheetOverlay');

        // New Elements for Map Picking
        const mapConfirmOverlay = document.getElementById('mapConfirmOverlay');
        const confirmText = document.getElementById('confirmText');
        let pickingMode = null; // 'pickup' or 'dest' or null
        let tempSelectedStopIndex = -1;

        let currentRouteLayer = null;
        let searchMarkers = { pickup: null, dest: null };
        let debounceTimer;

        // --- DRAG VARS ---
        let startY = 0;
        let isDragging = false;
        const getHiddenY = () => window.innerHeight - 90;

        // --- RUSH HOUR LOGIC ---
        function checkRushHour() {
            const now = new Date();
            const hour = now.getHours(); // 0-23
            const banner = document.getElementById('rushHourBanner');

            // Define Rush Hours: 
            // Morning: 7 AM - 9 AM (7, 8)
            // Evening: 4 PM - 8 PM (16, 17, 18, 19)
            const isMorningRush = (hour >= 7 && hour < 9);
            const isEveningRush = (hour >= 16 && hour < 20);

            if (isMorningRush || isEveningRush) {
                // Add a small delay for better UX (so it slides in)
                setTimeout(() => {
                    banner.classList.add('show');
                }, 1000);
            }
        }

        function closeRushBanner() {
            const banner = document.getElementById('rushHourBanner');
            banner.classList.remove('show');
        }

        // Call immediately on load
        checkRushHour();


        // --- 1. FETCH STOPS ---
        async function fetchBusStops() {
            try {
                const response = await fetch('get_stops.php');
                const data = await response.json();
                stops = data.map(item => ({
                    id: item.stop_id,
                    name: item.stop_name,
                    lat: parseFloat(item.latitude),
                    lng: parseFloat(item.longitude)
                }));
                initializeMapMarkers();
                populateDropdowns();
                checkUrlParams();
            } catch (error) { console.error('Error loading stops:', error); }
        }

        function initializeMapMarkers() {
            stops.forEach((stop, index) => {
                const marker = L.circleMarker([stop.lat, stop.lng], { color: 'green', radius: 4 }).addTo(map);
                
                // --- NEW LOGIC FOR MARKER CLICK ---
                marker.on('click', function(e) {
                    if (pickingMode) {
                        // In picking mode: Show Custom Confirmation
                        showStopConfirmation(index);
                        // Prevent map flyto or default popup behavior if needed
                        L.DomEvent.stopPropagation(e);
                    } else {
                        // Normal mode: Show standard popup
                        this.bindPopup(`<b>${stop.name}</b>`).openPopup();
                    }
                });
                
                marker.on('mouseover', function (e) { 
                    if(!pickingMode) { // Only show popup on hover if not picking
                        this.bindPopup(`<b>${stop.name}</b>`).openPopup(); 
                    }
                });
                marker.on('mouseout', function (e) { 
                    this.closePopup(); 
                });
            });
        }

        function populateDropdowns() {
            pickupSelect.innerHTML = '<option value="" disabled selected>-- Choose a Landmark --</option>';
            destSelect.innerHTML = '<option value="" disabled selected>-- Choose a Landmark --</option>';
            stops.forEach((stop, index) => {
                let opt = document.createElement("option");
                opt.value = index; opt.text = stop.name;
                pickupSelect.add(opt.cloneNode(true)); destSelect.add(opt.cloneNode(true));
            });
        }

        // --- NEW: PICK ON MAP LOGIC ---

        function startPickingLocation(type) {
            pickingMode = type;
            closeSheet();
            // Optional: Toast or small hint could go here
            console.log("Pick mode started for: " + type);
        }

        function showStopConfirmation(index) {
            tempSelectedStopIndex = index;
            const stopName = stops[index].name;
            const modeName = pickingMode === 'pickup' ? "Pick-up" : "Destination";
            
            confirmText.innerText = `Set "${stopName}" as your ${modeName}?`;
            mapConfirmOverlay.classList.add('active');
        }

        function confirmStopSelection() {
            if (tempSelectedStopIndex === -1 || !pickingMode) return;

            // Set the value based on mode
            if (pickingMode === 'pickup') {
                pickupSelect.value = tempSelectedStopIndex;
                manualSelect('pickup', false); // false = don't double calculate yet
            } else {
                destSelect.value = tempSelectedStopIndex;
                manualSelect('dest', false);
            }

            // Clean up
            mapConfirmOverlay.classList.remove('active');
            pickingMode = null;
            tempSelectedStopIndex = -1;

            // Re-open sheet and calculate
            openSheet();
            calculateFare();
        }

        function cancelStopSelection() {
            // Just close the confirmation modal, user is still in picking mode
            // Or, per requirement: "opens bottom sheet back up and cancels feature"
            
            mapConfirmOverlay.classList.remove('active');
            
            // Per instructions: "If they decline, it opens the bottom sheet back up and cancels the feature"
            pickingMode = null;
            tempSelectedStopIndex = -1;
            openSheet();
        }


        // --- 2. SWAP LOGIC ---
        swapBtn.addEventListener('click', () => {
            const tempSelect = pickupSelect.value;
            pickupSelect.value = destSelect.value;
            destSelect.value = tempSelect;

            const tempText = pickupInput.value;
            pickupInput.value = destInput.value;
            destInput.value = tempText;

            if (searchMarkers['pickup']) map.removeLayer(searchMarkers['pickup']);
            if (searchMarkers['dest']) map.removeLayer(searchMarkers['dest']);

            if(pickupSelect.value !== "") manualSelect('pickup', false);
            if(destSelect.value !== "") manualSelect('dest', false);

            calculateFare();
        });

        fetchBusStops();

        // --- 3. URL PARAMS CHECK ---
        function checkUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const savedPickupId = urlParams.get('savedPickup');
            const savedDropoffId = urlParams.get('savedDropoff');

            if (savedPickupId && savedDropoffId) {
                const pIndex = stops.findIndex(s => s.id == savedPickupId);
                const dIndex = stops.findIndex(s => s.id == savedDropoffId);

                if (pIndex !== -1 && dIndex !== -1) {
                    openSheet();
                    pickupSelect.value = pIndex;
                    destSelect.value = dIndex;
                    calculateFare();
                }
            }
            else if (urlParams.get('autoLocate') === 'true') {
                openSheet();
                if (navigator.geolocation) {
                    document.getElementById('pickupInput').value = "Locating you...";
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            findNearestStop(lat, lng, 'pickup', "Your Current Location");
                        },
                        (error) => {
                            document.getElementById('pickupInput').value = "";
                            document.getElementById('pickupMsg').innerText = "Could not detect location.";
                        }
                    );
                }
            }
        }

        function findNearestStop(lat, lng, type, label) {
            let nearestIndex = -1; let minDistance = Infinity;
            stops.forEach((stop, index) => {
                const dist = map.distance([lat, lng], [stop.lat, stop.lng]);
                if (dist < minDistance) { minDistance = dist; nearestIndex = index; }
            });
            if (nearestIndex !== -1) {
                const selectBox = document.getElementById(type + 'Select');
                const msg = document.getElementById(type + 'Msg');
                const input = document.getElementById(type + 'Input');
                selectBox.value = nearestIndex;
                input.value = label;
                const stopName = stops[nearestIndex].name;
                msg.innerText = `Auto-selected: ${stopName} (${(minDistance / 1000).toFixed(2)}km away)`;
                if (searchMarkers[type]) map.removeLayer(searchMarkers[type]);
                searchMarkers[type] = L.marker([lat, lng]).addTo(map).bindPopup(`<b>${type.toUpperCase()}</b><br>${label}`).openPopup();
                map.setView([lat, lng], 14);
                calculateFare();
            }
        }

        // --- 4. SAVE ROUTE (STAR BUTTON) ---
        async function updateSavedRoute() {
            const pIndex = pickupSelect.value;
            const dIndex = destSelect.value;
            if (pIndex === "" || dIndex === "") return;

            // Check visual state to determine action
            const action = saveRouteBtn.classList.contains('active') ? 'save' : 'remove';
            const pickupId = stops[pIndex].id;
            const dropoffId = stops[dIndex].id;

            const formData = new FormData();
            formData.append('pickup_id', pickupId);
            formData.append('dropoff_id', dropoffId);
            formData.append('action', action);

            try {
                await fetch('save_route.php', { method: 'POST', body: formData });
                console.log("Saved route: " + action);
                
                // Update Local Array so logic persists without reload
                if (action === 'save') {
                    USER_SAVED_ROUTES.push({pickup_stop_id: pickupId, dropoff_stop_id: dropoffId});
                } else {
                    const idx = USER_SAVED_ROUTES.findIndex(r => r.pickup_stop_id == pickupId && r.dropoff_stop_id == dropoffId);
                    if (idx > -1) USER_SAVED_ROUTES.splice(idx, 1);
                }
            } catch(e) { console.error(e); }
        }

        saveRouteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            // Toggle visual state immediately
            saveRouteBtn.classList.toggle('active');
            updateSavedRoute();
        });

        // --- 5. HELPER: COMPUTE FARE MATH (UPDATED) ---
        function computeFareValue(distKm, startName, endName) {
            const isStudent = IS_DISCOUNTED;
            let billableDistance = distKm;

            // 0.6km reduction for IT Park
            if (startName === "IT Park" || endName === "IT Park") {
                billableDistance = Math.max(0, distKm - 0.6); 
            }

            let fare = 0;
            if (isStudent) {
                const excess = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                fare = STUDENT_BASE_FARE + (excess * STUDENT_EXCESS_RATE);
                
                // 1. FIXED RATE: IT Park -> Mactan Newtown ONLY (41)
                if (startName === "IT Park" && endName === "Mactan Newtown") {
                    fare = 41; 
                }

                // 2. SPECIAL CAPS for Students
                if (startName === "Mactan Newtown") {
                    if (fare > 31) fare = 41;
                }
                if (startName === "NFA") {
                    if (fare > 34) fare = 39;
                }

            } else {
                // Regular Logic
                const excess = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                fare = REGULAR_BASE_FARE + (excess * REGULAR_EXCESS_RATE);
                
                // FIXED RATE: IT Park -> Mactan Newtown ONLY (51)
                if (startName === "IT Park" && endName === "Mactan Newtown") {
                    fare = 51; 
                }
            }

            // Minimum Floor
            if (fare < 13.00) fare = 13.00;
            
            return Math.round(fare);
        }

        // --- 6. NEW: CALCULATE RANGE (Only Pickup Selected) ---
        async function calculateRange(pIndex) {
            const pickupStop = stops[pIndex];
            if (!pickupStop) return;

            // Update UI to "Loading" state
            document.getElementById('fareResult').innerHTML = `<div style="padding:10px; text-align:center;">Calculating range...</div>`;
            
            // 1. Find farthest stop via Straight Line (to minimize API calls)
            let maxDist = 0;
            let farthestIndex = -1;

            stops.forEach((stop, i) => {
                if (i == pIndex) return;
                const dist = map.distance([pickupStop.lat, pickupStop.lng], [stop.lat, stop.lng]);
                if (dist > maxDist) {
                    maxDist = dist;
                    farthestIndex = i;
                }
            });

            if (farthestIndex === -1) return; // Should not happen if >1 stop

            // 2. Fetch OSRM for that ONE farthest stop to get accurate Max Fare
            const destStop = stops[farthestIndex];
            const coordsString = `${pickupStop.lng},${pickupStop.lat};${destStop.lng},${destStop.lat}`;
            const routeURL = `https://router.project-osrm.org/route/v1/foot/${coordsString}?overview=false`;

            try {
                const response = await fetch(routeURL);
                const data = await response.json();
                
                if (data.routes && data.routes.length > 0) {
                    const distKm = data.routes[0].distance / 1000;
                    const maxFare = computeFareValue(distKm, pickupStop.name, destStop.name);
                    const minFare = 13; // Base fare is almost always the minimum

                    // Update UI
                    const rangeText = `₱ ${minFare} - ₱ ${maxFare}`;
                    sheetTitle.innerHTML = `<span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem;">${rangeText}</span>`;
                    
                    document.getElementById('fareResult').innerHTML = `
                        <div class="fd-body" style="justify-content: center;">
                            <div class="fd-info" style="text-align: center; width: 100%;">
                                <span class="fd-info-label">Estimated Fare Range</span>
                                <span class="fd-price">${rangeText}</span>
                                <span class="fd-time" style="font-weight:normal; font-size: 0.9rem; opacity: 0.8;">From ${pickupStop.name}</span>
                            </div>
                        </div>
                    `;
                }
            } catch(e) {
                console.error("Range calc error", e);
            }
        }


        // --- 7. CALCULATE FARE (Updated to use Helper) ---
        async function calculateFare() {
            const pickupVal = pickupSelect.value;
            const destVal = destSelect.value;
            const resultDisplay = document.getElementById('fareResult');
            
            // IF PICKUP IS SET BUT DESTINATION IS EMPTY -> SHOW RANGE
            if (pickupVal !== "" && destVal === "") {
                calculateRange(parseInt(pickupVal));
                saveRouteBtn.disabled = true;
                saveRouteBtn.classList.remove('active');
                return;
            }

            if (pickupVal === "" || destVal === "") {
                saveRouteBtn.disabled = true;
                saveRouteBtn.classList.remove('active');
                return;
            }

            saveRouteBtn.disabled = false;

            // Check if this route is already saved (Update Star)
            const currentPickupId = stops[pickupVal].id;
            const currentDropoffId = stops[destVal].id;
            const isSaved = USER_SAVED_ROUTES.some(r => r.pickup_stop_id == currentPickupId && r.dropoff_stop_id == currentDropoffId);
            if (isSaved) saveRouteBtn.classList.add('active');
            else saveRouteBtn.classList.remove('active');

            const pIndex = parseInt(pickupVal);
            const dIndex = parseInt(destVal);

            if (pIndex === dIndex) {
                resultDisplay.innerHTML = `₱ 0 <span class="fare-details">Start and End are same</span>`;
                sheetTitle.innerText = "₱ 0";
                if (currentRouteLayer) map.removeLayer(currentRouteLayer); return;
            }

            resultDisplay.innerHTML = `Calculating...`;
            sheetTitle.innerText = "BUS";
            if (currentRouteLayer) map.removeLayer(currentRouteLayer);

            let routeStops = [];
            if (pIndex < dIndex) { routeStops = stops.slice(pIndex, dIndex + 1); }
            else { routeStops = stops.slice(dIndex, pIndex + 1).reverse(); }

            const coordsString = routeStops.map(s => `${s.lng},${s.lat}`).join(';');
            const routeURL = `https://router.project-osrm.org/route/v1/foot/${coordsString}?overview=full&geometries=geojson`;

            try {
                const response = await fetch(routeURL);
                const data = await response.json();
                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const actualDistanceKm = route.distance / 1000;
                    
                    // Time Calc
                    const avgSpeed = 20; 
                    const timeInMinutes = Math.round((actualDistanceKm / avgSpeed) * 60);
                    let timeString = `${timeInMinutes} min`;
                    if (timeInMinutes >= 60) {
                        const hrs = Math.floor(timeInMinutes / 60);
                        const mins = timeInMinutes % 60;
                        timeString = `${hrs} hr ${mins} min`;
                    }

                    const startName = stops[pIndex].name;
                    const endName = stops[dIndex].name;

                    // USE HELPER FOR FARE MATH
                    const finalFare = computeFareValue(actualDistanceKm, startName, endName);

                    sheetTitle.innerHTML = `<span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem;">₱ ${finalFare}</span>`;

                    // Ticket Style Output
                    const safeStart = startName.length > 15 ? startName.substring(0, 15) + '...' : startName;
                    const safeEnd = endName.length > 15 ? endName.substring(0, 15) + '...' : endName;

                    resultDisplay.innerHTML = `
                        <div class="fd-body">
                            <div class="fd-route">
                                <div><span class="fd-label">Start</span><span class="fd-address">${safeStart}</span></div>
                                <div class="fd-arrow">↓</div>
                                <div><span class="fd-label">Finish</span><span class="fd-address">${safeEnd}</span></div>
                            </div>
                            <div class="fd-info">
                                <span class="fd-info-label">Total Fare</span>
                                <span class="fd-price">₱ ${finalFare}</span>
                                <span class="fd-time">Estimated Time~${timeString}</span>
                            </div>
                        </div>
                    `;
                    
                    currentRouteLayer = L.geoJSON(route.geometry, { style: { color: 'blue', weight: 4, opacity: 0.7 } }).addTo(map);
                    map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });
                    
                    saveTripToHistory(stops[pIndex].id, stops[dIndex].id, finalFare);

                } else { resultDisplay.innerHTML = `Error <span class="fare-details">No route found</span>`; }
            } catch (error) { console.error(error); resultDisplay.innerHTML = `Error <span class="fare-details">Connection failed</span>`; }
        }

        // --- INPUT HANDLERS ---
        document.getElementById('pickupInput').addEventListener('input', (e) => handleInput(e, 'pickupSuggestions', 'pickup'));
        document.getElementById('destInput').addEventListener('input', (e) => handleInput(e, 'destSuggestions', 'dest'));

        function handleInput(e, listId, type) {
            clearTimeout(debounceTimer);
            const query = e.target.value;
            const list = document.getElementById(listId);
            if (query.length < 3) { list.style.display = 'none'; return; }
            debounceTimer = setTimeout(() => { fetchSuggestions(query, list, type); }, 300);
        }

        async function fetchSuggestions(query, listElement, type) {
            const viewbox = '123.70,10.50,124.10,10.10';
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ph&viewbox=${viewbox}&bounded=1&limit=5`;
            try {
                const response = await fetch(url);
                const results = await response.json();
                listElement.innerHTML = '';
                if (results.length > 0) {
                    listElement.style.display = 'block';
                    results.forEach(place => {
                        const div = document.createElement('div');
                        const displayName = place.display_name.split(',')[0];
                        div.innerHTML = `<strong>${displayName}</strong>`;
                        div.addEventListener('click', () => {
                            const lat = parseFloat(place.lat); const lng = parseFloat(place.lon);
                            findNearestStop(lat, lng, type, place.display_name.split(',')[0]);
                            listElement.style.display = 'none';
                        });
                        listElement.appendChild(div);
                    });
                } else { listElement.style.display = 'none'; }
            } catch (err) { console.error("Search error", err); }
        }

        pickupSelect.addEventListener('change', () => manualSelect('pickup'));
        destSelect.addEventListener('change', () => manualSelect('dest'));

        function manualSelect(type, doCalc = true) {
            const selectBox = document.getElementById(type + 'Select');
            const input = document.getElementById(type + 'Input');
            const msg = document.getElementById(type + 'Msg');
            const index = selectBox.value;
            const stop = stops[index];
            if(input.value === "") input.value = stop.name; 
            msg.innerText = "Selected: " + stop.name;
            if (searchMarkers[type]) map.removeLayer(searchMarkers[type]);
            map.setView([stop.lat, stop.lng], 14);
            if(doCalc) calculateFare();
        }

        async function saveTripToHistory(pickupId, dropoffId, amount) {
            const formData = new FormData();
            formData.append('pickup_id', pickupId);
            formData.append('dropoff_id', dropoffId);
            formData.append('fare', amount);
            try { await fetch('save_trip.php', { method: 'POST', body: formData }); } catch (e) { }
        }

        // --- DRAG LOGIC ---
        function openSheet() {
            bottomSheet.classList.add('expanded');
            sheetOverlay.classList.add('active');
            bottomSheet.style.transform = ""; 
            
            // --- NEW: Cancel picking mode if user manually opens sheet ---
            if(pickingMode) {
                pickingMode = null;
                console.log("Picking mode cancelled by manual sheet open");
            }
        }
        function closeSheet() {
            bottomSheet.classList.remove('expanded');
            sheetOverlay.classList.remove('active');
            bottomSheet.style.transform = ""; 
        }

        sheetHeader.addEventListener('touchstart', (e) => {
            isDragging = true;
            startY = e.touches[0].clientY;
            bottomSheet.classList.add('dragging'); 
        }, { passive: true });

        sheetHeader.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            e.preventDefault(); 
            const touchY = e.touches[0].clientY;
            const deltaY = touchY - startY;
            const isExpanded = bottomSheet.classList.contains('expanded');
            if (isExpanded && deltaY > 0) {
                 bottomSheet.style.transform = `translateY(${deltaY}px)`;
            } 
        }, { passive: false });

        sheetHeader.addEventListener('touchend', (e) => {
            isDragging = false;
            bottomSheet.classList.remove('dragging'); 
            const touchY = e.changedTouches[0].clientY;
            const deltaY = touchY - startY;
            const isExpanded = bottomSheet.classList.contains('expanded');
            const threshold = 100; 
            if (isExpanded) {
                if (deltaY > threshold) closeSheet();
                else openSheet(); 
            } else {
                if (deltaY < -threshold) openSheet();
                else closeSheet(); 
            }
        });

        sheetHeader.addEventListener('click', () => {
            if (bottomSheet.classList.contains('expanded')) closeSheet();
            else openSheet();
        });

        sheetOverlay.addEventListener('click', closeSheet);
    </script>
</body>
</html>