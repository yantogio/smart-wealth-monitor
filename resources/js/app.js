import { Chart } from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-price-chart]').forEach((canvas) => {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const smaValue = canvas.dataset.sma ? parseFloat(canvas.dataset.sma) : null;

        const datasets = [{
            label: 'Harga Penutupan',
            data: values,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderWidth: 2,
            pointRadius: values.length > 60 ? 0 : 2,
            pointHoverRadius: 5,
            pointBackgroundColor: '#2563eb',
            tension: 0.25,
            fill: true,
        }];

        if (smaValue !== null && !Number.isNaN(smaValue)) {
            datasets.push({
                label: 'SMA (7 hari)',
                data: labels.map(() => smaValue),
                borderColor: '#f59e0b',
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
            });
        }

        new Chart(canvas, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${Number(context.parsed.y).toLocaleString('id-ID', { maximumFractionDigits: 2 })}`,
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: false },
                        ticks: { maxTicksLimit: 8, autoSkip: true, color: '#6b7280', font: { size: 11 } },
                    },
                    y: {
                        display: true,
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            color: '#6b7280',
                            font: { size: 11 },
                            callback: (value) => Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 }),
                        },
                    },
                },
            },
        });
    });
});
