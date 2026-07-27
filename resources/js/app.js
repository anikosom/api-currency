import { createApp } from 'vue';
import App from './components/App.vue';

const el = document.getElementById('app');

createApp(App, {
    baseCurrencyCode: el.dataset.baseCurrency,
    translations: JSON.parse(el.dataset.translations),
}).mount(el);
