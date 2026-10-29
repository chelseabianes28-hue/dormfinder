// Favorites page: remove a listing from favorites without reloading.
const favPage = document.querySelector('#favoritesPage');

document.addEventListener('click', async (event) => {
  const button = event.target.closest('.fav-remove');
  if (!button || !favPage) return;

  button.disabled = true;
  const body = new FormData();
  body.append('csrf_token', favPage.dataset.csrf);
  body.append('listing_id', button.dataset.id);

  try {
    const response = await fetch('api/favorite_toggle.php', { method: 'POST', body, headers: { Accept: 'application/json' } });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || 'Request failed.');

    button.closest('.fav-item').remove();
    if (!document.querySelector('.fav-item')) location.reload();   // shows the empty message
  } catch (error) {
    button.disabled = false;
    const box = document.querySelector('#pageAlert');
    box.innerHTML = '<div class="alert alert-danger" role="alert"></div>';
    box.firstChild.textContent = error.message || 'Unable to connect to the server.';
  }
});
