import Sortable from 'sortablejs';

window.Sortable = Sortable;

// Livewire bundles and starts its own Alpine instance. Hook into
// 'alpine:init' (fired before Livewire calls Alpine.start()) to register
// custom directives/plugins/stores instead of importing/starting Alpine here.
document.addEventListener('alpine:init', () => {
    // Returns focus to whatever opened a dialog once the dialog leaves the DOM.
    Alpine.directive('restore-focus', (el, _directive, { cleanup }) => {
        const opener = document.activeElement;

        cleanup(() => {
            if (opener && opener !== document.body && opener.isConnected) {
                opener.focus();
            }
        });
    });
});
