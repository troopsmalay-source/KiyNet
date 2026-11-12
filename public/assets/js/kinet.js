document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const root = document.documentElement;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const themeIcon = themeToggle?.querySelector('i');
    const toastElement = document.getElementById('kinetToast');
    const toastMessage = document.querySelector('[data-toast-message]');

    const showToast = (message) => {
        if (!toastElement || !toastMessage || !window.bootstrap) return;
        toastMessage.textContent = message;
        window.bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3200 }).show();
    };

    const updateThemeButton = () => {
        const isDark = root.dataset.theme === 'dark';
        themeIcon?.classList.replace(isDark ? 'bi-moon-stars' : 'bi-sun', isDark ? 'bi-sun' : 'bi-moon-stars');
        themeToggle?.setAttribute('aria-label', `Aktifkan mode ${isDark ? 'siang' : 'malam'}`);
        themeToggle?.setAttribute('title', `Aktifkan mode ${isDark ? 'siang' : 'malam'}`);
        themeToggle?.setAttribute('aria-pressed', String(isDark));
    };

    updateThemeButton();
    themeToggle?.addEventListener('click', () => {
        const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = theme;
        updateThemeButton();
        try {
            window.localStorage.setItem('kinet.theme', theme);
        } catch (error) {
            console.error('Preferensi tema tidak dapat disimpan:', error);
            showToast('Mode berubah, tetapi preferensi tidak dapat disimpan di browser ini.');
        }
    });

    toggle?.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 960px)').matches) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
        }
    });

    backdrop?.addEventListener('click', () => body.classList.remove('sidebar-open'));
    document.querySelectorAll('[data-toast]').forEach((button) => {
        button.addEventListener('click', () => showToast(button.dataset.toast));
    });

    const reminderMessages = {
        whatsapp: 'Yth. Pelanggan KiNet,\nTagihan internet Anda akan segera jatuh tempo.\nSilakan periksa detail tagihan pada aplikasi KiNet.\nTerima kasih.',
        email: 'Subjek: Pengingat tagihan KiNet\n\nYth. Pelanggan,\nInformasi tagihan internet Anda tersedia di aplikasi KiNet.\nTerima kasih.',
        sms: 'KiNet: Informasi tagihan internet Anda tersedia. Silakan buka aplikasi KiNet untuk melihat detail. Terima kasih.'
    };

    document.querySelectorAll('[data-preview-tabs]').forEach((group) => {
        group.querySelectorAll('[data-preview-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                group.querySelectorAll('[data-preview-tab]').forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');
                const message = reminderMessages[tab.dataset.previewTab];
                const textarea = document.querySelector('[data-reminder-message]');
                if (textarea && message) textarea.value = message;
            });
        });
    });

    document.querySelectorAll('[data-payment-tabs]').forEach((group) => {
        group.querySelectorAll('[data-payment-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                group.querySelectorAll('[data-payment-tab]').forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');
                document.querySelectorAll('[data-payment-panel]').forEach((panel) => {
                    panel.hidden = panel.dataset.paymentPanel !== tab.dataset.paymentTab;
                });
            });
        });
    });

    const canvas = document.getElementById('paymentChart');
    const rangeSelect = document.getElementById('chartRange');
    const dashboard = window.kinetDashboard;
    if (!canvas || !rangeSelect || !dashboard) return;

    if (!window.Chart) {
        const message = document.createElement('p');
        message.className = 'chart-load-error';
        message.textContent = 'Grafik tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
        canvas.replaceWith(message);
        return;
    }

    const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: dashboard.initialData.labels,
            datasets: [
                {
                    label: 'Tagihan',
                    data: dashboard.initialData.tagihan,
                    borderColor: '#2176e8',
                    backgroundColor: '#2176e8',
                    pointBackgroundColor: '#2176e8',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.34
                },
                {
                    label: 'Pembayaran',
                    data: dashboard.initialData.pembayaran,
                    borderColor: '#10b981',
                    backgroundColor: '#10b981',
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.34
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 7,
                        boxHeight: 7,
                        padding: 17,
                        color: '#66758c',
                        font: { family: 'DM Sans', size: 10 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#8794a9', maxTicksLimit: 7, maxRotation: 0, font: { family: 'DM Sans', size: 9 } }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false, dash: [3, 4] },
                    grid: { color: '#edf1f6', drawTicks: false },
                    ticks: {
                        color: '#8794a9',
                        maxTicksLimit: 5,
                        padding: 9,
                        font: { family: 'DM Sans', size: 9 },
                        callback: (value) => new Intl.NumberFormat('id-ID', {
                            notation: 'compact',
                            maximumFractionDigits: 1
                        }).format(value)
                    }
                }
            }
        }
    });
    let selectedRange = dashboard.initialRange;

    rangeSelect.addEventListener('change', async () => {
        const range = rangeSelect.value;
        rangeSelect.disabled = true;

        try {
            const url = new URL(dashboard.chartUrl, window.location.origin);
            url.searchParams.set('range', range);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Permintaan grafik gagal (${response.status}).`);
            }

            const data = await response.json();
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.tagihan;
            chart.data.datasets[1].data = data.pembayaran;
            chart.update();
            selectedRange = range;
        } catch (error) {
            showToast(error.message || 'Grafik tidak dapat diperbarui.');
            rangeSelect.value = selectedRange;
        } finally {
            rangeSelect.disabled = false;
        }
    });
});
