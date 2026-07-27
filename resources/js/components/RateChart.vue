<script setup>
import { computed, ref } from 'vue';
import { formatRate, formatTime } from './format';

const props = defineProps({
    points: { type: Array, required: true },
    color: { type: Object, required: true },
    currencyCode: { type: String, required: true },
    baseCurrencyCode: { type: String, required: true },
});

const width = 300;
const height = 170;
const padding = { top: 12, right: 12, bottom: 22, left: 54 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = height - padding.top - padding.bottom;

const minTime = computed(() => Math.min(...props.points.map((p) => new Date(p.time).getTime())));
const maxTime = computed(() => Math.max(...props.points.map((p) => new Date(p.time).getTime())));

const minRate = computed(() => Math.min(...props.points.map((p) => p.rate)));
const maxRate = computed(() => Math.max(...props.points.map((p) => p.rate)));

const rateSpan = computed(() => maxRate.value - minRate.value);
const rateRange = computed(() => (rateSpan.value === 0
    ? Math.max(Math.abs(maxRate.value) * 0.01, 0.0001)
    : rateSpan.value * 1.15));
const rateMinDomain = computed(() => {
    const buffer = rateSpan.value === 0 ? rateRange.value / 2 : rateSpan.value * 0.075;

    return minRate.value - buffer;
});

function xFor(time) {
    const domain = maxTime.value - minTime.value || 1;

    return padding.left + ((time - minTime.value) / domain) * innerWidth;
}

function yFor(rate) {
    return padding.top + innerHeight - ((rate - rateMinDomain.value) / rateRange.value) * innerHeight;
}

const coords = computed(() => props.points.map((point) => ({
    x: xFor(new Date(point.time).getTime()),
    y: yFor(point.rate),
    time: point.time,
    rate: point.rate,
})));

const linePath = computed(() => coords.value
    .map((c, i) => `${i === 0 ? 'M' : 'L'} ${c.x.toFixed(2)} ${c.y.toFixed(2)}`)
    .join(' '));

const areaPath = computed(() => {
    if (coords.value.length === 0) {
        return '';
    }

    const baseline = padding.top + innerHeight;
    const first = coords.value[0];
    const last = coords.value[coords.value.length - 1];

    return `${linePath.value} L ${last.x.toFixed(2)} ${baseline} L ${first.x.toFixed(2)} ${baseline} Z`;
});

const yTicks = computed(() => {
    const steps = 3;

    return Array.from({ length: steps + 1 }, (_, i) => {
        const rate = rateMinDomain.value + (rateRange.value * i) / steps;

        return { rate, y: yFor(rate) };
    });
});

const hoverIndex = ref(null);
const hovered = computed(() => (hoverIndex.value === null ? null : coords.value[hoverIndex.value]));

function handlePointerMove(event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * width;

    let closest = 0;
    let closestDistance = Infinity;

    coords.value.forEach((c, i) => {
        const distance = Math.abs(c.x - x);

        if (distance < closestDistance) {
            closestDistance = distance;
            closest = i;
        }
    });

    hoverIndex.value = closest;
}

function handlePointerLeave() {
    hoverIndex.value = null;
}

const tooltipStyle = computed(() => {
    if (!hovered.value) {
        return {};
    }

    const clampedX = Math.min(Math.max(hovered.value.x, 55), width - 55);

    return {
        left: `${(clampedX / width) * 100}%`,
        top: `${(hovered.value.y / height) * 100}%`,
    };
});
</script>

<template>
    <div
        class="chart"
        :style="{ '--series-light': color.light, '--series-dark': color.dark }"
    >
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="chart__svg"
            role="img"
            :aria-label="`${currencyCode} to ${baseCurrencyCode} rate today`"
            @pointermove="handlePointerMove"
            @pointerleave="handlePointerLeave"
        >
            <line
                v-for="tick in yTicks"
                :key="`grid-${tick.rate}`"
                class="chart__gridline"
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="tick.y"
                :y2="tick.y"
            />
            <text
                v-for="tick in yTicks"
                :key="`label-${tick.rate}`"
                class="chart__tick-label"
                :x="padding.left - 8"
                :y="tick.y"
                text-anchor="end"
                dominant-baseline="middle"
            >{{ formatRate(tick.rate) }}</text>

            <path class="chart__area" :d="areaPath" />
            <path class="chart__line" :d="linePath" />

            <circle
                v-if="coords.length"
                class="chart__end-dot"
                :cx="coords[coords.length - 1].x"
                :cy="coords[coords.length - 1].y"
                r="5"
            />

            <template v-if="hovered">
                <line
                    class="chart__crosshair"
                    :x1="hovered.x"
                    :x2="hovered.x"
                    :y1="padding.top"
                    :y2="padding.top + innerHeight"
                />
                <circle class="chart__hover-dot" :cx="hovered.x" :cy="hovered.y" r="5" />
            </template>

            <text class="chart__tick-label" :x="padding.left" :y="height - 4">{{ formatTime(points[0]?.time) }}</text>
            <text
                class="chart__tick-label"
                :x="width - padding.right"
                :y="height - 4"
                text-anchor="end"
            >{{ formatTime(points[points.length - 1]?.time) }}</text>
        </svg>

        <div v-if="hovered" class="chart__tooltip" :style="tooltipStyle">
            <strong class="chart__tooltip-value">{{ formatRate(hovered.rate) }} {{ baseCurrencyCode }}</strong>
            <span class="chart__tooltip-time">{{ formatTime(hovered.time) }}</span>
        </div>
    </div>
</template>

<style scoped>
.chart {
    position: relative;
}

.chart__svg {
    width: 100%;
    height: auto;
    display: block;
    touch-action: none;
}

.chart__gridline {
    stroke: var(--gridline);
    stroke-width: 1;
}

.chart__tick-label {
    fill: var(--text-muted);
    font-size: 9px;
}

.chart__area {
    fill: var(--series-light);
    fill-opacity: 0.1;
}

.chart__line {
    fill: none;
    stroke: var(--series-light);
    stroke-width: 2;
    stroke-linejoin: round;
    stroke-linecap: round;
}

.chart__end-dot,
.chart__hover-dot {
    fill: var(--series-light);
    stroke: var(--surface-1);
    stroke-width: 2;
}

.chart__crosshair {
    stroke: var(--axis);
    stroke-width: 1;
}

@media (prefers-color-scheme: dark) {
    .chart__area,
    .chart__end-dot,
    .chart__hover-dot {
        fill: var(--series-dark);
    }

    .chart__line {
        stroke: var(--series-dark);
    }
}

.chart__tooltip {
    position: absolute;
    transform: translate(-50%, -120%);
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    gap: 2px;
    pointer-events: none;
    white-space: nowrap;
}

.chart__tooltip-value {
    font-size: 13px;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
}

.chart__tooltip-time {
    font-size: 11px;
    color: var(--text-secondary);
}
</style>
