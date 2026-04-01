import './bootstrap';
import './globals/theme.js'; /* By Sheaf.dev */
import './globals/modals.js';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import anchor from '@alpinejs/anchor';
import rover from '@sheaf/rover';

window.Alpine = Alpine;
window.Livewire = Livewire;

Alpine.plugin(anchor);
Alpine.plugin(rover);

import './components/autocomplete.js';
import './components/combobox.js';
import './components/select.js';
import './components/slider.js';

Livewire.start();
