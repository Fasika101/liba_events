import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Expose jQuery/Bootstrap globals for AdminLTE widgets
import $ from 'jquery';
window.$ = window.jQuery = $;
import 'popper.js';
import 'bootstrap';
