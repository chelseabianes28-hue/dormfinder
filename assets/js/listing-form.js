// Add/Edit listing form: map pin picker, character counter, and photo preview.

const MAX_PHOTO_BYTES = 2 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const DEFAULT_CENTER = [14.5995, 120.9842];

// Feature 1: click the map (or use GPS) to set the dorm location.
const setupMapPicker = () => {
  const mapEl = document.querySelector('#map');
  if (!mapEl || typeof L === 'undefined') return;

  const latInput = document.querySelector('#latitude');
  const lngInput = document.querySelector('#longitude');
  const coordsText = document.querySelector('#coordsText');
  const locateBtn = document.querySelector('#locateBtn');
  const locateMsg = document.querySelector('#locateMsg');

  const hasPin = latInput.value !== '' && lngInput.value !== '';
  const start = hasPin ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : DEFAULT_CENTER;

  const map = L.map(mapEl).setView(start, hasPin ? 16 : 12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  let marker = null;

  const setPin = (lat, lng) => {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
    coordsText.textContent = `Pinned at ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

    if (marker) {
      marker.setLatLng([lat, lng]);
      return;
    }
    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', () => {
      const { lat: newLat, lng: newLng } = marker.getLatLng();
      setPin(newLat, newLng);
    });
  };

  if (hasPin) setPin(start[0], start[1]);

  map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng));

  locateBtn.addEventListener('click', () => {
    locateMsg.textContent = '';
    if (!navigator.geolocation) {
      locateMsg.textContent = 'Your browser does not support location.';
      return;
    }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        setPin(coords.latitude, coords.longitude);
        map.setView([coords.latitude, coords.longitude], 17);
      },
      () => {
        locateMsg.textContent = 'We could not get your location. Please click the map instead.';
      }
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
