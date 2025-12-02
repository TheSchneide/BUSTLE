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
    <link rel="stylesheet" href="index.css"> <link rel="stylesheet" href="tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="icon" type="image/x-icon" href="busFavicon.png">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        /* CSS Fix for Mobile Nav in Tracker */
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }
            #navBar {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: white;
                flex-direction: column;
                max-height: 0;
                overflow: hidden;
                transition: 0.4s ease;
                box-shadow: 0 4px 10px rgba(0,0,0,0.15);
                z-index: 9999;
                padding: 0; 
            }
            #navBar.active {
                max-height: 400px;
                padding: 20px 0;
            }
        }
    </style>
</head>

<body>
    <div id="rushHourBanner" class="rush-hour-banner">
        <div style="font-size: 24px;">⚠️</div>
        <div class="rush-content">
            <span class="rush-title">Rush Hour Alert</span>
            <span class="rush-msg">Expect heavy traffic. Seats may not be available immediately.</span>
        </div>
        <button class="rush-close" onclick="closeRushBanner()">✕</button>
    </div>

    <!-- NEW: WALK INFO MODAL -->
    <div id="walkInfo" class="walk-info-modal" style="display:none;">
        <div class="walk-info-title">Nearest Stop</div>
        <div class="walk-info-value" id="walkTime">-- min</div>
        <div class="walk-info-sub" id="walkDist">-- m</div>
        <div class="walk-info-sub" id="nearestStopName" style="font-size:0.75rem; font-style:italic; margin-top:5px; line-height:1.2;"></div>
    </div>

    <div id="mapTip" class="map-tip-overlay">
        <div class="map-tip-icon">👆</div>
        <h3 style="margin:0 0 10px 0; color:#4F200D;">Tap a Bus Stop</h3>
        <p style="margin:0; color:#666; font-size:0.95rem;">
            Click on any 🟢 green circle on the map to select it as your location.
        </p>
        <button class="map-tip-btn" onclick="closeMapTip()">Got it!</button>
    </div>

    <button id="recenterBtn" class="recenter-btn" onclick="recenterMap()" title="Locate Me">
        <i class="fa-solid fa-location-crosshairs"></i>
    </button>

    <header>
        <div class="Navigation">
            <div id="logo" data-anim="fade-up">
                <a href="index.php">Bustle</a>
            </div>

            <!-- ADDED HAMBURGER MENU -->
            <div class="menu-toggle" id="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div id="navBar" data-anim="fade-up">
                <a href="index.php">Home</a>
                <a href="Tracker.php" class="tracker">Calculator</a>
                
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
                        <button id="pickupMapBtn" class="map-pick-btn" onclick="startPickingLocation('pickup')">📍 Select from Map</button>
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
                        <button class="map-pick-btn" onclick="startPickingLocation('dest')">📍 Select from Map</button>
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
        const menuToggle = document.getElementById('menu-toggle');
        const navBar = document.getElementById('navBar');
        
        if(menuToggle && navBar) {
            menuToggle.addEventListener('click', () => {
                menuToggle.classList.toggle('active');
                navBar.classList.toggle('active');
            });
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

        const STUDENT_BASE_FARE = 13.00;
        const STUDENT_BASE_DIST = 7.4;
        const STUDENT_EXCESS_RATE = 2.56;
        const REGULAR_BASE_FARE = 13.00;
        const REGULAR_EXCESS_RATE = STUDENT_EXCESS_RATE / 0.8;
        const IS_DISCOUNTED = <?php echo $isDiscounted ? 'true' : 'false'; ?>;
        const USER_SAVED_ROUTES = <?php echo json_encode($mySavedRoutes); ?>;

        // --- MAP INIT (Zoom control moved to top-right) ---
        const map = L.map('map', { zoomControl: false }).setView([10.32853, 123.9089], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        L.control.zoom({ position: 'topright' }).addTo(map);

        // RESET HIGHLIGHT ON MAP CLICK
        map.on('click', () => {
            resetHighlights();
        });

        let stops = [];
        let stopMarkers = []; 
        let currentRouteLayers = []; 
        let walkLineLayer = null; // Stores the orange walking line

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
        let searchMarkers = { pickup: null, dest: null };
        let debounceTimer;
        
        let userLat = null;
        let userLng = null;
        let userMarker = null;

        let startY = 0;
        let isDragging = false;

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
                highlightKeyStops(); // Highlight specific stops on load
                populateDropdowns();
                checkUrlParams();
            } catch (error) { console.error('Error loading stops:', error); }
        }

        function initializeMapMarkers() {
            stops.forEach((stop, index) => {
                const marker = L.circleMarker([stop.lat, stop.lng], { color: 'green', radius: 4 }).addTo(map);
                marker.stopId = stop.id; 
                marker.stopName = stop.name; // For referencing
                marker.on('click', function(e) {
                    if (pickingMode) {
                        showStopConfirmation(index);
                        L.DomEvent.stopPropagation(e);
                    } else {
                        // Reset highlights on click too
                        resetHighlights();
                        this.bindPopup(`<b>${stop.name}</b>`).openPopup();
                    }
                });
                // Attach name to popup for default behavior
                marker.bindPopup(`<b>${stop.name}</b>`);
                stopMarkers.push(marker); 
            });
        }

        // --- HIGHLIGHT FEATURE (UPDATED TO USE ID) ---
        function highlightKeyStops() {
            // Need small timeout to ensure markers are ready
            setTimeout(() => {
                stopMarkers.forEach(marker => {
                    // Check for ID 1 (IT Park) or 31 (Mactan Newtown)
                    if(marker.stopId == 1 || marker.stopId == 31) {
                        marker.openPopup();
                    }
                });
            }, 500);
        }

        function resetHighlights() {
            map.closePopup();
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

        function startPickingLocation(type) {
            pickingMode = type;
            closeSheet();
            const btn = document.getElementById('pickupMapBtn');
            btn.classList.remove('tour-highlight');
            localStorage.setItem('hasSeenMapHighlight', 'true');
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

        // --- UPDATED LOCATE USER FOR REAL-TIME WALK INFO ---
        function locateUser() {
            if (navigator.geolocation) {
                // Use watchPosition for updates
                navigator.geolocation.watchPosition(
                    (position) => {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        
                        if(userMarker) {
                            userMarker.setLatLng([userLat, userLng]);
                        } else {
                            const userIcon = L.divIcon({
                                className: 'user-dot-container',
                                html: '<div class="user-dot"></div>',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            });
                            userMarker = L.marker([userLat, userLng], {icon: userIcon}).addTo(map);
                        }
                        
                        updateNearestStopInfo();
                    },
                    (error) => console.log("Location permission denied"),
                    { enableHighAccuracy: true }
                );
            }
        }

        function updateNearestStopInfo() {
            if(!userLat || stops.length === 0) return;

            let minDist = Infinity;
            let nearestStop = null;

            stops.forEach(stop => {
                const dist = map.distance([userLat, userLng], [stop.lat, stop.lng]);
                if(dist < minDist) {
                    minDist = dist;
                    nearestStop = stop;
                }
            });

            if(nearestStop) {
                const walkTimeMin = Math.round(minDist / 83); // Approx 83m per min (5km/h)
                
                document.getElementById('walkInfo').style.display = 'flex';
                document.getElementById('walkTime').innerText = (walkTimeMin < 1 ? "< 1" : walkTimeMin) + " min";
                document.getElementById('walkDist').innerText = Math.round(minDist) + " m";
                document.getElementById('nearestStopName').innerText = "to " + nearestStop.name;

                // --- DRAW ORANGE DOTTED LINE ---
                if (walkLineLayer) {
                    map.removeLayer(walkLineLayer);
                }
                walkLineLayer = L.polyline(
                    [[userLat, userLng], [nearestStop.lat, nearestStop.lng]], 
                    {
                        color: '#FF9A00',
                        dashArray: '10, 10',
                        weight: 4,
                        opacity: 0.8
                    }
                ).addTo(map);
            }
        }

        function recenterMap() {
            if (userLat && userLng) {
                map.flyTo([userLat, userLng], 15);
            } else {
                // Just trigger logic, let watchPosition handle update
                // But if first time, maybe timeout
                setTimeout(() => { if(userLat) map.flyTo([userLat, userLng], 15); }, 1000);
            }
        }

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
                if ((startName === "IT Park" && endName === "Saac") || (startName === "Saac" && endName === "IT Park")) fare = 29;
            } else {
                const excess = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                fare = REGULAR_BASE_FARE + (excess * REGULAR_EXCESS_RATE);
                if ((startName === "IT Park" && endName === "Mactan Newtown") || (startName === "Mactan Newtown" && endName === "IT Park")) fare = 51; 
            }
            if (fare < 13.00) fare = 13.00;
            return Math.round(fare);
        }

        function resetMarkerVisibility() {
            stopMarkers.forEach(marker => {
                if (!map.hasLayer(marker)) {
                    marker.addTo(map);
                }
            });
        }

        function isRushHour() {
            const date = new Date();
            const hour = date.getHours();
            return (hour >= 7 && hour < 9) || (hour >= 16 && hour < 20);
        }

        function isSegmentRed(idA, idB) {
            if (idA >= 32 && idA <= 49 && idB >= 32 && idB <= 49) return true;
            const redPairs = [[24, 49],[49, 18],[7, 6],[6, 5],[5, 4],[4, 3],[3, 2]];
            return redPairs.some(pair => pair[0] === idA && pair[1] === idB);
        }

        async function calculateFare() {
            const pickupVal = pickupSelect.value;
            const destVal = destSelect.value;
            const resultDisplay = document.getElementById('fareResult');

            if (pickupVal === "" || destVal === "") {
                saveRouteBtn.disabled = true;
                saveRouteBtn.classList.remove('active');
                if(pickupVal !== "") calculateRange(parseInt(pickupVal));
                return;
            }

            saveRouteBtn.disabled = false;
            
            const pId = stops[pickupVal].id;
            const dId = stops[destVal].id;
            const isSaved = USER_SAVED_ROUTES.some(r => r.pickup_stop_id == pId && r.dropoff_stop_id == dId);
            if (isSaved) saveRouteBtn.classList.add('active'); else saveRouteBtn.classList.remove('active');

            const pIndex = parseInt(pickupVal);
            const dIndex = parseInt(destVal);

            currentRouteLayers.forEach(layer => map.removeLayer(layer));
            currentRouteLayers = [];

            if (pIndex === dIndex) {
                resultDisplay.innerHTML = `₱ 0 <span class="fare-details">Start and End are same</span>`;
                sheetTitle.innerText = "₱ 0";
                return;
            }

            resultDisplay.innerHTML = `Calculating...`;
            sheetTitle.innerText = "BUS";

            // --- 1. DETERMINE ROUTE STOPS ---
            let routeStops = [];
            const pIDVal = parseInt(stops[pIndex].id);
            const dIDVal = parseInt(stops[dIndex].id);
            let customRoute = false;

            if (pIDVal >= 24 && pIDVal <= 31 && (dIDVal == 52 || dIDVal <= 18)) {
                const part1 = stops.filter(s => { const sid = parseInt(s.id); return sid <= pIDVal && sid >= 24; }).sort((a, b) => parseInt(b.id) - parseInt(a.id));
                const part2 = stops.filter(s => parseInt(s.id) == 52);
                let part3 = [];
                if (dIDVal <= 18) {
                    part3 = stops.filter(s => { const sid = parseInt(s.id); return sid <= 18 && sid >= dIDVal; }).sort((a, b) => parseInt(b.id) - parseInt(a.id));
                }
                routeStops = part1.concat(part2).concat(part3);
                customRoute = true;
            }
            else if (pIDVal >= 2 && pIDVal <= 31 && dIDVal < pIDVal) {
                routeStops = stops.filter(s => { const sid = parseInt(s.id); return sid <= pIDVal && sid >= dIDVal; }).sort((a, b) => parseInt(b.id) - parseInt(a.id));
                customRoute = true;
            }
            else if (pIDVal == 1 || pIDVal >= 32) {
                if (dIDVal >= 24 && dIDVal <= 31) {
                    let part1 = [];
                    if (pIDVal == 1) {
                        const stop1 = stops.filter(s => parseInt(s.id) == 1);
                        const stops32to49 = stops.filter(s => { const sid = parseInt(s.id); return sid >= 32 && sid <= 49; }).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                        part1 = stop1.concat(stops32to49);
                    } else {
                        part1 = stops.filter(s => { const sid = parseInt(s.id); return sid >= pIDVal && sid <= 49; }).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                    }
                    const part2 = stops.filter(s => { const sid = parseInt(s.id); return sid >= 24 && sid <= dIDVal; }).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                    routeStops = part1.concat(part2);
                    customRoute = true;
                }
                else if (dIDVal <= 49) {
                    if (pIDVal == 1) {
                         if (dIDVal >= 32) {
                             const stop1 = stops.filter(s => parseInt(s.id) == 1);
                             const stops32toDest = stops.filter(s => { const sid = parseInt(s.id); return sid >= 32 && sid <= dIDVal; }).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                             routeStops = stop1.concat(stops32toDest);
                             customRoute = true;
                         }
                    } else {
                        if (dIDVal > pIDVal) {
                             routeStops = stops.filter(s => { const sid = parseInt(s.id); return sid >= pIDVal && sid <= dIDVal; }).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                             customRoute = true;
                        }
                    }
                }
            }

            if (!customRoute) {
                if (pIndex < dIndex) { routeStops = stops.slice(pIndex, dIndex + 1); }
                else { routeStops = stops.slice(dIndex, pIndex + 1).reverse(); }
            }

            // --- 2. HIDE LOGIC ---
            resetMarkerVisibility();
            if (pIDVal >= 24 && pIDVal <= 31) {
                const marker49 = stopMarkers.find(m => m.stopId == 49);
                if (marker49) map.removeLayer(marker49);
            } else if (pIDVal == 1 || pIDVal >= 32) {
                const marker52 = stopMarkers.find(m => m.stopId == 52);
                if (marker52) map.removeLayer(marker52);
            }

            // --- 3. OPTIMIZED FETCHING (Group segments by color) ---
            try {
                let totalDistKm = 0;
                const requests = [];
                const rush = isRushHour();

                if (routeStops.length > 1) {
                    // Initialize first batch
                    let p1 = routeStops[0];
                    let p2 = routeStops[1];
                    let initialColor = rush && isSegmentRed(parseInt(p1.id), parseInt(p2.id)) ? 'red' : 'green';
                    
                    let currentBatch = {
                        color: initialColor,
                        stops: [p1, p2]
                    };

                    for (let i = 1; i < routeStops.length - 1; i++) {
                        p1 = routeStops[i];
                        p2 = routeStops[i+1];
                        let segColor = rush && isSegmentRed(parseInt(p1.id), parseInt(p2.id)) ? 'red' : 'green';

                        if (segColor === currentBatch.color) {
                            // If color matches, append the NEXT stop (p2) to the current batch
                            // Note: p1 is already the last stop of the previous segment
                            currentBatch.stops.push(p2);
                        } else {
                            // Color changed: close current batch and start new
                            requests.push(fetchBatch(currentBatch));
                            currentBatch = { color: segColor, stops: [p1, p2] };
                        }
                    }
                    // Push last batch
                    requests.push(fetchBatch(currentBatch));
                }

                // Helper: Fetch a single batch
                async function fetchBatch(batch) {
                    let coordList = [];
                    for(let i=0; i < batch.stops.length; i++) {
                        const s = batch.stops[i];
                        coordList.push(`${s.lng},${s.lat}`);
                        
                        // HIDDEN ROUTE LOGIC: NFA (2) -> IT Park (1)
                        if (parseInt(s.id) === 2 && (i + 1 < batch.stops.length) && parseInt(batch.stops[i+1].id) === 1) {
                            coordList.push("123.90621644479106,10.3273140907822");
                        }
                    }
                    
                    const coords = coordList.join(';');
                    const url = `https://router.project-osrm.org/route/v1/foot/${coords}?overview=full&geometries=geojson`;
                    const res = await fetch(url);
                    const data = await res.json();
                    return { data, color: batch.color };
                }

                const results = await Promise.all(requests);
                const featureGroup = L.featureGroup();

                results.forEach(res => {
                    const data = res.data;
                    const color = res.color;
                    
                    if (data.routes && data.routes.length > 0) {
                        const route = data.routes[0];
                        totalDistKm += (route.distance / 1000);
                        
                        const layer = L.geoJSON(route.geometry, { 
                            style: { color: color, weight: 4, opacity: 0.8 } 
                        }).addTo(map);
                        
                        currentRouteLayers.push(layer);
                        layer.addTo(featureGroup);
                    }
                });

                if(currentRouteLayers.length > 0) {
                    map.fitBounds(featureGroup.getBounds(), { padding: [50, 50] });
                }

                const timeInMinutes = Math.round((totalDistKm / 20) * 60);
                let timeString = timeInMinutes >= 60 ? `${Math.floor(timeInMinutes / 60)} hr ${timeInMinutes % 60} min` : `${timeInMinutes} min`;
                const finalFare = computeFareValue(totalDistKm, stops[pIndex].name, stops[dIndex].name);
                
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
                
                saveTripToHistory(stops[pIndex].id, stops[dIndex].id, finalFare);

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

        function openSheet() {
            bottomSheet.classList.add('expanded');
            sheetOverlay.classList.add('active');
            bottomSheet.style.transform = ""; 
            if(pickingMode) { pickingMode = null; }
            
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

        function copyToClipboard(text, btnElement) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btnElement.innerText;
                btnElement.innerText = "Copied!";
                btnElement.classList.add('copied');
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.classList.remove('copied');
                }, 2000);
            }).catch(err => { console.error('Failed to copy: ', err); });
        }

        fetchBusStops();
        checkRushHour();
        // Start locating user in background to show marker
        locateUser();
    </script>
</body>
</html>