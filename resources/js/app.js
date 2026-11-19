import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Table rows with data-href open their record; links, buttons and form fields inside keep their own behaviour.
document.addEventListener('click', (event) => {
    const row = event.target.closest('tr[data-href]');

    if (!row || event.target.closest('a, button, input, select, textarea, label, form') || window.getSelection().toString()) {
        return;
    }

    if (event.ctrlKey || event.metaKey) {
        window.open(row.dataset.href, '_blank');
    } else {
        window.location.href = row.dataset.href;
    }
});
