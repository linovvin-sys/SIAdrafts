// escHtml() is shared globally via Frontend/Js/Student/shared.js, loaded
// on every Professor page before this file (see Include/footer.php).
document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect       = document.getElementById('materialClassSelect');
  const emptyState        = document.getElementById('materialEmptyState');
  const panel             = document.getElementById('materialPanel');
  // Set only on course_detail.php's Materials tab -- see assignments.js
  // for the same pattern.
  const fixedScheduleId   = document.body.dataset.scheduleId || null;
  const materialList      = document.getElementById('materialList');
  const materialForm      = document.getElementById('materialForm');
  const materialTitleEl   = document.getElementById('materialTitleInput');
  const materialTypeEl    = document.getElementById('materialTypeSelect');
  const materialFileGroup = document.getElementById('materialFileGroup');
  const materialFileEl    = document.getElementById('materialFileInput');
  const materialUrlGroup  = document.getElementById('materialUrlGroup');
  const materialUrlEl     = document.getElementById('materialUrlInput');
  const materialBodyGroup = document.getElementById('materialBodyGroup');
  const materialBodyEl    = document.getElementById('materialBodyInput');
  const materialVisibleFromEl = document.getElementById('materialVisibleFromInput');
  const materialErrBox    = document.getElementById('materialFormError');
  const materialErrMsg    = document.getElementById('materialFormErrorMsg');
  const materialPostBtn   = document.getElementById('materialPostBtn');
  const cancelMaterialEditBtn = document.getElementById('cancelMaterialEditBtn');

  if (!classSelect && !fixedScheduleId) return;

  let currentScheduleId = null;
  let lastMaterials = [];
  let editingMaterialId = null;

  function syncMaterialTypeFields() {
    const type = materialTypeEl.value;
    materialFileGroup.hidden = type !== 'file';
    materialUrlGroup.hidden = type !== 'link';
    materialBodyGroup.hidden = type !== 'text';
  }
  materialTypeEl?.addEventListener('change', syncMaterialTypeFields);

  function resetMaterialForm() {
    editingMaterialId = null;
    materialForm.reset();
    materialTypeEl.disabled = false;
    materialTypeEl.value = 'file';
    materialFileGroup.querySelector('label').textContent = 'File';
    syncMaterialTypeFields();
    materialPostBtn.querySelector('.sp-btn-label').textContent = 'Post material';
    cancelMaterialEditBtn.hidden = true;
  }

  function startEditMaterial(materialId) {
    const m = lastMaterials.find(x => String(x.material_id) === String(materialId));
    if (!m) return;
    editingMaterialId = materialId;
    materialTitleEl.value = m.title || '';
    materialTypeEl.value = m.type;
    materialTypeEl.disabled = true; // type can't change on edit
    syncMaterialTypeFields();
    materialUrlEl.value = m.type === 'link' ? (m.url || '') : '';
    materialBodyEl.value = m.type === 'text' ? (m.body || '') : '';
    materialVisibleFromEl.value = m.visible_from || '';
    if (m.type === 'file') materialFileGroup.querySelector('label').textContent = 'Replace file (optional — leave blank to keep current file)';
    materialErrBox.classList.remove('is-visible');
    materialPostBtn.querySelector('.sp-btn-label').textContent = 'Save changes';
    cancelMaterialEditBtn.hidden = false;
    materialTitleEl.focus();
  }

  cancelMaterialEditBtn?.addEventListener('click', resetMaterialForm);

  function showMaterialError(message) {
    materialErrBox.classList.remove('is-visible');
    void materialErrBox.offsetWidth;
    materialErrMsg.textContent = message;
    materialErrBox.classList.add('is-visible');
  }

  const materialTypeIcon = { file: 'mdi:file-outline', link: 'mdi:link-variant', text: 'mdi:text-box-outline' };
  const materialTypeBadge = { file: 'File', link: 'Link', text: 'Note' };

  function renderMaterials(items) {
    lastMaterials = items || [];
    if (!items || items.length === 0) {
      materialList.innerHTML = '<li class="sp-announce-empty">No materials posted for this class yet.</li>';
      return;
    }
    const today = new Date().toISOString().slice(0, 10);
    materialList.innerHTML = items.map((m, i) => {
      var contentHtml = '';
      if (m.type === 'file') {
        contentHtml = '<a href="' + API + 'Materials/download_material.php?material_id=' + m.material_id + '" target="_blank" rel="noopener">' + escHtml(m.file_name) + '</a>';
      } else if (m.type === 'link') {
        contentHtml = '<a href="' + escHtml(m.url) + '" target="_blank" rel="noopener">' + escHtml(m.url) + '</a>';
      } else {
        contentHtml = escHtml(m.body).replace(/\n/g, '<br>');
      }
      var meta = m.visible_from && m.visible_from > today
        ? 'Hidden from students until ' + m.visible_from
        : new Date(m.created_at.replace(' ', 'T')).toLocaleDateString([], { month: 'short', day: 'numeric' });
      return '<li class="sp-announce-item type-' + m.type + '" data-badge="' + (materialTypeBadge[m.type] || '') + '" style="--row-i:' + i + '" data-material-id="' + m.material_id + '">' +
        '<div class="sp-announce-item-head">' +
          '<iconify-icon icon="' + materialTypeIcon[m.type] + '"></iconify-icon>' +
          '<strong>' + escHtml(m.title) + '</strong>' +
          '<span class="sp-announce-item-date">' + escHtml(meta) + '</span>' +
          '<button type="button" class="sp-announce-delete" data-edit-material="' + m.material_id + '" aria-label="Edit material">' +
            '<iconify-icon icon="mdi:pencil-outline"></iconify-icon>' +
          '</button>' +
          '<button type="button" class="sp-announce-delete" data-delete-material="' + m.material_id + '" aria-label="Delete material">' +
            '<iconify-icon icon="mdi:trash-can-outline"></iconify-icon>' +
          '</button>' +
        '</div>' +
        '<p>' + contentHtml + '</p>' +
      '</li>';
    }).join('');
  }

  async function loadMaterials(scheduleId) {
    materialList.innerHTML = '<li class="sp-announce-empty"><span class="sp-loading-dots"><span></span><span></span><span></span></span></li>';
    try {
      const res = await fetch(API + 'Materials/get_class_materials.php?schedule_id=' + encodeURIComponent(scheduleId));
      const data = await res.json();
      if (data.error) {
        materialList.innerHTML = '<li class="sp-announce-empty">' + escHtml(data.error) + '</li>';
        return;
      }
      renderMaterials(data.materials);
    } catch (err) {
      materialList.innerHTML = '<li class="sp-announce-empty">Could not load materials.</li>';
    }
  }

  function initForClass(scheduleId) {
    currentScheduleId = scheduleId;
    if (panel) panel.hidden = false;
    if (emptyState) emptyState.hidden = true;
    resetMaterialForm();
    materialErrBox.classList.remove('is-visible');
    loadMaterials(currentScheduleId);
  }

  classSelect?.addEventListener('change', () => {
    window.spRememberClassSelection?.(classSelect);
    const scheduleId = classSelect.value || null;
    if (!scheduleId) {
      if (panel) panel.hidden = true;
      if (emptyState) emptyState.hidden = false;
      currentScheduleId = null;
      return;
    }
    initForClass(scheduleId);
  });

  materialForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    const isEditing = editingMaterialId !== null;
    materialPostBtn.disabled = true;
    materialPostBtn.querySelector('.sp-btn-spinner').hidden = false;
    materialPostBtn.querySelector('.sp-btn-label').textContent = isEditing ? 'Saving…' : 'Posting…';

    const body = new FormData();
    if (isEditing) body.append('material_id', editingMaterialId);
    body.append('schedule_id', currentScheduleId);
    body.append('title', materialTitleEl.value);
    body.append('type', materialTypeEl.value);
    body.append('visible_from', materialVisibleFromEl.value);
    body.append('csrf_token', csrfToken);
    if (materialTypeEl.value === 'file' && materialFileEl.files[0]) {
      body.append('material_file', materialFileEl.files[0]);
    } else if (materialTypeEl.value === 'link') {
      body.append('url', materialUrlEl.value);
    } else if (materialTypeEl.value === 'text') {
      body.append('body', materialBodyEl.value);
    }

    try {
      const endpoint = isEditing ? 'Materials/update_material.php' : 'Materials/post_material.php';
      const res = await fetch(API + endpoint, { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        showMaterialError(data.error);
        return;
      }
      renderMaterials(data.materials);
      resetMaterialForm();
      if (window.spToast) window.spToast(isEditing ? 'Material updated.' : 'Material posted.', 'mdi:folder-multiple-outline');
    } catch (err) {
      showMaterialError('Something went wrong. Please try again.');
    } finally {
      materialPostBtn.disabled = false;
      materialPostBtn.querySelector('.sp-btn-spinner').hidden = true;
      materialPostBtn.querySelector('.sp-btn-label').textContent = editingMaterialId ? 'Save changes' : 'Post material';
    }
  });

  materialList?.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit-material]');
    if (editBtn) {
      startEditMaterial(editBtn.getAttribute('data-edit-material'));
      return;
    }

    const delBtn = e.target.closest('[data-delete-material]');
    if (!delBtn) return;
    const materialId = delBtn.getAttribute('data-delete-material');

    const confirmed = window.spConfirm
      ? await window.spConfirm('This material will be removed for every student in this class.', { title: 'Delete this material?', confirmLabel: 'Delete' })
      : window.confirm('Delete this material?');
    if (!confirmed) return;

    const body = new FormData();
    body.append('material_id', materialId);
    body.append('csrf_token', csrfToken);

    try {
      const res = await fetch(API + 'Materials/delete_material.php', { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        if (window.spToast) window.spToast(data.error, 'mdi:alert-circle-outline');
        return;
      }
      loadMaterials(currentScheduleId);
      if (window.spToast) window.spToast('Material deleted.', 'mdi:trash-can-outline');
    } catch (err) {
      if (window.spToast) window.spToast('Could not delete material.', 'mdi:alert-circle-outline');
    }
  });

  if (classSelect) {
    if (window.spRestoreClassSelection?.(classSelect)) {
      classSelect.dispatchEvent(new Event('change'));
    }
  } else if (fixedScheduleId) {
    initForClass(fixedScheduleId);
  }

});
