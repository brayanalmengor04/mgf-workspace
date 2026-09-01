import * as echarts from 'echarts/core';
import { LineChart, PieChart, GaugeChart, BarChart } from 'echarts/charts';
import {
    GridComponent,
    TooltipComponent,
    LegendComponent,
    TitleComponent,
    DatasetComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import { LabelLayout, UniversalTransition } from 'echarts/features';

echarts.use([
    LineChart,
    PieChart,
    GaugeChart,
    BarChart,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    TitleComponent,
    DatasetComponent,
    CanvasRenderer,
    LabelLayout,
    UniversalTransition,
]);

const registry = new Map();

const palette = {
    primary: '#465fff',
    primarySoft: 'rgba(70, 95, 255, 0.18)',
    teal: '#0d9488',
    amber: '#f59e0b',
    slate: '#64748b',
    success: '#10b981',
    danger: '#ef4444',
};

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function textColor() {
    return isDark() ? '#e2e8f0' : '#334155';
}

function mutedColor() {
    return isDark() ? '#94a3b8' : '#64748b';
}

function gridColor() {
    return isDark() ? 'rgba(148, 163, 184, 0.15)' : 'rgba(148, 163, 184, 0.25)';
}

function getChart(el) {
    if (typeof el === 'string') {
        el = document.getElementById(el);
    }

    if (! el) {
        return null;
    }

    if (registry.has(el)) {
        return registry.get(el);
    }

    const chart = echarts.init(el, null, { renderer: 'canvas' });
    registry.set(el, chart);

    const resize = () => chart.resize();
    window.addEventListener('resize', resize);

    return chart;
}

function disposeChart(el) {
    if (typeof el === 'string') {
        el = document.getElementById(el);
    }

    if (! el || ! registry.has(el)) {
        return;
    }

    registry.get(el).dispose();
    registry.delete(el);
}

function formatMoney(value) {
    const n = Number(value);

    if (Number.isNaN(n)) {
        return String(value);
    }

    return new Intl.NumberFormat('es-PA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(n);
}

function renderTrend(el, { labels = [], data = [], label = 'Tendencia' } = {}) {
    const chart = getChart(el);

    if (! chart) {
        return null;
    }

    chart.setOption({
        animationDuration: 900,
        animationEasing: 'cubicOut',
        grid: { left: 12, right: 16, top: 24, bottom: 8, containLabel: true },
        tooltip: {
            trigger: 'axis',
            backgroundColor: isDark() ? '#1e293b' : '#ffffff',
            borderColor: isDark() ? '#334155' : '#e2e8f0',
            textStyle: { color: textColor(), fontSize: 12 },
            formatter: (params) => {
                const point = params?.[0];

                if (! point) {
                    return '';
                }

                return `<strong>${point.axisValue}</strong><br/>${label}: ${formatMoney(point.data)}`;
            },
        },
        xAxis: {
            type: 'category',
            data: labels,
            boundaryGap: false,
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { color: mutedColor(), fontSize: 11 },
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: gridColor() } },
            axisLabel: {
                color: mutedColor(),
                fontSize: 11,
                formatter: (v) => formatMoney(v),
            },
        },
        series: [{
            name: label,
            type: 'line',
            smooth: 0.35,
            symbol: 'circle',
            symbolSize: 7,
            showSymbol: labels.length <= 12,
            lineStyle: { width: 3, color: palette.primary },
            itemStyle: { color: palette.primary, borderWidth: 2, borderColor: '#fff' },
            areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(70, 95, 255, 0.35)' },
                    { offset: 1, color: 'rgba(70, 95, 255, 0.02)' },
                ]),
            },
            data,
        }],
    }, true);

    return chart;
}

function renderDonut(el, { labels = [], data = [], colors = [] } = {}) {
    const chart = getChart(el);

    if (! chart) {
        return null;
    }

    const seriesData = labels.map((name, i) => ({
        name,
        value: data[i] ?? 0,
        itemStyle: colors[i] ? { color: colors[i] } : undefined,
    }));

    chart.setOption({
        animationDuration: 1100,
        animationEasing: 'elasticOut',
        tooltip: {
            trigger: 'item',
            backgroundColor: isDark() ? '#1e293b' : '#ffffff',
            borderColor: isDark() ? '#334155' : '#e2e8f0',
            textStyle: { color: textColor(), fontSize: 12 },
            formatter: ({ name, value, percent }) => `${name}<br/><strong>${formatMoney(value)}</strong> (${percent}%)`,
        },
        legend: {
            bottom: 0,
            icon: 'circle',
            itemWidth: 8,
            itemHeight: 8,
            textStyle: { color: mutedColor(), fontSize: 11 },
        },
        series: [{
            type: 'pie',
            radius: ['52%', '78%'],
            center: ['50%', '44%'],
            avoidLabelOverlap: true,
            itemStyle: {
                borderRadius: 8,
                borderColor: isDark() ? '#111827' : '#ffffff',
                borderWidth: 3,
            },
            label: { show: false },
            emphasis: {
                scale: true,
                scaleSize: 10,
                label: {
                    show: true,
                    fontSize: 13,
                    fontWeight: 700,
                    color: textColor(),
                    formatter: '{b}\n{d}%',
                },
            },
            data: seriesData,
        }],
    }, true);

    return chart;
}

