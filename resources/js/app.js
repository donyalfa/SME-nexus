import Alpine from 'alpinejs';
import {
    Chart,
    RadarController,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(RadarController, RadialLinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

/* ------------------------------------------------------------------ *
 * Radar chart: any <canvas data-radar-chart data-payload="{json}">
 * payload = { labels: [], datasets: [{ label, data: [], kind: current|previous }] }
 * ------------------------------------------------------------------ */
const STYLES = {
    current: {
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15, 118, 110, 0.18)',
        pointBackgroundColor: '#0f766e',
        borderWidth: 2.5,
    },
    previous: {
        borderColor: '#94a3b8',
        backgroundColor: 'rgba(148, 163, 184, 0.08)',
        pointBackgroundColor: '#94a3b8',
        borderWidth: 1.5,
        borderDash: [5, 4],
    },
};

function initRadarCharts() {
    document.querySelectorAll('canvas[data-radar-chart]').forEach((canvas) => {
        let payload;
        try {
            payload = JSON.parse(canvas.dataset.payload);
        } catch (e) {
            return;
        }

        new Chart(canvas, {
            type: 'radar',
            data: {
                labels: payload.labels,
                datasets: payload.datasets.map((d) => ({
                    label: d.label,
                    data: d.data,
                    pointRadius: 3,
                    ...(STYLES[d.kind] ?? STYLES.current),
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: { stepSize: 25, backdropColor: 'transparent', color: '#64748b', font: { size: 10 } },
                        grid: { color: '#e2e8f0' },
                        angleLines: { color: '#e2e8f0' },
                        pointLabels: { color: '#14213d', font: { size: 13, weight: '500' } },
                    },
                },
                plugins: {
                    legend: { display: payload.datasets.length > 1, position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.r.toFixed(0)}/100` } },
                },
            },
        });
    });
}

/* ------------------------------------------------------------------ *
 * Detect form: live preview of the headline ratios while typing.
 * ------------------------------------------------------------------ */
const AMOUNT_FIELDS = [
    'revenue', 'cogs', 'operating_expenses',
    'current_assets', 'current_liabilities',
    'cash_balance', 'receivables', 'payables', 'inventory_value',
];

Alpine.data('detectForm', (old = {}) => ({
    f: Object.fromEntries(AMOUNT_FIELDS.map((k) => [k, old[k] ?? ''])),

    n(key) {
        const v = parseFloat(this.f[key]);
        return Number.isFinite(v) ? v : 0;
    },
    get currentRatio() {
        return this.n('current_liabilities') > 0
            ? (this.n('current_assets') / this.n('current_liabilities')).toFixed(2)
            : '-';
    },
    get netMargin() {
        const rev = this.n('revenue');
        return rev > 0 ? (((rev - this.n('cogs') - this.n('operating_expenses')) / rev) * 100).toFixed(1) + '%' : '-';
    },
    get assetsMismatch() {
        return this.n('cash_balance') + this.n('receivables') + this.n('inventory_value') - this.n('current_assets') > 0.01;
    },
}));

window.Alpine = Alpine;
Alpine.start();
document.addEventListener('DOMContentLoaded', initRadarCharts);
