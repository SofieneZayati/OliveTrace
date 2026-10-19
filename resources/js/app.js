import './bootstrap';

import Alpine from 'alpinejs';
import { farmMap } from './farm-map';

window.Alpine = Alpine;
Alpine.data('farmMap', farmMap);

Alpine.start();
