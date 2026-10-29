// Add/Edit listing form: map pin picker, character counter, and photo preview.

const MAX_PHOTO_BYTES = 2 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const DEFAULT_CENTER = [14.5995, 120.9842];

// Feature 1: the pin is placed from the typed address; the owner can only move it a little.
const MAX_MOVE_METERS = 300;

const setupMapPicker = () => {
  const mapEl = document.querySelector('#map');
  if (!mapEl || typeof L === 'undefined') return;

  const latInput = document.querySelector('#latitude');
  const lngInput = document.querySelector('#longitude');
  const coordsText = document.querySelector('#coordsText');
  const locateBtn = document.querySelector('#locateBtn');
  const locateMsg = document.querySelector('#locateMsg');
  const findBtn = document.querySelector('#findOnMap');
  const findMsg = document.querySelector('#findMsg');
  const addressInput = document.querySelector('#address');
  const cityInput = document.querySelector('#city');

  const hasPin = latInput.value !== '' && lngInput.value !== '';
  const start = hasPin ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : DEFAULT_CENTER;

  const map = L.map(mapEl).setView(start, hasPin ? 16 : 12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  let marker = null;
  let lastPos = null;
  let anchor = null;        // the spot found from the address; the pin must stay close to it
  let limitCircle = null;
  let userMoved = false;    // true once the owner moved the pin by hand
  let busy = false;

  const setAnchor = (lat, lng) => {
    anchor = L.latLng(lat, lng);
    if (limitCircle) {
      limitCircle.setLatLng(anchor);
      return;
    }
    limitCircle = L.circle(anchor, {
      radius: MAX_MOVE_METERS, color: '#5b3a66', weight: 1.5, dashArray: '6 6', fillOpacity: 0.06, interactive: false,
    }).addTo(map);
  };

  const clearAnchor = () => {
    anchor = null;
    if (limitCircle) { limitCircle.remove(); limitCircle = null; }
  };

  const setPin = (lat, lng) => {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
    coordsText.textContent = `Pinned at ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    lastPos = [lat, lng];

    if (marker) {
      marker.setLatLng([lat, lng]);
      return;
    }
    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', () => {
      const { lat: newLat, lng: newLng } = marker.getLatLng();
      if (!tryMove(newLat, newLng)) marker.setLatLng(lastPos);   // too far: snap back
    });
  };

  // A manual move is only allowed within MAX_MOVE_METERS of the address spot.
  const tryMove = (lat, lng) => {
    if (anchor && anchor.distanceTo([lat, lng]) > MAX_MOVE_METERS) {
      findMsg.textContent = `Keep the pin within ${MAX_MOVE_METERS} m of your address. To place it somewhere else, fix the address and click "Find on map".`;
      return false;
    }
    userMoved = true;
    setPin(lat, lng);
    return true;
  };

  if (hasPin) {
    setPin(start[0], start[1]);
    setAnchor(start[0], start[1]);
  }

  map.on('click', (event) => tryMove(event.latlng.lat, event.latlng.lng));

  // Look up the typed address (one request per click or per finished field, never while typing).
  const findAddress = async ({ auto }) => {
    const address = addressInput.value.trim();
    const city = cityInput.value.trim();
    if (!address || !city) {
      if (!auto) findMsg.textContent = 'Type the street address and the city first.';
      return;
    }
    if (auto && userMoved) return;            // never overwrite a pin the owner adjusted
    if (busy) return;

    busy = true;
    findBtn.disabled = true;
    findMsg.textContent = 'Looking for your address...';
    try {
      const response = await fetch(`${mapEl.dataset.geocodeUrl}?${new URLSearchParams({ address, city })}`, { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');

      if (data.precision === 'address') {
        userMoved = false;
        setPin(data.lat, data.lng);
        setAnchor(data.lat, data.lng);
        map.setView([data.lat, data.lng], 17);
        findMsg.textContent = `Pin placed near: ${data.display_name}. You can move it up to ${MAX_MOVE_METERS} m.`;
      } else {
        clearAnchor();
        map.setView([data.lat, data.lng], 14);
        findMsg.textContent = 'We found the city but not the exact street. Click the map to place the pin on your dorm.';
      }
    } catch (error) {
      findMsg.textContent = error.message || 'Unable to connect to the server. Click the map to place the pin.';
    } finally {
      busy = false;
      findBtn.disabled = false;
    }
  };

  findBtn.addEventListener('click', () => { userMoved = false; findAddress({ auto: false }); });
  addressInput.addEventListener('change', () => findAddress({ auto: true }));
  cityInput.addEventListener('change', () => findAddress({ auto: true }));

  // The owner is standing at the dorm: trust the GPS position.
  locateBtn.addEventListener('click', () => {
    locateMsg.textContent = '';
    if (!navigator.geolocation) { locateMsg.textContent = 'Your browser does not support location.'; return; }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        clearAnchor();
        userMoved = true;
        setPin(coords.latitude, coords.longitude);
        map.setView([coords.latitude, coords.longitude], 17);
      },
      () => { locateMsg.textContent = 'We could not get your location. Please type the address instead.'; }
    );
  });
};

// Feature 2: live character counter for the description.
const setupCharCounter = () => {
  const textarea = document.querySelector('#description');
  const counter = document.querySelector('#descCount');
  if (!textarea || !counter) return;

  const update = () => {
    counter.textContent = textarea.value.length;
  };
  textarea.addEventListener('input', update);
  update();
};

// Feature 3: check photos and show previews before uploading.
const setupPhotoPreview = () => {
  const input = document.querySelector('#photos');
  const preview = document.querySelector('#photoPreview');
  const errorBox = document.querySelector('#photoError');
  if (!input || !preview || !errorBox) return;

  const maxFiles = parseInt(input.dataset.maxFiles, 10);

  input.addEventListener('change', () => {
    preview.innerHTML = '';
    errorBox.textContent = '';
    const files = [...input.files];

    const tooMany = files.length > maxFiles;
    const bad = files.find((file) => !ALLOWED_TYPES.includes(file.type) || file.size > MAX_PHOTO_BYTES);

    if (tooMany || bad) {
      errorBox.textContent = tooMany
        ? `You can add only ${maxFiles} more photo(s).`
        : `"${bad.name}" must be a JPG, PNG, or WebP image up to 2 MB.`;
      input.value = '';
      return;
    }

    files.forEach((file) => {
      const img = document.createElement('img');
      img.className = 'photo-thumb';
      img.alt = `Preview of ${file.name}`;
      img.src = URL.createObjectURL(file);
      preview.appendChild(img);
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  setupMapPicker();
  setupCharCounter();
  setupPhotoPreview();
});
