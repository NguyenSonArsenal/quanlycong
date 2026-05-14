/**
 * btmh.js – Giá Vàng Bảo Tín Mạnh Hải
 * Mọi dữ liệu đều lấy từ DB qua internal API – giống cách silver chart hoạt động
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── State ───────────────────────────────────────────────
    let btmhChartDays = 7;
    let btmhChartInstance = null;
    const btmhUnit = 'KGB';

    // ─── Elements ─────────────────────────────────────────────
    const elUpdated = document.getElementById('btmh-updated');
    const elBuy     = document.getElementById('btmh-buy');
    const elSell    = document.getElementById('btmh-sell');
    const elSpread  = document.getElementById('btmh-spread');
    const elLoading = document.getElementById('btmh-chart-loading');
    const elCanvas  = document.getElementById('btmhChart');

    if (!elCanvas) return;

    const ctx = elCanvas.getContext('2d');

    // ─── Helpers ──────────────────────────────────────────────
    const fmtVnd = (n) => new Intl.NumberFormat('vi-VN').format(n);

    function flashEl(el) {
        if (!el) return;
        el.classList.remove('btmh-price-flash');
        void el.offsetWidth; // reflow
        el.classList.add('btmh-price-flash');
    }

    // ─── Fetch giá hiện tại từ DB ─────────────────────────────
    function loadBtmhCurrent() {
        fetch('/api/gold/btmh/current')
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    if (elUpdated) elUpdated.textContent = 'Chưa có dữ liệu';
                    return;
                }

                const d = res.data[btmhUnit];
                if (!d) return;

                const buy    = d.buy_price;
                const sell   = d.sell_price;
                const spread = sell - buy;

                if (elBuy) {
                    const prev = parseInt(elBuy.dataset.raw || '0');
                    if (prev && prev !== buy) flashEl(elBuy);
                    elBuy.textContent = fmtVnd(buy);
                    elBuy.dataset.raw = buy;
                }
                if (elSell) {
                    const prev = parseInt(elSell.dataset.raw || '0');
                    if (prev && prev !== sell) flashEl(elSell);
                    elSell.textContent = fmtVnd(sell);
                    elSell.dataset.raw = sell;
                }
                if (elSpread)  elSpread.textContent  = fmtVnd(spread);
                if (elUpdated) elUpdated.textContent = d.recorded_at
                    ? `Cập nhật: ${d.recorded_at}`
                    : 'Đang tải...';
            })
            .catch(err => console.error('[BTMH] current price error:', err));
    }

    // ─── Fetch & Render Chart từ DB ───────────────────────────
    function loadBtmhChart() {
        if (elLoading) { elLoading.style.display = 'flex'; elLoading.innerHTML = '<div class="sv-spinner" style="border-top-color:#dc2626;"></div> Đang tải biểu đồ...'; }
        if (elCanvas)  elCanvas.style.display = 'none';

        fetch(`/api/gold/btmh/history?days=${btmhChartDays}&type=${btmhUnit}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.data || !res.data.dates.length) {
                    if (elLoading) elLoading.innerHTML = '<span style="color:#64748b;font-size:0.85rem">Chưa có dữ liệu cho khoảng này</span>';
                    return;
                }
                renderBtmhChart(res.data, res.type_label || 'Kim Gia Bảo 24K');
            })
            .catch(err => {
                console.error('[BTMH] chart error:', err);
                if (elLoading) elLoading.innerHTML = '<span style="color:#ef4444;font-size:0.85rem">Lỗi tải biểu đồ</span>';
            });
    }

    function renderBtmhChart(data, typeLabel) {
        if (btmhChartInstance) { btmhChartInstance.destroy(); btmhChartInstance = null; }

        if (elLoading) elLoading.style.display = 'none';
        if (elCanvas)  elCanvas.style.display  = 'block';

        const gradBuy  = ctx.createLinearGradient(0, 0, 0, 280);
        gradBuy.addColorStop(0, 'rgba(34,197,94,0.22)');
        gradBuy.addColorStop(1, 'rgba(34,197,94,0)');

        const gradSell = ctx.createLinearGradient(0, 0, 0, 280);
        gradSell.addColorStop(0, 'rgba(239,68,68,0.22)');
        gradSell.addColorStop(1, 'rgba(239,68,68,0)');

        // Tính y-axis padding thông minh
        const allPrices = [...data.buy_prices, ...data.sell_prices].filter(Boolean);
        const minP = Math.min(...allPrices);
        const maxP = Math.max(...allPrices);
        const pad  = Math.round((maxP - minP) * 0.15) || 300000;

        btmhChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [
                    {
                        label: 'Mua vào',
                        data: data.buy_prices,
                        borderColor: '#22c55e',
                        backgroundColor: gradBuy,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#22c55e',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    },
                    {
                        label: 'Bán ra',
                        data: data.sell_prices,
                        borderColor: '#ef4444',
                        backgroundColor: gradSell,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#ef4444',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        titleColor: '#f1f5f9',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(220,38,38,0.35)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            title: (items) => items[0]?.label || '',
                            label: (c) => ` ${c.dataset.label}: ${fmtVnd(c.parsed.y)} đ`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: {
                            color: '#64748b', font: { size: 11 },
                            maxTicksLimit: btmhChartDays <= 7 ? 7 : (btmhChartDays <= 30 ? 10 : 12),
                            maxRotation: 0,
                        }
                    },
                    y: {
                        min: minP - pad,
                        max: maxP + pad,
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: {
                            color: '#64748b', font: { size: 11 },
                            callback: (v) => v >= 1_000_000 ? (v / 1_000_000).toFixed(1) + 'tr' : fmtVnd(v)
                        }
                    }
                }
            }
        });
    }

    // ─── Period Tab Events ────────────────────────────────────
    document.querySelectorAll('.btmh-prd').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btmh-prd').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            btmhChartDays = parseInt(this.dataset.days, 10);
            loadBtmhChart();
        });
    });

    // ─── Init ─────────────────────────────────────────────────
    loadBtmhCurrent();
    loadBtmhChart();

    // Refresh giá mỗi 10 phút (đồng bộ với cron fetch-btmh)
    setInterval(loadBtmhCurrent, 10 * 60 * 1000);
});