function renderRose(el, { labels = [], data = [], colors = [] } = {}) {
    const chart = getChart(el);

    if (! chart) {
        return null;
    }

    chart.setOption({
        animationDuration: 1000,
        tooltip: {
            trigger: 'item',
            backgroundColor: isDark() ? '#1e293b' : '#ffffff',
            borderColor: isDark() ? '#334155' : '#e2e8f0',
            textStyle: { color: textColor(), fontSize: 12 },
            formatter: ({ name, value, percent }) => `${name}<br/><strong>${formatMoney(value)}</strong> (${percent}%)`,
        },
        series: [{
            type: 'pie',
            radius: [24, 110],
            center: ['50%', '50%'],
            roseType: 'radius',
            itemStyle: { borderRadius: 6 },
            label: { color: mutedColor(), fontSize: 11 },
            data: labels.map((name, i) => ({
                name,
                value: data[i] ?? 0,
                itemStyle: colors[i] ? { color: colors[i] } : undefined,
            })),
        }],
    }, true);

    return chart;
}

function renderGauges(el, { items = [] } = {}) {
    const chart = getChart(el);

    if (! chart) {
        return null;
    }

    const count = Math.max(items.length, 1);
    const series = items.map((item, index) => {
        const percent = Math.min(100, Math.max(0, Number(item.percent ?? 0)));
        const centerX = ((index + 0.5) / count) * 100;
        const tone = percent >= 70 ? palette.success : (percent >= 40 ? palette.amber : palette.danger);

        return {
            type: 'gauge',
            center: [`${centerX}%`, '58%'],
            radius: count === 1 ? '88%' : '72%',
            startAngle: 200,
            endAngle: -20,
            min: 0,
            max: 100,
            progress: {
                show: true,
                width: 10,
                roundCap: true,
                itemStyle: { color: tone },
            },
            axisLine: {
                lineStyle: {
                    width: 10,
                    color: [[1, isDark() ? '#374151' : '#e2e8f0']],
                },
            },
            pointer: { show: false },
            axisTick: { show: false },
            splitLine: { show: false },
            axisLabel: { show: false },
            title: {
                offsetCenter: [0, '28%'],
                fontSize: 11,
                color: mutedColor(),
            },
            detail: {
                valueAnimation: true,
                offsetCenter: [0, '-4%'],
                fontSize: 18,
                fontWeight: 700,
                color: textColor(),
                formatter: '{value}%',
            },
            data: [{ value: percent, name: item.label ?? '' }],
        };
    });

    chart.setOption({
        animationDuration: 1200,
        animationEasing: 'cubicOut',
        series,
    }, true);

    return chart;
}

function renderPaidSplit(el, { paid = 0, pending = 0, labels = ['Pagado', 'Pendiente'] } = {}) {
    const chart = getChart(el);

    if (! chart) {
        return null;
    }

    chart.setOption({
        animationDuration: 900,
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            backgroundColor: isDark() ? '#1e293b' : '#ffffff',
            borderColor: isDark() ? '#334155' : '#e2e8f0',
            textStyle: { color: textColor(), fontSize: 12 },
            formatter: (params) => params.map((p) => `${p.seriesName}: ${formatMoney(p.data)}`).join('<br/>'),
        },
        grid: { left: 8, right: 8, top: 16, bottom: 8, containLabel: true },
        xAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: gridColor() } },
            axisLabel: { color: mutedColor(), formatter: (v) => formatMoney(v) },
        },
        yAxis: {
            type: 'category',
            data: ['Total'],
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { show: false },
        },
        series: [
            {
                name: labels[0],
                type: 'bar',
                stack: 'total',
                barWidth: 28,
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                        { offset: 0, color: '#10b981' },
                        { offset: 1, color: '#34d399' },
                    ]),
                    borderRadius: [6, 0, 0, 6],
                },
                data: [paid],
            },
            {
                name: labels[1],
                type: 'bar',
                stack: 'total',
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                        { offset: 0, color: '#f59e0b' },
                        { offset: 1, color: '#fbbf24' },
                    ]),
                    borderRadius: [0, 6, 6, 0],
                },
                data: [pending],
            },
        ],
    }, true);

    return chart;
}

window.MgfCrmCharts = {
    palette,
    getChart,
    disposeChart,
    renderTrend,
    renderDonut,
    renderRose,
    renderGauges,
    renderPaidSplit,
};

document.addEventListener('livewire:navigated', () => {
    registry.forEach((chart) => chart.resize());
});
