<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import CurrencyCard from './CurrencyCard.vue';

const props = defineProps({
    baseCurrencyCode: { type: String, required: true },
    translations: { type: Object, required: true },
});

// The job that stores new rates runs every 10 minutes; poll well below
// that so the page picks up a fresh run promptly.
const POLL_INTERVAL_MS = 60_000;

const currencies = ref([]);
const status = ref('loading');
const refreshing = ref(false);
let pollTimer = null;

async function loadRates({ background = false } = {}) {
    if (background) {
        refreshing.value = true;
    } else {
        status.value = 'loading';
    }

    try {
        const response = await fetch('/api/rates/today', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Unexpected status ${response.status}`);
        }

        const data = await response.json();
        currencies.value = data.currencies;
        status.value = 'ready';
    } catch {
        if (!background) {
            status.value = 'error';
        }
    } finally {
        refreshing.value = false;
    }
}

onMounted(() => {
    loadRates();
    pollTimer = window.setInterval(() => loadRates({ background: true }), POLL_INTERVAL_MS);
});

onUnmounted(() => {
    window.clearInterval(pollTimer);
});
</script>

<template>
    <div class="page">
        <header class="page__header">
            <h1 class="page__title">{{ translations.title }}</h1>
            <p class="page__subtitle">{{ translations.subtitle.replace(':base', baseCurrencyCode) }}</p>
        </header>

        <p v-if="status === 'loading'" class="page__message">{{ translations.loading }}</p>
        <p v-else-if="status === 'error'" class="page__message page__message--error">{{ translations.error }}</p>
        <p v-else-if="currencies.length === 0" class="page__message">{{ translations.empty }}</p>

        <div v-else class="page__grid" :class="{ 'page__grid--refreshing': refreshing }">
            <CurrencyCard
                v-for="(currency, index) in currencies"
                :key="currency.code"
                :currency="currency"
                :base-currency-code="baseCurrencyCode"
                :translations="translations"
                :color-index="index"
            />
        </div>
    </div>
</template>

<style scoped>
.page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 24px 64px;
}

.page__header {
    margin-bottom: 32px;
}

.page__title {
    margin: 0 0 4px;
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -0.01em;
}

.page__subtitle {
    margin: 0;
    color: var(--text-secondary);
    font-size: 15px;
}

.page__message {
    color: var(--text-secondary);
    font-size: 15px;
}

.page__message--error {
    color: var(--delta-down);
}

.page__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    transition: opacity 150ms ease;
}

.page__grid--refreshing {
    opacity: 0.6;
}
</style>
