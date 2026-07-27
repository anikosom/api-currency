<script setup>
import { computed, ref } from 'vue';
import RateChart from './RateChart.vue';
import { formatRate, formatTime } from './format';

const props = defineProps({
    currency: { type: Object, required: true },
    baseCurrencyCode: { type: String, required: true },
    translations: { type: Object, required: true },
    colorIndex: { type: Number, required: true },
});

const PALETTE = [
    { light: '#2a78d6', dark: '#3987e5' },
    { light: '#eb6834', dark: '#d95926' },
    { light: '#1baf7a', dark: '#199e70' },
];

const color = computed(() => PALETTE[props.colorIndex % PALETTE.length]);

const view = ref('chart');

const points = computed(() => props.currency.points);
const latest = computed(() => points.value[points.value.length - 1] ?? null);
const first = computed(() => points.value[0] ?? null);

const changePercent = computed(() => {
    if (!first.value || !latest.value || first.value.rate === 0) {
        return null;
    }

    return ((latest.value.rate - first.value.rate) / first.value.rate) * 100;
});

function formatPercent(value) {
    const sign = value > 0 ? '+' : '';

    return `${sign}${value.toFixed(2)}%`;
}
</script>

<template>
    <section class="card">
        <header class="card__header">
            <div>
                <h2 class="card__title">{{ currency.code }}</h2>
                <p class="card__name">{{ currency.name }}</p>
            </div>
            <div class="card__toggle" role="group">
                <button
                    type="button"
                    class="card__toggle-btn"
                    :class="{ 'card__toggle-btn--active': view === 'chart' }"
                    @click="view = 'chart'"
                >{{ translations.chart_view }}</button>
                <button
                    type="button"
                    class="card__toggle-btn"
                    :class="{ 'card__toggle-btn--active': view === 'table' }"
                    @click="view = 'table'"
                >{{ translations.table_view }}</button>
            </div>
        </header>

        <div v-if="latest" class="card__stat">
            <span class="card__rate">
                <span class="sr-only">{{ translations.current_rate }}: </span>{{ formatRate(latest.rate) }} {{ baseCurrencyCode }}
            </span>
            <span
                v-if="changePercent !== null"
                class="card__delta"
                :class="changePercent >= 0 ? 'card__delta--up' : 'card__delta--down'"
                :aria-label="`${translations.change_today}: ${formatPercent(changePercent)}`"
            >{{ formatPercent(changePercent) }}</span>
        </div>

        <RateChart
            v-if="view === 'chart'"
            :points="points"
            :color="color"
            :currency-code="currency.code"
            :base-currency-code="baseCurrencyCode"
        />

        <div v-else class="card__table-wrap">
            <table class="card__table">
                <caption class="card__table-caption">{{ currency.code }} / {{ baseCurrencyCode }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ translations.time_column }}</th>
                        <th scope="col">{{ translations.rate_column }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="point in points" :key="point.time">
                        <td>{{ formatTime(point.time) }}</td>
                        <td>{{ formatRate(point.rate) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="latest" class="card__updated">{{ translations.updated_at.replace(':time', formatTime(latest.time)) }}</p>
    </section>
</template>

<style scoped>
.card {
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.card__title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.card__name {
    margin: 2px 0 0;
    font-size: 13px;
    color: var(--text-secondary);
}

.card__toggle {
    display: inline-flex;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.card__toggle-btn {
    border: none;
    background: transparent;
    color: var(--text-secondary);
    font-size: 12px;
    font-family: inherit;
    padding: 5px 10px;
    cursor: pointer;
}

.card__toggle-btn--active {
    background: var(--gridline);
    color: var(--text-primary);
}

.card__stat {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
}

.card__rate {
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.01em;
}

.card__delta {
    font-size: 13px;
    font-weight: 600;
}

.card__delta--up {
    color: var(--delta-up);
}

.card__delta--down {
    color: var(--delta-down);
}

.card__table-wrap {
    max-height: 200px;
    overflow-y: auto;
}

.card__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    font-variant-numeric: tabular-nums;
}

.card__table-caption {
    caption-side: top;
    text-align: left;
    color: var(--text-secondary);
    font-size: 12px;
    padding-bottom: 6px;
}

.card__table th,
.card__table td {
    text-align: left;
    padding: 6px 4px;
    border-bottom: 1px solid var(--gridline);
    color: var(--text-primary);
}

.card__table th {
    color: var(--text-muted);
    font-weight: 500;
}

.card__updated {
    margin: 0;
    font-size: 12px;
    color: var(--text-muted);
}
</style>
