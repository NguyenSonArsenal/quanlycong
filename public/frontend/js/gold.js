/**
 * gold.js - Gia Vang Thuong Hieu (BTMC + BTMH + Phu Quy + SJC) - Bang ke + Shared Chart
 */
document.addEventListener('DOMContentLoaded', function () {

    // State
    let activeBrand    = 'btmc';
    let activeUnit     = { btmc: 'NHAN_TRON', btmh: 'KGB', phuquy: 'NHAN_TRON', sjc: 'VANG_MIEN' };
    let chartDays      = 7;
    let chartInstance  = null;

    // API endpoints
    const API = {
        btmc:   { current: '/api/gold/btmc/current',   history: '/api/gold/btmc/history'   },
        btmh:   { current: '/api/gold/btmh/current',   history: '/api/gold/btmh/history'   },
        phuquy: { current: '/api/gold/phuquy/current', history: '/api/gold/phuquy/history' },
        sjc:    { current: '/api/gold/sjc/current',    history: '/api/gold/sjc/history'    },
    };

    // DOM refs
    const elLoading  = document.getElementById('gold-chart-loading');
    const elCanvas   = document.getElementById('goldSharedChart');
    const elUnitTabs = document.getElementById('gold-chart-unit-tabs');
    const elUnitLbl  = document.getElementById('gold-chart-unit-lbl');
    const elFootnote = document.getElementById('gold-chart-footnote');

    if (!elCanvas) return;
    const ctx = elCanvas.getContext('2d');

    const fmtVnd = (n) => new Intl.NumberFormat('vi-VN').format(n);

    // Unit tabs config per brand
    const unitTabsConfig = {
        btmc: [
            { unit: 'NHAN_TRON',  label: 'Nhan tron' },
            { unit: 'MIENG_VRTL', label: 'Vang mieng' },
        ],
        btmh:   null, // 1 loai, an tabs
        phuquy: [
            { unit: 'NHAN_TRON', label: 'Nhan tron' },
            { unit: 'SJC',       label: 'Vang mieng SJC' },
        ],
        sjc: [
            { unit: 'VANG_MIEN', label: 'Vang mieng' },
            { unit: 'NHAN_TRON', label: 'Vang nhan' },
        ],
    };

    // Load BTMC current price
    function loadBtmcCurrent() {
        fetch(API.btmc.current)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                window.btmcGoldData = res.data;
                renderBtmcTable();
            })
            .catch(err => console.error('[Gold] BTMC current error:', err));
    }

    function renderBtmcTable() {
        const data = window.btmcGoldData;
        if (!data) return;

        const nhan = data['NHAN_TRON'];
        if (nhan) {
            setEl('btmc-nhan-buy',     nhan.buy_formatted  || fmtVnd(nhan.buy_price));
            setEl('btmc-nhan-sell',    nhan.sell_formatted || fmtVnd(nhan.sell_price));
            setEl('btmc-nhan-spread',  fmtVnd(nhan.sell_price - nhan.buy_price));
            if (nhan.recorded_at) setEl('gold-btmc-updated', nhan.recorded_at);
            rawSellPrices['btmc_NHAN_TRON'] = nhan.sell_price;
        }

        const mieng = data['MIENG_VRTL'];
        if (mieng) {
            setEl('btmc-mieng-buy',    mieng.buy_formatted  || fmtVnd(mieng.buy_price));
            setEl('btmc-mieng-sell',   mieng.sell_formatted || fmtVnd(mieng.sell_price));
            setEl('btmc-mieng-spread', fmtVnd(mieng.sell_price - mieng.buy_price));
            if (mieng.recorded_at) setEl('gold-btmc-updated', mieng.recorded_at);
            rawSellPrices['btmc_MIENG_VRTL'] = mieng.sell_price;
        }
        updateHighLowBadges();
    }

    // Load BTMH current price
    function loadBtmhCurrent() {
        fetch(API.btmh.current)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data['KGB'];
                if (!d) return;
                setEl('btmh-buy',    d.buy_formatted  || fmtVnd(d.buy_price));
                setEl('btmh-sell',   d.sell_formatted || fmtVnd(d.sell_price));
                setEl('btmh-spread', fmtVnd(d.sell_price - d.buy_price));
                if (d.recorded_at) setEl('gold-btmh-updated', d.recorded_at);
                rawSellPrices['btmh_KGB'] = d.sell_price;
                updateHighLowBadges();
            })
            .catch(err => console.error('[Gold] BTMH current error:', err));
    }

    // Load Phu Quy current price
    function loadPhuquyCurrent() {
        fetch(API.phuquy.current)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                window.phuquyGoldData = res.data;
                renderPhuquyTable();
            })
            .catch(err => console.error('[Gold] Phu Quy current error:', err));
    }

    function renderPhuquyTable() {
        const data = window.phuquyGoldData;
        if (!data) return;

        const nhan = data['NHAN_TRON'];
        if (nhan) {
            setEl('pq-nhan-buy',    nhan.buy_formatted  || fmtVnd(nhan.buy_price));
            setEl('pq-nhan-sell',   nhan.sell_formatted || fmtVnd(nhan.sell_price));
            setEl('pq-nhan-spread', fmtVnd(nhan.sell_price - nhan.buy_price));
            if (nhan.recorded_at) setEl('gold-pq-updated', nhan.recorded_at);
            rawSellPrices['pq_NHAN_TRON'] = nhan.sell_price;
        }

        const sjc = data['SJC'];
        if (sjc) {
            setEl('pq-sjc-buy',    sjc.buy_formatted  || fmtVnd(sjc.buy_price));
            setEl('pq-sjc-sell',   sjc.sell_formatted || fmtVnd(sjc.sell_price));
            setEl('pq-sjc-spread', fmtVnd(sjc.sell_price - sjc.buy_price));
            if (sjc.recorded_at) setEl('gold-pq-updated', sjc.recorded_at);
            rawSellPrices['pq_SJC'] = sjc.sell_price;
        }
        updateHighLowBadges();
    }

    // Load SJC current price
    function loadSjcCurrent() {
        fetch(API.sjc.current)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                window.sjcGoldData = res.data;
                renderSjcTable();
            })
            .catch(err => console.error('[Gold] SJC current error:', err));
    }

    function renderSjcTable() {
        const data = window.sjcGoldData;
        if (!data) return;

        const mien = data['VANG_MIEN'];
        if (mien) {
            setEl('sjc-mien-buy',    mien.buy_formatted  || fmtVnd(mien.buy_price));
            setEl('sjc-mien-sell',   mien.sell_formatted || fmtVnd(mien.sell_price));
            setEl('sjc-mien-spread', fmtVnd(mien.sell_price - mien.buy_price));
            if (mien.recorded_at) setEl('gold-sjc-updated', mien.recorded_at);
            rawSellPrices['sjc_VANG_MIEN'] = mien.sell_price;
        }

        const nhan = data['NHAN_TRON'];
        if (nhan) {
            setEl('sjc-nhan-buy',    nhan.buy_formatted  || fmtVnd(nhan.buy_price));
            setEl('sjc-nhan-sell',   nhan.sell_formatted || fmtVnd(nhan.sell_price));
            setEl('sjc-nhan-spread', fmtVnd(nhan.sell_price - nhan.buy_price));
            if (nhan.recorded_at) setEl('gold-sjc-updated', nhan.recorded_at);
            rawSellPrices['sjc_NHAN_TRON'] = nhan.sell_price;
        }
        updateHighLowBadges();
    }

    function setEl(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    // Badge Cao nhat / Thap nhat - compare sell prices across all rows
    const rawSellPrices = {};

    function updateHighLowBadges() {
        const SELL_IDS = [
            { sellId: 'btmc-nhan-sell',  rawKey: 'btmc_NHAN_TRON'  },
            { sellId: 'btmc-mieng-sell', rawKey: 'btmc_MIENG_VRTL' },
            { sellId: 'btmh-sell',       rawKey: 'btmh_KGB'        },
            { sellId: 'pq-nhan-sell',    rawKey: 'pq_NHAN_TRON'    },
            { sellId: 'pq-sjc-sell',     rawKey: 'pq_SJC'          },
            { sellId: 'sjc-mien-sell',   rawKey: 'sjc_VANG_MIEN'   },
            { sellId: 'sjc-nhan-sell',   rawKey: 'sjc_NHAN_TRON'   },
        ];

        const entries     = SELL_IDS.map(r => ({ ...r, price: rawSellPrices[r.rawKey] || 0 }));
        const validPrices = entries.filter(r => r.price > 0).map(r => r.price);
        if (validPrices.length < 2) return;

        const maxPrice = Math.max(...validPrices);
        const minPrice = Math.min(...validPrices);

        entries.forEach(row => {
            const sellEl = document.getElementById(row.sellId);
            if (!sellEl) return;

            const existing = sellEl.parentElement.querySelectorAll('.gold-badge');
            existing.forEach(b => b.remove());

            if (!row.price) return;

            if (row.price === maxPrice) {
                const badge = document.createElement('span');
                badge.className = 'gold-badge gold-badge-high';
                badge.textContent = 'Cao nh\u1ea5t';
                sellEl.after(badge);
            } else if (row.price === minPrice) {
                const badge = document.createElement('span');
                badge.className = 'gold-badge gold-badge-low';
                badge.textContent = 'Th\u1ea5p nh\u1ea5t';
                sellEl.after(badge);
            }
        });
    }

    // Unit tabs per brand
    function updateUnitTabs(brand) {
        const tabs    = unitTabsConfig[brand];
        const curUnit = activeUnit[brand];

        if (!tabs) {
            if (elUnitTabs) elUnitTabs.style.visibility = 'hidden';
            if (elUnitLbl)  elUnitLbl.style.visibility  = 'hidden';
            return;
        }

        if (elUnitTabs) elUnitTabs.style.visibility = 'visible';
        if (elUnitLbl)  elUnitLbl.style.visibility  = 'visible';

        if (elUnitTabs) {
            elUnitTabs.innerHTML = tabs.map(t =>
                `<button class="gold-chart-unit-btn${t.unit === curUnit ? ' active' : ''}" data-unit="${t.unit}">${t.label}</button>`
            ).join('');

            elUnitTabs.querySelectorAll('.gold-chart-unit-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const unit = this.dataset.unit;
                    if (activeBrand !== brand) return;
                    setActiveRow(brand, unit);
                });
            });
        }
    }

    // Shared Chart
    function loadChart() {
        if (elLoading) { elLoading.style.display = 'flex'; elLoading.innerHTML = '<div class="sv-spinner" style="border-top-color:#f59e0b;"></div> Dang tai bieu do...'; }
        if (elCanvas)  elCanvas.style.display  = 'none';

        const brand = activeBrand;
        const unit  = activeUnit[brand];
        const url   = `${API[brand].history}?days=${chartDays}&type=${unit}`;

        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data?.dates?.length) {
                    renderChart(res.data, brand, unit);
                } else {
                    if (elLoading) elLoading.innerHTML = '<span style="color:#64748b;font-size:.85rem">Chua co du lieu bieu do</span>';
                }
            })
            .catch(() => {
                if (elLoading) elLoading.innerHTML = '<span style="color:#ef4444;font-size:.85rem">Loi tai bieu do</span>';
            });
    }

    function renderChart(data, brand, unit) {
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
        if (elLoading) elLoading.style.display = 'none';
        if (elCanvas)  elCanvas.style.display  = 'block';

        const gradBuy  = ctx.createLinearGradient(0, 0, 0, 320);
        gradBuy.addColorStop(0, 'rgba(34,197,94,0.18)');
        gradBuy.addColorStop(1, 'rgba(34,197,94,0)');

        const gradSell = ctx.createLinearGradient(0, 0, 0, 320);
        gradSell.addColorStop(0, 'rgba(239,68,68,0.18)');
        gradSell.addColorStop(1, 'rgba(239,68,68,0)');

        const allP = [...(data.buy_prices||[]), ...(data.sell_prices||[])].filter(Boolean);
        const minP = Math.min(...allP);
        const maxP = Math.max(...allP);
        const pad  = Math.round((maxP - minP) * 0.12) || 300000;

        // Số điểm dữ liệu xác định cách vẽ dot
        const numPoints = (data.dates || []).length;

        // Khi chỉ có 1–2 điểm: dot to hơn, vẫn vẽ đường thẳng nối giữa 2 điểm
        const isSparse     = numPoints <= 2;
        const isFewPoints  = numPoints <= 12;
        const pointRadius  = isSparse ? 4 : (isFewPoints ? 4 : 0);
        const pointHover   = isSparse ? 6 : (isFewPoints ? 6 : 5);
        const useFill      = !isSparse;   // 1–2 điểm: tắt fill gradient, chỉ vẽ đường + dot
        const useTension   = isSparse ? 0 : 0.3;  // tension=0 để đường thẳng khi ít điểm

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [
                    {
                        label: 'Mua vao', data: data.buy_prices,
                        borderColor: '#22c55e', backgroundColor: useFill ? gradBuy : 'transparent',
                        borderWidth: 2, tension: useTension, fill: useFill,
                        spanGaps: true,
                        pointRadius: pointRadius,
                        pointHoverRadius: pointHover,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: pointRadius > 0 ? 1.5 : 0,
                        pointHoverBackgroundColor: '#22c55e',
                    },
                    {
                        label: 'Ban ra', data: data.sell_prices,
                        borderColor: '#ef4444', backgroundColor: useFill ? gradSell : 'transparent',
                        borderWidth: 2, tension: useTension, fill: useFill,
                        spanGaps: true,
                        pointRadius: pointRadius,
                        pointHoverRadius: pointHover,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: pointRadius > 0 ? 1.5 : 0,
                        pointHoverBackgroundColor: '#ef4444',
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 350 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Inter', size: 12 }, boxWidth: 14 } },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)', titleColor: '#f1f5f9',
                        bodyColor: '#cbd5e1', borderColor: 'rgba(245,197,24,0.3)',
                        borderWidth: 1, padding: 11, cornerRadius: 8,
                        callbacks: { label: (c) => ` ${c.dataset.label}: ${fmtVnd(c.parsed.y)} d` }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: { color: '#64748b', font: { size: 11 }, maxTicksLimit: chartDays <= 7 ? 7 : (chartDays <= 30 ? 10 : 12), maxRotation: 0 }
                    },
                    y: {
                        min: minP - pad, max: maxP + pad,
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: { color: '#64748b', font: { size: 11 }, callback: (v) => v >= 1_000_000 ? (v / 1_000_000).toFixed(1) + 'tr' : fmtVnd(v) }
                    }
                }
            }
        });

        if (elFootnote) {
            const srcMap  = { btmc: 'Bao Tin Minh Chau', btmh: 'Bao Tin Manh Hai', phuquy: 'Phu Quy Group', sjc: 'SJC Official' };
            const unitMap = { btmc: 'VND/Luong', btmh: 'dong/chi', phuquy: 'dong/chi', sjc: 'VND/Luong' };
            elFootnote.textContent = `Nguon: ${srcMap[brand]} - ${unitMap[brand]}`;
        }
    }

    // Set active row + sync UI
    function setActiveRow(brand, unit) {
        activeBrand       = brand;
        activeUnit[brand] = unit;

        document.querySelectorAll('.gold-tr-clickable').forEach(tr => {
            tr.classList.toggle('active', tr.dataset.brand === brand && tr.dataset.unit === unit);
        });

        document.querySelectorAll('.gold-chart-brand').forEach(b => {
            b.classList.toggle('active', b.dataset.brand === brand);
        });

        updateUnitTabs(brand);
        loadChart();
    }

    // Click on table row
    document.querySelectorAll('.gold-tr-clickable').forEach(tr => {
        tr.addEventListener('click', function () {
            const brand = this.dataset.brand;
            const unit  = this.dataset.unit;
            if (activeBrand === brand && activeUnit[brand] === unit) return;
            setActiveRow(brand, unit);
        });
    });

    // Chart brand tab click
    document.querySelectorAll('.gold-chart-brand').forEach(btn => {
        btn.addEventListener('click', function () {
            const brand = this.dataset.brand;
            if (brand === activeBrand) return;
            setActiveRow(brand, activeUnit[brand]);
        });
    });

    // Period tabs
    document.querySelectorAll('.gold-prd').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.gold-prd').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            chartDays = parseInt(this.dataset.days, 10);
            loadChart();
        });
    });

    // Init
    loadBtmcCurrent();
    loadBtmhCurrent();
    loadPhuquyCurrent();
    loadSjcCurrent();
    updateUnitTabs('btmc');
    loadChart();

    setInterval(loadBtmcCurrent,   3 * 60 * 1000);
    setInterval(loadBtmhCurrent,  10 * 60 * 1000);
    setInterval(loadPhuquyCurrent, 5 * 60 * 1000);
    setInterval(loadSjcCurrent,   10 * 60 * 1000);
});
