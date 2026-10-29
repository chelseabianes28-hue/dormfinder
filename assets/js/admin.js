// Admin pages: approve or reject listings, enable or disable users (Fetch API).
const adminPage = document.querySelector('#adminPage');

const showAlert = (type, text) => {
  const box = document.querySelector('#pageAlert');
  box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert"><span></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
  box.querySelector('span').textContent = text;
};

const LISTING_BADGE = { approved: ['Approved', 'success'], rejected: ['Rejected', 'danger'] };

const updateRow = (row, button, result) => {
  const badge = row.querySelector('.row-status');

  if (result.status) {                                   // a listing changed status
    if (adminPage.dataset.removeOnChange === '1') { row.remove(); return; }
    const [label, color] = LISTING_BADGE[result.status];
    badge.textContent = label;
    badge.className = `badge text-bg-${color} ms-1 row-status`;
    row.querySelector('[data-action="approve_listing"]').classList.toggle('d-none', result.status === 'approved');
    row.querySelector('[data-action="reject_listing"]').classList.toggle('d-none', result.status === 'rejected');
    return;
  }
  // a user was enabled or disabled
  badge.textContent = result.active ? 'Active' : 'Disabled';
  badge.className = `badge text-bg-${result.active ? 'success' : 'secondary'} row-status`;
  button.textContent = result.active ? 'Disable' : 'Enable';
  button.dataset.action = result.active ? 'disable_user' : 'enable_user';
  button.className = `btn btn-sm ${result.active ? 'btn-outline-danger' : 'btn-outline-secondary'} admin-act`;
};

document.addEventListener('click', async (event) => {
  const button = event.target.closest('.admin-act');
  if (!button || !adminPage) return;

  const row = button.closest('[data-row]');
  const { action, id } = button.dataset;
  const name = row.dataset.title || row.dataset.name;

  if (action === 'reject_listing' && !confirm(`Reject "${name}"? Students will no longer see it.`)) return;
  if (action === 'disable_user' && !confirm(`Disable ${name}? They will not be able to log in.`)) return;

  button.disabled = true;
  const body = new FormData();
  body.append('csrf_token', adminPage.dataset.csrf);
  body.append('action', action);
  body.append('id', id);

  try {
    const response = await fetch(adminPage.dataset.endpoint, { method: 'POST', body, headers: { Accept: 'application/json' } });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || 'Request failed.');

    updateRow(row, button, result);
    showAlert('success', 'Done.');
  } catch (error) {
    showAlert('danger', error.message || 'Unable to connect to the server.');
  } finally {
    button.disabled = false;
  }
});
