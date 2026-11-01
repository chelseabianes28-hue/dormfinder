// Site-wide JavaScript.

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

document.addEventListener('DOMContentLoaded', () => {
  autoDismissFlashMessages();
  setupThemeToggle();
});
