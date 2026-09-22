import Sortable from 'sortablejs';

window.Sortable = Sortable;

// Livewire bundles and starts its own Alpine instance. Hook into
// 'alpine:init' (fired before Livewire calls Alpine.start()) to register
// custom directives/plugins/stores instead of importing/starting Alpine here.
document.addEventListener('alpine:init', () => {
    //
});
