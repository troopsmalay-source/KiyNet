document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.querySelector('.topbar-search');
    const searchToggle = document.querySelector('.topbar-search-toggle');
    const searchInput = searchForm?.querySelector('input[type="search"]');
    searchToggle?.addEventListener('click', () => {
        const isOpen = searchForm.classList.toggle('search-open');
        searchToggle.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) searchInput?.focus();
    });

    const dashboard = window.kinetDashboard;
    const revenueCanvas = document.getElementById('revenueChart');
    const customerCanvas = document.getElementById('customerChart');
    const rangeSelect = document.getElementById('chartRange');
    if (!dashboard || !revenueCanvas || !customerCanvas || !rangeSelect) return;

    if (!window.Chart) {
        [revenueCanvas, customerCanvas].forEach((canvas) => {
            const message = document.createElement('p');
            message.className = 'chart-load-error';
            message.textContent = 'Grafik tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
            canvas.replaceWith(message);
        });
        return;
    }

    const palette = {
        blue: '#2563eb',
        green: '#16a34a',
        orange: '#f59e0b',
        grid: '#eef2f7',
        text: '#94a3b8'
    };
    const money = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });
    const compactNumber = new Intl.NumberFormat('id-ID', {
        notation: 'compact',
        maximumFractionDigits: 1
    });
    const revenueGradient = revenueCanvas.getContext('2d').createLinearGradient(0, 0, 0, 240);
    revenueGradient.addColorStop(0, 'rgb(37 99 235 / 20%)');
    revenueGradient.addColorStop(1, 'rgb(37 99 235 / 0%)');
    const paymentGradient = revenueCanvas.getContext('2d').createLinearGradient(0, 0, 0, 240);
    paymentGradient.addColorStop(0, 'rgb(22 163 74 / 15%)');
    paymentGradient.addColorStop(1, 'rgb(22 163 74 / 0%)');

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
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
                    color: '#64748b',
                    font: { family: 'Inter', size: 10 }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: palette.text, maxTicksLimit: 7, maxRotation: 0, font: { family: 'Inter', size: 9 } }
            },
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: palette.grid, drawTicks: false },
                ticks: {
                    color: palette.text,
                    maxTicksLimit: 5,
                    padding: 8,
                    font: { family: 'Inter', size: 9 },
                    callback: (value) => compactNumber.format(value)
                }
            }
        }
    };

    const revenueChart = new Chart(revenueCanvas, {
        type: 'line',
        data: {
            labels: dashboard.initialData.labels,
            datasets: [
                {
                    label: 'Tagihan',
                    data: dashboard.initialData.tagihan,
                    borderColor: palette.blue,
                    backgroundColor: revenueGradient,
                    pointBackgroundColor: palette.blue,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderWidth: 2.5,
                    tension: 0.38,
                    fill: true
                },
                {
                    label: 'Pembayaran berhasil',
                    data: dashboard.initialData.pembayaran,
                    borderColor: palette.green,
                    backgroundColor: paymentGradient,
                    pointBackgroundColor: palette.green,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                    tension: 0.38,
                    fill: true
                }
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${money.format(context.parsed.y)}` } }
            }
        }
    });

    const customerChart = new Chart(customerCanvas, {
        type: 'bar',
        data: {
            labels: dashboard.initialData.labels,
            datasets: [
                {
                    label: 'Pelanggan baru',
                    data: dashboard.initialData.pelangganBaru,
                    backgroundColor: 'rgb(37 99 235 / 82%)',
                    borderColor: palette.blue,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Aktif saat ini',
                    data: dashboard.initialData.pelangganAktif,
                    backgroundColor: 'rgb(22 163 74 / 78%)',
                    borderColor: palette.green,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Terisolir saat ini',
                    data: dashboard.initialData.pelangganTerisolir,
                    backgroundColor: 'rgb(245 158 11 / 82%)',
                    borderColor: palette.orange,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }
            ]
        },
        options: {
            ...commonOptions,
            datasets: {
                bar: {
                    categoryPercentage: 0.68,
                    barPercentage: 0.78,
                    maxBarThickness: 18
                }
            },
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${new Intl.NumberFormat('id-ID').format(context.parsed.y)} pelanggan`
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    ticks: {
                        ...commonOptions.scales.y.ticks,
                        precision: 0,
                        callback: (value) => new Intl.NumberFormat('id-ID').format(value)
                    }
                }
            }
        }
    });

    let selectedRange = dashboard.initialRange;
    rangeSelect.addEventListener('change', async () => {
        rangeSelect.disabled = true;

        try {
            const url = new URL(dashboard.chartUrl, window.location.origin);
            url.searchParams.set('range', rangeSelect.value);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`Permintaan grafik gagal (${response.status}).`);

            const data = await response.json();
            revenueChart.data.labels = data.labels;
            revenueChart.data.datasets[0].data = data.tagihan;
            revenueChart.data.datasets[1].data = data.pembayaran;
            customerChart.data.labels = data.labels;
            customerChart.data.datasets[0].data = data.pelangganBaru;
            customerChart.data.datasets[1].data = data.pelangganAktif;
            customerChart.data.datasets[2].data = data.pelangganTerisolir;
            revenueChart.update();
            customerChart.update();
            selectedRange = rangeSelect.value;
        } catch (error) {
            const toastElement = document.getElementById('kinetToast');
            const toastMessage = document.querySelector('[data-toast-message]');
            if (toastElement && toastMessage && window.bootstrap) {
                toastMessage.textContent = error.message || 'Grafik tidak dapat diperbarui.';
                window.bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3200 }).show();
            }
            rangeSelect.value = selectedRange;
        } finally {
            rangeSelect.disabled = false;
        }
    });
});
