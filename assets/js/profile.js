// Profile page: check the chosen photo and show a preview before saving.
const MAX_AVATAR_BYTES = 2 * 1024 * 1024;
const AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

const setupAvatarPreview = () => {
  const input = document.querySelector('#avatar');
  const errorBox = document.querySelector('#avatarError');
  const box = document.querySelector('#avatarBox');
  if (!input || !errorBox || !box) return;

  input.addEventListener('change', () => {
    errorBox.textContent = '';
    const [file] = input.files;
    if (!file) return;

    if (!AVATAR_TYPES.includes(file.type)) {
      errorBox.textContent = 'Please choose a JPG, PNG, or WebP image.';
      input.value = '';
      return;
    }
    if (file.size > MAX_AVATAR_BYTES) {
      errorBox.textContent = 'That photo is larger than 2 MB. Please choose a smaller one.';
      input.value = '';
      return;
    }

    let img = box.querySelector('#avatarImg');
    if (!img) {                                   // replace the placeholder icon with a photo
      box.innerHTML = '';
      img = Object.assign(document.createElement('img'), { id: 'avatarImg', className: 'rounded-circle border', width: 160, height: 160 });
      img.style.objectFit = 'cover';
      box.appendChild(img);
    }
    img.alt = 'Preview of the new profile photo';
    img.src = URL.createObjectURL(file);
  });
};

document.addEventListener('DOMContentLoaded', setupAvatarPreview);
