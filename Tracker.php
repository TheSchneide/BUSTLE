<?php
session_start();
include 'Database.php';
include 'SavedRoute.php';
include 'Trip.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$isDiscounted = isset($_SESSION['is_discounted']) && $_SESSION['is_discounted'] === true;
$discountLabel = isset($_SESSION['discount_type']) ? $_SESSION['discount_type'] : "Regular";

$db = new Database();
$savedObj = new SavedRoute($db->getConnection());
$mySavedRoutes = $savedObj->getAll($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bustle - Tracker</title>
    <link rel="stylesheet" href="index.css"> <!-- Navbar Styles -->
    <link rel="stylesheet" href="tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="icon" type="image/x-icon" href="busFavicon.png">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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

    <!-- MAP TIP MODAL -->
    <div id="mapTip" class="map-tip-overlay">
        <div class="map-tip-icon">👆</div>
        <h3 style="margin:0 0 10px 0; color:#4F200D;">Tap a Bus Stop</h3>
        <p style="margin:0; color:#666; font-size:0.95rem;">
            Click on any 🟢 green circle on the map to select it as your location.
        </p>
        <button class="map-tip-btn" onclick="closeMapTip()">Got it!</button>
    </div>

    <!-- NEW: RECENTER BUTTON -->
    <button id="recenterBtn" class="recenter-btn" onclick="recenterMap()" title="Locate Me">
        <i class="fa-solid fa-location-crosshairs"></i>
    </button>

    <header>
        <div class="Navigation">
            <div id="logo" data-anim="fade-up">
                <a href="index.php">Bustle</a>
            </div>
            <div id="navBar" data-anim="fade-up">
                <a href="index.php">Home</a>
                <!-- Renamed back to Tracker as requested -->
                <a href="Tracker.php" class="tracker">Tracker</a>
                
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
                        <!-- ADDED ID HERE FOR HIGHLIGHT -->
                        <button id="pickupMapBtn" class="map-pick-btn" onclick="startPickingLocation('pickup')">📍 Map</button>
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

    <script>
        // --- CONFIGURATION ---
        const STUDENT_BASE_FARE = 13.00;
        const STUDENT_BASE_DIST = 7.4;
        const STUDENT_EXCESS_RATE = 2.56;
        const REGULAR_BASE_FARE = 13.00;
        const REGULAR_EXCESS_RATE = STUDENT_EXCESS_RATE / 0.8;
        const IS_DISCOUNTED = <?php echo $isDiscounted ? 'true' : 'false'; ?>;
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
        const mapConfirmOverlay = document.getElementById('mapConfirmOverlay');
        const confirmText = document.getElementById('confirmText');
        
        let pickingMode = null; 
        let tempSelectedStopIndex = -1;
        let currentRouteLayer = null;
        let searchMarkers = { pickup: null, dest: null };
        let debounceTimer;
        
        // --- NEW: USER LOCATION VARS ---
        let userLat = null;
        let userLng = null;
        let userMarker = null;

        // --- DRAG VARS ---
        let startY = 0;
        let isDragging = false;

        // --- 1. INITIALIZATION ---
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
                marker.on('click', function(e) {
                    if (pickingMode) {
                        showStopConfirmation(index);
                        L.DomEvent.stopPropagation(e);
                    } else {
                        this.bindPopup(`<b>${stop.name}</b>`).openPopup();
                    }
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

        // --- 2. MAP PICKING & HIGHLIGHT ---
        function startPickingLocation(type) {
            pickingMode = type;
            closeSheet();
            
            // Remove highlighting if user clicked the button
            const btn = document.getElementById('pickupMapBtn');
            btn.classList.remove('tour-highlight');
            localStorage.setItem('hasSeenMapHighlight', 'true');

            // Show instruction
            const hasSeenTip = localStorage.getItem('hasSeenMapTip');
            if (!hasSeenTip) {
                document.getElementById('mapTip').classList.add('show');
            }
        }

        function closeMapTip() {
            document.getElementById('mapTip').classList.remove('show');
            localStorage.setItem('hasSeenMapTip', 'true');
        }

        function showStopConfirmation(index) {
            tempSelectedStopIndex = index;
            confirmText.innerText = `Set "${stops[index].name}" as your ${pickingMode === 'pickup' ? "Pick-up" : "Destination"}?`;
            mapConfirmOverlay.classList.add('active');
        }

        function confirmStopSelection() {
            if (tempSelectedStopIndex === -1 || !pickingMode) return;
            if (pickingMode === 'pickup') {
                pickupSelect.value = tempSelectedStopIndex;
                manualSelect('pickup', false);
            } else {
                destSelect.value = tempSelectedStopIndex;
                manualSelect('dest', false);
            }
            mapConfirmOverlay.classList.remove('active');
            pickingMode = null;
            openSheet();
            calculateFare();
        }

        function cancelStopSelection() {
            mapConfirmOverlay.classList.remove('active');
            pickingMode = null;
            openSheet();
        }

        // --- 3. RECENTER & USER LOCATION LOGIC ---
        function locateUser() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        
                        // Update Marker
                        if(userMarker) map.removeLayer(userMarker);
                        
                        const userIcon = L.divIcon({
                            className: 'user-dot-container',
                            html: '<div class="user-dot"></div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        });
                        userMarker = L.marker([userLat, userLng], {icon: userIcon}).addTo(map);
                    },
                    (error) => console.log("Location permission denied")
                );
            }
        }

        function recenterMap() {
            if (userLat && userLng) {
                map.flyTo([userLat, userLng], 15);
            } else {
                locateUser(); // Try again if null
                // Small delay to allow update
                setTimeout(() => { if(userLat) map.flyTo([userLat, userLng], 15); }, 1000);
            }
        }

        // --- 4. FARE & LOGIC ---
        function computeFareValue(distKm, startName, endName) {
            const isStudent = IS_DISCOUNTED;
            let billableDistance = distKm;
            if (startName === "IT Park" || endName === "IT Park") {
                billableDistance = Math.max(0, distKm - 0.6); 
            }
            let fare = 0;
            if (isStudent) {
                const excess = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                fare = STUDENT_BASE_FARE + (excess * STUDENT_EXCESS_RATE);
                if ((startName === "IT Park" && endName === "Mactan Newtown") || (startName === "Mactan Newtown" && endName === "IT Park")) fare = 41; 
            } else {
                const excess = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                fare = REGULAR_BASE_FARE + (excess * REGULAR_EXCESS_RATE);
                if ((startName === "IT Park" && endName === "Mactan Newtown") || (startName === "Mactan Newtown" && endName === "IT Park")) fare = 51; 
            }
            if (fare < 13.00) fare = 13.00;
            return Math.round(fare);
        }

        async function calculateFare() {
            const pickupVal = pickupSelect.value;
            const destVal = destSelect.value;
            const resultDisplay = document.getElementById('fareResult');

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
            
            // Check star status
            const pId = stops[pickupVal].id;
            const dId = stops[destVal].id;
            const isSaved = USER_SAVED_ROUTES.some(r => r.pickup_stop_id == pId && r.dropoff_stop_id == dId);
            if (isSaved) saveRouteBtn.classList.add('active'); else saveRouteBtn.classList.remove('active');

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
                    const distKm = route.distance / 1000;
                    const timeInMinutes = Math.round((distKm / 20) * 60);
                    let timeString = timeInMinutes >= 60 ? `${Math.floor(timeInMinutes / 60)} hr ${timeInMinutes % 60} min` : `${timeInMinutes} min`;

                    const finalFare = computeFareValue(distKm, stops[pIndex].name, stops[dIndex].name);
                    sheetTitle.innerHTML = `<span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem;">₱ ${finalFare}</span>`;

                    const shareText = `🚍 Bustle Trip: ${stops[pIndex].name} ➝ ${stops[dIndex].name} | 💰 Fare: ₱${finalFare} | ⏳ ${timeString}`;

                    resultDisplay.innerHTML = `
                        <div class="fd-body">
                            <div class="fd-route">
                                <div><span class="fd-label">Start</span><span class="fd-address">${stops[pIndex].name}</span></div>
                                <div class="fd-arrow">↓</div>
                                <div><span class="fd-label">Finish</span><span class="fd-address">${stops[dIndex].name}</span></div>
                            </div>
                            <div class="fd-info">
                                <span class="fd-info-label">Total Fare <button class="copy-btn" onclick="copyToClipboard('${shareText}', this)">Copy</button></span>
                                <span class="fd-price">₱ ${finalFare}</span>
                                <span class="fd-time">Estimated Time~${timeString}</span>
                            </div>
                        </div>
                    `;
                    currentRouteLayer = L.geoJSON(route.geometry, { style: { color: 'blue', weight: 4, opacity: 0.7 } }).addTo(map);
                    map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });
                    saveTripToHistory(stops[pIndex].id, stops[dIndex].id, finalFare);
                }
            } catch (error) { console.error(error); }
        }

        async function calculateRange(pIndex) {
            const pickupStop = stops[pIndex];
            if (!pickupStop) return;
            document.getElementById('fareResult').innerHTML = `<div style="padding:10px; text-align:center;">Calculating range...</div>`;
            
            let maxDist = 0;
            let farthestIndex = -1;
            stops.forEach((stop, i) => {
                if (i == pIndex) return;
                const dist = map.distance([pickupStop.lat, pickupStop.lng], [stop.lat, stop.lng]);
                if (dist > maxDist) { maxDist = dist; farthestIndex = i; }
            });

            if (farthestIndex === -1) return;
            const destStop = stops[farthestIndex];
            const coordsString = `${pickupStop.lng},${pickupStop.lat};${destStop.lng},${destStop.lat}`;
            const routeURL = `https://router.project-osrm.org/route/v1/foot/${coordsString}?overview=false`;

            try {
                const response = await fetch(routeURL);
                const data = await response.json();
                if (data.routes && data.routes.length > 0) {
                    const distKm = data.routes[0].distance / 1000;
                    const maxFare = computeFareValue(distKm, pickupStop.name, destStop.name);
                    const minFare = 13;
                    const rangeText = `₱ ${minFare} - ₱ ${maxFare}`;
                    sheetTitle.innerHTML = `<span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem;">${rangeText}</span>`;
                    document.getElementById('fareResult').innerHTML = `
                        <div class="fd-body" style="justify-content: center;">
                            <div class="fd-info" style="text-align: center; width: 100%;">
                                <span class="fd-info-label">Estimated Fare Range</span>
                                <span class="fd-price">${rangeText}</span>
                                <span class="fd-time" style="font-weight:normal; font-size: 0.9rem; opacity: 0.8;">From ${pickupStop.name}</span>
                            </div>
                        </div>`;
                }
            } catch(e) { console.error("Range calc error", e); }
        }

        // --- INPUT & UI HANDLERS ---
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
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ph&viewbox=123.70,10.50,124.10,10.10&bounded=1&limit=5`;
            try {
                const response = await fetch(url);
                const results = await response.json();
                listElement.innerHTML = '';
                if (results.length > 0) {
                    listElement.style.display = 'block';
                    results.forEach(place => {
                        const div = document.createElement('div');
                        div.innerHTML = `<strong>${place.display_name.split(',')[0]}</strong>`;
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

        async function saveTripToHistory(pId, dId, amt) {
            const fd = new FormData(); fd.append('pickup_id', pId); fd.append('dropoff_id', dId); fd.append('fare', amt);
            await fetch('save_trip.php', { method: 'POST', body: fd });
        }

        async function updateSavedRoute() {
            const pIndex = pickupSelect.value;
            const dIndex = destSelect.value;
            if (pIndex === "" || dIndex === "") return;
            const action = saveRouteBtn.classList.contains('active') ? 'save' : 'remove';
            const formData = new FormData();
            formData.append('pickup_id', stops[pIndex].id);
            formData.append('dropoff_id', stops[dIndex].id);
            formData.append('action', action);
            try { await fetch('save_route.php', { method: 'POST', body: formData }); } catch(e) {}
        }

        saveRouteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            saveRouteBtn.classList.toggle('active');
            updateSavedRoute();
        });

        // --- DRAG LOGIC ---
        function openSheet() {
            bottomSheet.classList.add('expanded');
            sheetOverlay.classList.add('active');
            bottomSheet.style.transform = ""; 
            if(pickingMode) { pickingMode = null; }
            
            // --- NEW: Highlight Map Button if first time ---
            const hasSeenHighlight = localStorage.getItem('hasSeenMapHighlight');
            if(!hasSeenHighlight) {
                document.getElementById('pickupMapBtn').classList.add('tour-highlight');
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
            if (isExpanded && deltaY > 0) bottomSheet.style.transform = `translateY(${deltaY}px)`;
        }, { passive: false });

        sheetHeader.addEventListener('touchend', (e) => {
            isDragging = false;
            bottomSheet.classList.remove('dragging'); 
            const touchY = e.changedTouches[0].clientY;
            const deltaY = touchY - startY;
            const isExpanded = bottomSheet.classList.contains('expanded');
            const threshold = 100; 
            if (isExpanded) {
                if (deltaY > threshold) closeSheet(); else openSheet(); 
            } else {
                if (deltaY < -threshold) openSheet(); else closeSheet(); 
            }
        });

        sheetHeader.addEventListener('click', () => {
            if (bottomSheet.classList.contains('expanded')) closeSheet();
            else openSheet();
        });

        sheetOverlay.addEventListener('click', closeSheet);
        swapBtn.addEventListener('click', () => {
            const tempVal = pickupSelect.value;
            pickupSelect.value = destSelect.value;
            destSelect.value = tempVal;
            const tempText = pickupInput.value;
            pickupInput.value = destInput.value;
            destInput.value = tempText;
            calculateFare();
        });

        // --- CHECKS ---
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
            } else if (urlParams.get('autoLocate') === 'true') {
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

        function closeRushBanner() { document.getElementById('rushHourBanner').classList.remove('show'); }
        function checkRushHour() {
            const h = new Date().getHours();
            if ((h >= 7 && h < 9) || (h >= 16 && h < 20)) setTimeout(() => document.getElementById('rushHourBanner').classList.add('show'), 1000);
        }

        fetchBusStops();
        checkRushHour();
        // Start locating user in background to show marker
        locateUser();
    </script>
</body>
</html>