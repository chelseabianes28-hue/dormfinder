// Inquiries page: owners accept or decline a student's inquiry.
const inqPage = document.querySelector('#inquiriesPage');

const STATUS_VIEW = { accepted: ['Accepted', 'success'], declined: ['Declined', 'secondary'] };

const showAlert = (type, text) => {
  const box = document.querySelector('#pageAlert');
  box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert"><span></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
  box.querySelector('span').textContent = text;
};

document.addEventListener('click', async (event) => {
  const button = event.target.closest('.inq-action');
  if (!button || !inqPage) return;

  const actions = button.closest('.inq-actions');
  actions.querySelectorAll('button').forEach((b) => { b.disabled = true; });

  const body = new FormData();
  body.append('csrf_token', inqPage.dataset.csrf);
  body.append('inquiry_id', button.dataset.id);
  body.append('action', button.dataset.action);

  try {
    const response = await fetch('api/inquiry_update.php', { method: 'POST', body, headers: { Accept: 'application/json' } });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || 'Request failed.');

    const [label, color] = STATUS_VIEW[result.status];
    const badge = button.closest('[data-inquiry]').querySelector('.inq-status');
    badge.textContent = label;
    badge.className = `badge text-bg-${color} inq-status`;
    actions.remove();
    showAlert('success', `Inquiry ${result.status}. The student was notified.`);
  } catch (error) {
    actions.querySelectorAll('button').forEach((b) => { b.disabled = false; });
    showAlert('danger', error.message || 'Unable to connect to the server.');
  }
});
