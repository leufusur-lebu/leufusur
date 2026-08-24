import 'trix';

import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.css';

flatpickr.localize(Spanish);
window.flatpickr = flatpickr;
