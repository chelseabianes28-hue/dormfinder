// Listing details page: map, favorite button, inquiry form, and photo viewer.

const page = document.querySelector('#listingPage');

const showAlert = (type, text) => {
  const box = document.querySelector('#pageAlert');
  box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
    <span></span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
  box.querySelector('span').textContent = text;          // textContent keeps user text safe
  box.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// Send a POST request to one of our API endpoints and return the JSON result.
const post = async (url, fields) => {
  const body = new FormData();
  body.append('csrf_token', page.dataset.csrf);
  body.append('listing_id', page.dataset.id);
  Object.entries(fields).forEach(([key, value]) => body.append(key, value));

  let response;
  try {
    response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
  } catch {
    return { ok: false, error: 'Unable to connect to the server. Please check your connection.' };
  }
  if (response.status === 401) {
    location.href = page.dataset.loginUrl;
    return { ok: false, error: 'Please log in first.' };
  }
  try {
    return await response.json();
  } catch {
    return { ok: false, error: 'Unexpected response from the server.' };
  }
};

// Read-only map with the dorm's pin.
const setupMap = () => {
  const mapEl = document.querySelector('#detailMap');
  if (!mapEl || typeof L === 'undefined') return;
  const lat = parseFloat(mapEl.dataset.lat);
  const lng = parseFloat(mapEl.dataset.lng);
  if (Number.isNaN(lat) || Number.isNaN(lng)) { mapEl.remove(); return; }

  const map = L.map(mapEl).setView([lat, lng], 16);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);
  L.marker([lat, lng]).addTo(map);
};

// Add to favorite / remove from favorite.
const setupFavorite = () => {
  const button = document.querySelector('#favoriteBtn');
  if (!button) return;

  button.addEventListener('click', async () => {
    button.disabled = true;
    const result = await post('api/favorite_toggle.php', {});
    button.disabled = false;
    if (!result.ok) { showAlert('danger', result.error); return; }

    button.setAttribute('aria-pressed', String(result.favorited));
    button.querySelector('i').className = `bi ${result.favorited ? 'bi-heart-fill' : 'bi-heart'}`;
    button.querySelector('span').textContent = result.favorited ? 'Saved to favorites' : 'Add to favorite';
  });
};

// Inquiry form in a modal.
const setupInquiry = () => {
  const form = document.querySelector('#inquiryForm');
  if (!form) return;

  const message = form.querySelector('#inquiryMessage');
  const counter = form.querySelector('#inquiryCount');
  const errorBox = form.querySelector('#inquiryError');
  const submit = form.querySelector('#inquirySubmit');
  const moveIn = form.querySelector('#moveIn');

  moveIn.min = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  message.addEventListener('input', () => { counter.textContent = message.value.length; });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.classList.add('d-none');

    if (message.value.trim().length < 10) {
      errorBox.textContent = 'Please write at least 10 characters.';
      errorBox.classList.remove('d-none');
      return;
    }

    submit.disabled = true;
    submit.textContent = 'Sending...';
    const result = await post('api/inquiry_create.php', { message: message.value, move_in_date: moveIn.value });
    submit.disabled = false;
    submit.textContent = 'Send inquiry';

    if (!result.ok) {
      errorBox.textContent = result.error;
      errorBox.classList.remove('d-none');
      return;
    }
    bootstrap.Modal.getInstance(document.querySelector('#inquiryModal')).hide();
    form.reset();
    counter.textContent = '0';
    showAlert('success', result.message);
  });
};

// Show a gallery photo larger.
const setupPhotoModal = () => {
  const modal = document.querySelector('#photoModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', (event) => {
    modal.querySelector('#photoModalImg').src = event.relatedTarget.dataset.src;
  });
};

document.addEventListener('DOMContentLoaded', () => {
  setupMap();
  setupFavorite();
  setupInquiry();
  setupPhotoModal();
});
