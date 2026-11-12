(function () {
    'use strict';

    var toneByStatus = {
        Pending: 'pending',
        Aktif: 'aktif',
        Menunggak: 'menunggak',
        Isolir: 'isolir',
        Suspend: 'suspend',
        Berhenti: 'berhenti'
    };

    function badgeTone(status) {
        return 'cst-badge cst-badge--lg cst-badge--' + (toneByStatus[status] || 'netral');
    }

    document.querySelectorAll('[data-toggle-secret]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.dataset.toggleSecret);
            if (!target) {
                return;
            }

            var revealed = target.dataset.revealed === 'true';
            target.textContent = revealed ? '••••••••' : (target.dataset.secretValue || '');
            target.dataset.revealed = revealed ? 'false' : 'true';
            button.setAttribute('aria-label', revealed ? 'Tampilkan password PPPoE' : 'Sembunyikan password PPPoE');
            button.setAttribute('aria-pressed', revealed ? 'false' : 'true');

            var icon = button.querySelector('i');
            if (icon) {
                icon.className = revealed ? 'bi bi-eye' : 'bi bi-eye-slash';
            }
        });
    });

    var editModal = document.getElementById('cstEditModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger || !trigger.dataset.cstEdit) {
                return;
            }

            var data;
            try {
                data = JSON.parse(trigger.dataset.cstEdit);
            } catch (error) {
                return;
            }

            var form = editModal.querySelector('form');
            if (form && trigger.dataset.cstUpdateUrl) {
                form.action = trigger.dataset.cstUpdateUrl;
            }

            Object.keys(data).forEach(function (key) {
                var field = form ? form.elements.namedItem(key) : null;
                if (field) {
                    field.value = data[key] === null ? '' : data[key];
                }
            });

            var subject = editModal.querySelector('[data-cst-edit-subject]');
            if (subject) {
                subject.textContent = data.id + ' · ' + data.nama;
            }
        });
    }

    var statusModal = document.getElementById('cstStatusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger || !trigger.dataset.cstStatusUrl) {
                return;
            }

            var form = statusModal.querySelector('form');
            if (form) {
                form.action = trigger.dataset.cstStatusUrl;
            }

            var subject = statusModal.querySelector('[data-cst-status-subject]');
            if (subject) {
                subject.textContent = (trigger.dataset.cstName || '') + ' · ' + (trigger.dataset.cstId || '');
            }

            var badge = statusModal.querySelector('[data-cst-current-status]');
            if (badge) {
                var status = trigger.dataset.cstCurrentStatus || 'Pending';
                badge.className = badgeTone(status);
                badge.dataset.status = status;
                badge.textContent = '';

                var dot = document.createElement('span');
                dot.className = 'cst-badge-dot';
                badge.appendChild(dot);
                badge.appendChild(document.createTextNode(' ' + status));
            }

            var select = statusModal.querySelector('select[name="status"]');
            if (select) {
                select.value = trigger.dataset.cstCurrentStatus || 'Pending';
            }

            var reason = statusModal.querySelector('textarea[name="reason"]');
            if (reason) {
                reason.value = '';
            }
        });
    }

    var perPageSelect = document.querySelector('[data-cst-per-page]');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', perPageSelect.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    if (window.cstOpenCreateModal && document.getElementById('cstCreateModal')) {
        new bootstrap.Modal(document.getElementById('cstCreateModal')).show();
    }

    if (window.cstOpenEditModal && editModal) {
        new bootstrap.Modal(editModal).show();
    }
})();
