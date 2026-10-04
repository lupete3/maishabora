<script>
// Une confirmation explicite avant les ecritures ; les autorisations sont controlees sur le serveur.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-stock-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            // Bloquer aussi une seconde soumission au clavier pendant la navigation.
            if (form.dataset.stockSubmitting === 'true') { event.preventDefault(); return; }
            const details = [];
            const items = form.querySelectorAll('input[name="items[]"]:checked');
            if (form.querySelector('input[name="items[]"]')) {
                if (!items.length) { event.preventDefault(); window.alert('Sélectionnez au moins un carnet.'); return; }
                details.push(`${items.length} carnet(s) sélectionné(s)`);
            }
            ['action', 'batch_id', 'collector_id', 'reference', 'supplier', 'received_at', 'quantity', 'number_start', 'number_end', 'range_start', 'range_end', 'printed_number', 'reason'].forEach(name => {
                const field = form.elements.namedItem(name);
                if (field && !field.disabled && field.value) {
                    const label = field.closest('div')?.querySelector('label')?.textContent.trim() || name;
                    const value = field.tagName === 'SELECT' ? field.selectedOptions[0].textContent.trim() : field.value;
                    details.push(`${label} : ${value}`);
                }
            });
            const recovered = form.elements.namedItem('recovered');
            if (recovered) details.push(recovered.checked ? 'Carnet récupéré : retour au stock' : 'Carnet non récupéré : déclaré perdu');
            if (!window.confirm(form.dataset.stockConfirm + '\n\n' + details.join('\n'))) {
                event.preventDefault(); return;
            }
            // Activer le chargement uniquement apres validation et confirmation.
            form.dataset.stockSubmitting = 'true';
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"]').forEach(button => {
                if (button.disabled) return;
                button.dataset.stockLoading = 'true';
                if (button.tagName === 'INPUT') {
                    button.dataset.stockOriginalLabel = button.value;
                    button.value = 'En cours…';
                } else {
                    button.dataset.stockOriginalLabel = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span><span role="status">En cours…</span>';
                }
                button.disabled = true;
            });
        });
    });
});
// Le retour navigateur peut restaurer une page avec ses boutons encore bloques.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-stock-confirm]').forEach(form => {
        delete form.dataset.stockSubmitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-stock-loading]').forEach(button => {
            if (button.tagName === 'INPUT') button.value = button.dataset.stockOriginalLabel;
            else button.innerHTML = button.dataset.stockOriginalLabel;
            button.disabled = false;
            delete button.dataset.stockLoading;
            delete button.dataset.stockOriginalLabel;
        });
    });
});
</script>
