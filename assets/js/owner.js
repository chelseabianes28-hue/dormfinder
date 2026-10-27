// My listings page: fill the delete confirmation modal with the chosen listing.
const setupDeleteModal = () => {
  const modal = document.querySelector('#deleteModal');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', (event) => {
    const { id, title } = event.relatedTarget.dataset;
    modal.querySelector('#deleteId').value = id;
    modal.querySelector('#deleteTitle').textContent = title;
  });
};

document.addEventListener('DOMContentLoaded', setupDeleteModal);
