// Find a dorm page: search, filter, sort, and load listings with the Fetch API.

const API_URL = 'api/listings.php';
const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const TYPE_LABELS = { dormitory: 'Dormitory', bedspace: 'Bedspace', boarding_house: 'Boarding house' };
const GENDER_LABELS = { any: 'All genders', male_only: 'Male only', female_only: 'Female only' };

const state = { q: '', type: '', gender: '', min: '', max: '', sort: 'newest', available: false, page: 1, lat: null, lng: null };
let controller = null;

const $ = (selector) => document.querySelector(selector);
const resultsEl = $('#results');
const countEl = $('#resultCount');
const loadMoreBtn = $('#loadMore');

const escapeHtml = (text) =>
  String(text).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

const cardHtml = ({ title, address, city, type, price_per_month, slots_available, gender_policy, photo_url, link, distance_km }) => `
  <div class="col-md-6 col-lg-4">
    <article class="card listing-card shadow-sm h-100">
      <div class="position-relative">
        ${photo_url
          ? `<img class="listing-photo" src="${escapeHtml(photo_url)}" alt="Photo of ${escapeHtml(title)}" loading="lazy">`
          : `<div class="listing-photo-placeholder" role="img" aria-label="No photo available"><i class="bi bi-image" aria-hidden="true"></i></div>`}
        <span class="badge text-bg-light listing-badge">${escapeHtml(TYPE_LABELS[type] ?? type)}</span>
      </div>
      <div class="card-body d-flex flex-column">
        <h2 class="h5 mb-1">${escapeHtml(title)}</h2>
        <p class="text-body-secondary small mb-2"><i class="bi bi-geo-alt" aria-hidden="true"></i> ${escapeHtml(address)}, ${escapeHtml(city)}</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="tag">${slots_available} ${slots_available === 1 ? 'slot' : 'slots'} left</span>
          <span class="tag">${escapeHtml(GENDER_LABELS[gender_policy] ?? '')}</span>
          ${distance_km !== null ? `<span class="tag">${distance_km} km away</span>` : ''}
        </div>
        <div class="d-flex justify-content-between align-items-center mt-auto">
          <div><span class="fs-5 fw-semibold">${peso.format(price_per_month)}</span> <span class="text-body-secondary small">/ month</span></div>
          <a class="circle-btn" href="${escapeHtml(link)}" aria-label="View ${escapeHtml(title)}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
      </div>
    </article>
  </div>`;

const showSkeletons = () => {
  resultsEl.insertAdjacentHTML('beforeend',
    Array.from({ length: 3 }, () => '<div class="col-md-6 col-lg-4 skeleton-col"><div class="skeleton"></div></div>').join(''));
};

const showMessage = (html) => {
  resultsEl.innerHTML = `<div class="col-12"><div class="card shadow-sm"><div class="card-body text-center py-5">${html}</div></div></div>`;
};

const buildQuery = () => {
  const params = new URLSearchParams({ page: state.page, sort: state.sort });
  if (state.q) params.set('q', state.q);
  if (state.type) params.set('type', state.type);
  if (state.gender) params.set('gender', state.gender);
  if (state.min !== '') params.set('min_price', state.min);
  if (state.max !== '') params.set('max_price', state.max);
  if (state.available) params.set('available', '1');
  if (state.lat !== null) { params.set('lat', state.lat); params.set('lng', state.lng); }
  return params;
};

const loadListings = async ({ append = false } = {}) => {
  if (controller) controller.abort();          // cancel an older request
  controller = new AbortController();
  if (!append) { state.page = 1; resultsEl.innerHTML = ''; }
  loadMoreBtn.classList.add('d-none');
  showSkeletons();

  try {
    const response = await fetch(`${API_URL}?${buildQuery()}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');

    resultsEl.querySelectorAll('.skeleton-col').forEach((el) => el.remove());
    if (!append && data.listings.length === 0) {
      countEl.textContent = '';
      showMessage('<i class="bi bi-search fs-1 text-body-secondary"></i><p class="mt-3 mb-0 text-body-secondary">No listings found. Try changing your search or filters.</p>');
      return;
    }
    resultsEl.insertAdjacentHTML('beforeend', data.listings.map(cardHtml).join(''));
    countEl.textContent = `${data.total} ${data.total === 1 ? 'listing' : 'listings'} found`;
    state.page = data.page;
    loadMoreBtn.classList.toggle('d-none', data.page >= data.pages);
  } catch (error) {
    if (error.name === 'AbortError') return;
    resultsEl.querySelectorAll('.skeleton-col').forEach((el) => el.remove());
    showMessage(`<p class="text-danger mb-3">${escapeHtml(error.message || 'Unable to connect to the server.')}</p>
                 <button type="button" class="btn btn-primary" id="retry">Try again</button>`);
    $('#retry').addEventListener('click', () => loadListings());
  }
};

const syncUrl = () => {
  const params = new URLSearchParams();
  if (state.q) params.set('q', state.q);
  if (state.type) params.set('type', state.type);
  history.replaceState(null, '', params.toString() ? `?${params}` : location.pathname);
};

const debounce = (fn, delay = 350) => {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
};

const applyFilters = () => { syncUrl(); loadListings(); };

const setActiveChip = (type) => {
  document.querySelectorAll('.filter-chip').forEach((chip) => {
    const active = chip.dataset.type === type;
    chip.classList.toggle('active', active);
    chip.setAttribute('aria-pressed', String(active));
  });
};

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(location.search);
  state.q = urlParams.get('q') ?? '';
  state.type = urlParams.get('type') ?? '';
  $('#q').value = state.q;
  setActiveChip(state.type);

  $('#searchForm').addEventListener('submit', (event) => { event.preventDefault(); state.q = $('#q').value.trim(); applyFilters(); });
  $('#q').addEventListener('input', debounce(() => { state.q = $('#q').value.trim(); applyFilters(); }));

  $('#typeChips').addEventListener('click', (event) => {
    const chip = event.target.closest('.filter-chip');
    if (!chip) return;
    state.type = chip.dataset.type;
    setActiveChip(state.type);
    applyFilters();
  });

  $('#filters').addEventListener('submit', (event) => event.preventDefault());
  $('#minPrice').addEventListener('input', debounce(() => { state.min = $('#minPrice').value; applyFilters(); }));
  $('#maxPrice').addEventListener('input', debounce(() => { state.max = $('#maxPrice').value; applyFilters(); }));
  $('#gender').addEventListener('change', () => { state.gender = $('#gender').value; applyFilters(); });
  $('#available').addEventListener('change', () => { state.available = $('#available').checked; applyFilters(); });
  $('#sort').addEventListener('change', () => {
    state.sort = $('#sort').value;
    if (state.sort === 'nearest' && state.lat === null) { $('#nearMe').click(); return; }
    applyFilters();
  });

  // "Dorms near me" uses the browser's geolocation (with the user's permission).
  $('#nearMe').addEventListener('click', () => {
    const msg = $('#nearMsg');
    msg.textContent = '';
    if (!navigator.geolocation) { msg.textContent = 'Your browser does not support location.'; return; }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        state.lat = coords.latitude.toFixed(6);
        state.lng = coords.longitude.toFixed(6);
        state.sort = 'nearest';
        $('#sort').value = 'nearest';
        applyFilters();
      },
      () => {
        msg.textContent = 'We could not get your location. Please allow location access or search by area.';
        if ($('#sort').value === 'nearest') { $('#sort').value = 'newest'; state.sort = 'newest'; }
      }
    );
  });

  loadMoreBtn.addEventListener('click', () => { state.page += 1; loadListings({ append: true }); });

  loadListings();
});
