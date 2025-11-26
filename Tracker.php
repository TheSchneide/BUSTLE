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

        /* 5. Ticket Style Fare Result */
        .fare-display {
            background-color: #FF9A00; 
            color: #4F200D;
            padding: 20px 25px;
            border-radius: 16px;
            margin-top: 25px;
            position: relative;
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.3);
            font-family: 'Outfit', sans-serif;
            text-align: left;
        }
        .fd-header { text-align: center; margin-bottom: 15px; position: relative; }
        .fd-handle { width: 30px; height: 3px; background: #4F200D; opacity: 0.8; border-radius: 2px; margin: 0 auto 8px auto; }
        .fd-title { font-weight: 800; font-size: 14px; letter-spacing: 1px; margin: 0; text-transform: uppercase; }
        .fd-heart { position: absolute; top: 0; left: 0; font-size: 20px; opacity: 0.5; }
        .fd-body { display: flex; justify-content: space-between; align-items: flex-end; }
        .fd-route { flex: 1; padding-right: 10px; display: flex; flex-direction: column; gap: 2px; }
        .fd-label { font-weight: 800; font-size: 14px; color: #4F200D; display: inline-block; width: 50px; }
        .fd-address { font-size: 14px; font-weight: 400; opacity: 0.9; }
        .fd-arrow { font-size: 14px; color: #4F200D; margin-left: 18px; margin-top: -2px; margin-bottom: -2px; }
        .fd-info { text-align: right; min-width: 100px; margin-bottom: 10px;}
        .fd-info-label { font-size: 12px; opacity: 0.7; margin-bottom: 4px; display: block; }
        .fd-price { font-size: 32px; font-weight: 800; line-height: 1; display: block; }
        .fd-time { font-size: 13px; font-weight: 600; margin-top: 6px; display: block; opacity: 0.8; }
    </style>
</head>

<body>
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
                    <label class="input-label">Pick-up Point</label>
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
                <label class="input-label">Destination</label>
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

        let currentRouteLayer = null;
        let searchMarkers = { pickup: null, dest: null };
        let debounceTimer;

        // --- DRAG VARS ---
        let startY = 0;
        let isDragging = false;
        const getHiddenY = () => window.innerHeight - 90;

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
                marker.bindPopup(`<b>${stop.name}</b>`);
                marker.on('mouseover', function (e) { this.openPopup(); });
                marker.on('mouseout', function (e) { this.closePopup(); });
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


        // --- 5. CALCULATE FARE ---
        async function calculateFare() {
            const pickupVal = pickupSelect.value;
            const destVal = destSelect.value;
            const resultDisplay = document.getElementById('fareResult');
            
            const isStudent = IS_DISCOUNTED; 

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

                    // Discount Logic
                    const startName = stops[pIndex].name;
                    const endName = stops[dIndex].name;
                    
                    let billableDistance = actualDistanceKm;

                    // 0.6km reduction for IT Park (Corrected from 0.8)
                    if (startName === "IT Park" || endName === "IT Park") {
                        billableDistance = Math.max(0, actualDistanceKm - 0.6); 
                    }

                    let fare = 0;
                    const excessDist = Math.max(0, billableDistance - STUDENT_BASE_DIST);

                    if (isStudent) {
                        fare = STUDENT_BASE_FARE + (excessDist * STUDENT_EXCESS_RATE);
                        if ((startName === "IT Park" && endName === "Mactan Newtown") || (startName === "Mactan Newtown" && endName === "IT Park")) {
                            fare = 41; 
                        }
                    } else {
                        const regularExcessDist = Math.max(0, billableDistance - STUDENT_BASE_DIST);
                        fare = REGULAR_BASE_FARE + (regularExcessDist * REGULAR_EXCESS_RATE);
                        if ((startName === "IT Park" && endName === "Mactan Newtown") || (startName === "Mactan Newtown" && endName === "IT Park")) {
                            fare = 51; 
                        }
                    }

                    // Minimum Fare Floor
                    if (fare < 13.00) { fare = 13.00; }

                    const finalFare = Math.round(fare);
                    sheetTitle.innerText = `₱ ${finalFare}`;

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