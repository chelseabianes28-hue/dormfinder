// Interactivity for the login and register pages.

const STRENGTH_LABELS = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'];
const STRENGTH_COLORS = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];

// Feature 1: show or hide the password.
const setupPasswordToggles = () => {
  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.querySelector(button.dataset.togglePassword);
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      button.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  });
};

// Feature 2: password strength indicator.
const scorePassword = (password) => {
  if (!password) return 0;
  let score = 0;
  if (password.length >= 8) score++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  if (/\d/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;
  return score;
};

const setupStrengthMeter = () => {
  const input = document.querySelector('#password');
  const bar = document.querySelector('#strengthBar');
  const track = document.querySelector('#strengthTrack');
  const text = document.querySelector('#strengthText');
  if (!input || !bar || !track || !text) return;

  input.addEventListener('input', () => {
    const score = scorePassword(input.value);
    bar.style.width = `${score * 25}%`;
    bar.className = `progress-bar ${STRENGTH_COLORS[score]}`;
    track.setAttribute('aria-valuenow', String(score * 25));
    text.textContent = STRENGTH_LABELS[score];
  });
};

// Feature 3: live check that both passwords match.
const setupConfirmCheck = () => {
  const password = document.querySelector('#password');
  const confirm = document.querySelector('#confirm_password');
  const help = document.querySelector('#confirmHelp');
  if (!password || !confirm || !help) return;

  const check = () => {
    if (!confirm.value) {
      help.textContent = '';
      return;
    }
    const matches = password.value === confirm.value;
    help.textContent = matches ? 'Passwords match.' : 'Passwords do not match yet.';
    help.className = `form-text ${matches ? 'text-success' : 'text-danger'}`;
  };

  password.addEventListener('input', check);
  confirm.addEventListener('input', check);
};

document.addEventListener('DOMContentLoaded', () => {
  setupPasswordToggles();
  setupStrengthMeter();
  setupConfirmCheck();
});
