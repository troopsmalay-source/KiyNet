document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.querySelector('.topbar-search');
    const searchToggle = document.querySelector('.topbar-search-toggle');
    const searchInput = searchForm?.querySelector('input[type="search"]');
    searchToggle?.addEventListener('click', () => {
        const isOpen = searchForm.classList.toggle('search-open');
        searchToggle.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) searchInput?.focus();
    });

    const revenue = window.kinetDashboardRevenue;
    const canvas = document.getElementById('revenueComparisonChart');
    const rangeButtons = document.querySelectorAll('[data-revenue-range]');
    if (!revenue || !canvas) return;

    if (!window.Chart) {
        const message = document.createElement('p');
        message.className = 'dash-chart-error';
        message.textContent = 'Grafik tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
        canvas.replaceWith(message);
        return;
    }

    const money = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });
    const compact = new Intl.NumberFormat('id-ID', {
        notation: 'compact',
        maximumFractionDigits: 1
    });

    const chartTheme = () => (document.documentElement.dataset.theme === 'dark'
        ? { grid: 'rgba(148, 163, 184, 0.22)', ticks: '#94a3b8', legend: '#cbd5e1' }
        : { grid: 'rgba(148, 163, 184, 0.18)', ticks: '#94a3b8', legend: '#64748b' });
    const theme = chartTheme();

    const context = canvas.getContext('2d');
    const retailFill = context.createLinearGradient(0, 0, 0, 300);
    retailFill.addColorStop(0, 'rgba(59, 130, 246, 0.28)');
    retailFill.addColorStop(0.55, 'rgba(59, 130, 246, 0.08)');
    retailFill.addColorStop(1, 'rgba(59, 130, 246, 0)');

    const wholesaleFill = context.createLinearGradient(0, 0, 0, 300);
    wholesaleFill.addColorStop(0, 'rgba(139, 92, 246, 0.26)');
    wholesaleFill.addColorStop(0.55, 'rgba(139, 92, 246, 0.07)');
    wholesaleFill.addColorStop(1, 'rgba(139, 92, 246, 0)');

    const seriesFor = (range) => {
        const monthly = revenue.monthly ?? { labels: [], retail: [], wholesale: [] };

        if (range === 'month') {
            return revenue.daily ?? { labels: [], retail: [], wholesale: [] };
        }

        const months = range === '3m'
            ? 3
            : Math.min(12, Math.max(1, new Date().getMonth() + 1));

        return {
            labels: monthly.labels.slice(-months),
            retail: monthly.retail.slice(-months),
            wholesale: monthly.wholesale.slice(-months)
        };
    };

    const initial = seriesFor('month');

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: initial.labels,
            datasets: [
                {
                    label: 'Pendapatan Retail / Internal',
                    data: initial.retail,
                    borderColor: '#3b82f6',
                    backgroundColor: retailFill,
                    fill: true,
                    tension: 0.38,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBackgroundColor: '#3b82f6',
                    pointHitRadius: 12
                },
                {
                    label: 'Penjualan Bandwidth Mitra',
                    data: initial.wholesale,
                    borderColor: '#8b5cf6',
                    backgroundColor: wholesaleFill,
                    fill: true,
                    tension: 0.38,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBackgroundColor: '#8b5cf6',
                    pointHitRadius: 12
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 420, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 7,
                        boxHeight: 7,
                        padding: 16,
                        color: theme.legend,
                        font: { family: 'Inter', size: 10 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                    titleColor: '#f8fafc',
                    titleFont: { family: 'Inter', size: 11, weight: '600' },
                    bodyColor: '#cbd5e1',
                    bodyFont: { family: 'Inter', size: 11 },
                    footerColor: '#94a3b8',
                    borderColor: 'rgba(148, 163, 184, 0.18)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: { top: 10, right: 14, bottom: 10, left: 14 },
                    caretSize: 6,
                    caretPadding: 10,
                    boxPadding: 4,
                    usePointStyle: true,
                    displayColors: true,
                    callbacks: {
                        label: (item) => `${item.dataset.label}: ${money.format(item.parsed.y)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: theme.ticks,
                        maxRotation: 0,
                        autoSkipPadding: 12,
                        font: { family: 'Inter', size: 9 }
                    }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: {
                        color: theme.grid,
                        drawTicks: false,
                        borderDash: [5, 5],
                        lineWidth: 1
                    },
                    ticks: {
                        color: theme.ticks,
                        maxTicksLimit: 5,
                        padding: 8,
                        font: { family: 'Inter', size: 9 },
                        callback: (value) => compact.format(value)
                    }
                }
            }
        }
    });

    const chartWrap = canvas.parentElement;
    if (chartWrap && window.ResizeObserver) {
        let lastWidth = chartWrap.clientWidth;
        new ResizeObserver(() => {
            if (Math.abs(chartWrap.clientWidth - lastWidth) > 2) {
                lastWidth = chartWrap.clientWidth;
                chart.resize();
            }
        }).observe(chartWrap);
    }

    new MutationObserver(() => {
        const next = chartTheme();
        chart.options.plugins.legend.labels.color = next.legend;
        chart.options.scales.x.ticks.color = next.ticks;
        chart.options.scales.y.ticks.color = next.ticks;
        chart.options.scales.y.grid.color = next.grid;
        chart.update('none');
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    rangeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const range = button.dataset.revenueRange;
            const next = seriesFor(range);

            chart.data.labels = next.labels;
            chart.data.datasets[0].data = next.retail;
            chart.data.datasets[1].data = next.wholesale;
            chart.update('active');

            rangeButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });
        });
    });
});
