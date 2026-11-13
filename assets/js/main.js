// Site-wide JavaScript: flash messages, dark mode, live notification badge, and PWA service worker.

// Feature: flash messages fade out by themselves after 5 seconds.
const autoDismissFlashMessages = () => {
  document.querySelectorAll('.flash-message').forEach((alertEl) => {
    setTimeout(() => {
      bootstrap.Alert.getOrCreateInstance(alertEl).close();
    }, 5000);
  });
};

// Feature: dark mode. The choice is saved with Web Storage (localStorage) and kept after a refresh.
const THEME_KEY = 'df-theme';

const applyTheme = (theme) => {
  document.documentElement.setAttribute('data-bs-theme', theme);

  const button = document.querySelector('#themeToggle');
  if (!button) return;
  const dark = theme === 'dark';
  button.setAttribute('aria-pressed', String(dark));
  button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
  button.querySelector('i').className = `bi ${dark ? 'bi-sun' : 'bi-moon-stars'} fs-5`;
  const label = button.querySelector('span');
  if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
};

const setupThemeToggle = () => {
  const button = document.querySelector('#themeToggle');
  applyTheme(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light');
  if (!button) return;

  button.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try {
      localStorage.setItem(THEME_KEY, next);
    } catch {
      // Storage can be blocked (private mode); the theme still changes for this page.
    }
    document.dispatchEvent(new CustomEvent('themechange', { detail: next }));
  });

  // Keep other open tabs in sync.
  window.addEventListener('storage', (event) => {
    if (event.key === THEME_KEY && (event.newValue === 'dark' || event.newValue === 'light')) {
      applyTheme(event.newValue);
      document.dispatchEvent(new CustomEvent('themechange', { detail: event.newValue }));
    }
  });
};

// Feature: installable web app (PWA). The service worker caches CSS/JS and shows an offline page.
const registerServiceWorker = () => {
  if (!('serviceWorker' in navigator)) return;
  const base = document.querySelector('meta[name="app-base"]')?.content ?? '';
  navigator.serviceWorker.register(`${base}/sw.js`, { scope: `${base}/` }).catch(() => {
    // Not supported here (for example, not HTTPS); the site still works normally.
  });
};

// Feature: live notification badge. Every 30 seconds the unread count is checked with the Fetch API,
// so a new inquiry or reply appears in the navbar without reloading the page.
const NOTIFY_MS = 30000;

const showToast = (text) => {
  const toast = document.createElement('div');
  toast.className = 'toast align-items-center text-bg-primary border-0 position-fixed bottom-0 end-0 m-3';
  toast.setAttribute('role', 'status');
  toast.style.zIndex = '1080';
  toast.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
  toast.querySelector('.toast-body').textContent = text;
  document.body.appendChild(toast);
  toast.addEventListener('hidden.bs.toast', () => toast.remove());
  bootstrap.Toast.getOrCreateInstance(toast, { delay: 6000 }).show();
};

const setupLiveNotifications = () => {
  const link = document.querySelector('#inboxLink');
  if (!link) return;
  let known = parseInt(link.dataset.unread, 10) || 0;

  const check = async () => {
    if (document.hidden) return;
    try {
      const response = await fetch(link.dataset.endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      const data = await response.json();
      if (!response.ok || !data.ok) return;

      let badge = document.querySelector('#inboxBadge');
      if (data.unread > 0 && !badge) {
        badge = Object.assign(document.createElement('span'), {
          id: 'inboxBadge', className: 'badge rounded-pill text-bg-danger ms-1 ms-lg-0 position-lg-absolute',
        });
        link.appendChild(badge);
      }
      if (badge) {
        badge.textContent = data.unread;
        badge.classList.toggle('d-none', data.unread === 0);
      }
      link.setAttribute('aria-label', data.unread ? `Inquiries, ${data.unread} new` : 'Inquiries');
      if (data.unread > known && data.latest) showToast(data.latest);
      known = data.unread;
    } catch {
      // Offline or server busy: try again next time.
    }
  };

  setInterval(check, NOTIFY_MS);
  document.addEventListener('visibilitychange', check);
};

document.addEventListener('DOMContentLoaded', () => {
  autoDismissFlashMessages();
  setupThemeToggle();
  setupLiveNotifications();
});

window.addEventListener('load', registerServiceWorker);
