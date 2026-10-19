// Site-wide JavaScript.

// Feature: flash messages fade out by themselves after 5 seconds.
const autoDismissFlashMessages = () => {
  document.querySelectorAll('.flash-message').forEach((alertEl) => {
    setTimeout(() => {
      bootstrap.Alert.getOrCreateInstance(alertEl).close();
    }, 5000);
  });
};

document.addEventListener('DOMContentLoaded', () => {
  autoDismissFlashMessages();
});
