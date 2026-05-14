/* ═══ home page scripts ═══ */
/* ══════════════════════════════════════════════════
   SILVER BRANDS: Unified controller
   brands: phuquy | ancarat | doji
══════════════════════════════════════════════════ */
(function () {
  const API = {
    phuquy: { current: '/api/silver/current', history: '/api/silver/history', percent: '/api/silver/percent' },
    ancarat: { current: '/api/ancarat/current', history: '/api/ancarat/history', percent: '/api/ancarat/percent' },
    doji: { current: '/api/doji/current', history: '/api/doji/history', percent: '/api/doji/percent' },
    kimnganphuc: { current: '/api/kimnganphuc/current', history: '/api/kimnganphuc/history', percent: '/api/kimnganphuc/percent' },
  };

  let activeBrand = 'phuquy';
  let activePeriod = 7;
  const brandUnit = { phuquy: 'KG', ancarat: 'KG', doji: 'LUONG', kimnganphuc: 'KG' };
  const brandMult = { phuquy: 1, ancarat: 1, doji: 1, kimnganphuc: 1 };

  const brandUnitOptions = {
    phuquy: [{ unit: 'LUONG', mult: 1, label: 'Lượng' }, { unit: 'KG', mult: 1, label: 'KG' }],
    ancarat: [{ unit: 'LUONG', mult: 1, label: 'Lượng' }, { unit: 'KG', mult: 1, label: 'KG' }],
    doji: [{ unit: 'LUONG', mult: 1, label: '1 Lượng' }, { unit: 'LUONG', mult: 5, label: '5 Lượng' }],
    kimnganphuc: [{ unit: 'LUONG', mult: 1, label: 'Lượng' }, { unit: 'KG', mult: 1, label: 'KG' }],
  };

  function renderChartUnitTabs() {
    var options = brandUnitOptions[activeBrand] || [];
    var curUnit = brandUnit[activeBrand];
    var curMult = brandMult[activeBrand];
    var container = document.getElementById('sv-chart-unit-tabs');
    if (!container) return;
    container.innerHTML = options.map(function (opt) {
      var isActive = (opt.unit === curUnit && opt.mult === curMult) ? ' active' : '';
      return '<button class="sv-chart-unit-btn' + isActive + '" data-unit="' + opt.unit + '" data-mult="' + opt.mult + '">' + opt.label + '</button>';
    }).join('');
    container.querySelectorAll('.sv-chart-unit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        container.querySelectorAll('.sv-chart-unit-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var newUnit = btn.dataset.unit;
        var newMult = parseInt(btn.dataset.mult) || 1;

        // Sync tất cả thương hiệu
        var allBrands = ['phuquy', 'ancarat', 'doji', 'kimnganphuc'];
        allBrands.forEach(function (brand) {
          var options = brandUnitOptions[brand] || [];
          // Tìm option khớp unit (DOJI chỉ có LUONG → nếu click KG thì bỏ qua)
          var matched = options.find(function (o) { return o.unit === newUnit && o.mult === newMult; });
          if (!matched && newUnit === 'KG') {
            // Brand không có KG (vd DOJI) → giữ nguyên LUONG
            matched = options.find(function (o) { return o.unit === 'LUONG'; });
          }
          if (!matched) return;

          brandUnit[brand] = matched.unit;
          brandMult[brand] = matched.mult;

          // Update brand card tabs UI
          document.querySelectorAll('.sv-tab[data-brand="' + brand + '"]').forEach(function (t) { t.classList.remove('active'); });
          var matchTab = document.querySelector('.sv-tab[data-brand="' + brand + '"][data-unit="' + matched.unit + '"][data-mult="' + matched.mult + '"]');
          if (matchTab) matchTab.classList.add('active');

          // Reload price & pct cho brand
          loadBrandPrice(brand);
          loadBrandPct(brand, activePeriod);
        });

        loadSharedChart();
      });
    });
  }

  let sharedChart = null;

  function fmt(n) { return Number(n).toLocaleString('vi-VN'); }

  const ELIDS = {
    phuquy: { buy: 'pq-buy', sell: 'pq-sell', updated: 'pq-updated', pct: 'pq-pct', pctDays: 'pq-pct-days', spread: 'pq-spread' },
    ancarat: { buy: 'ac-buy', sell: 'ac-sell', updated: 'ac-updated', pct: 'ac-pct', pctDays: 'ac-pct-days', spread: 'ac-spread' },
    doji: { buy: 'dj-buy', sell: 'dj-sell', updated: 'dj-updated', pct: 'dj-pct', pctDays: 'dj-pct-days', spread: 'dj-spread' },
    kimnganphuc: { buy: 'knp-buy', sell: 'knp-sell', updated: 'knp-updated', pct: 'knp-pct', pctDays: 'knp-pct-days', spread: 'knp-spread' },
  };

  function loadBrandPrice(brand) {
    var unit = brandUnit[brand];
    var mult = brandMult[brand];
    var ids = ELIDS[brand];
    apiFetch(API[brand].current)
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json.success || !json.data) return;
        var d = json.data[unit];
        if (!d) return;
        var buyVal = d.buy_price * mult;
        var sellVal = d.sell_price * mult;
        document.getElementById(ids.buy).textContent = fmt(buyVal);
        document.getElementById(ids.sell).textContent = fmt(sellVal);
        document.getElementById(ids.updated).textContent = d.recorded_at || '';
        var spreadEl = ids.spread ? document.getElementById(ids.spread) : null;
        if (spreadEl) spreadEl.textContent = fmt(Math.round(sellVal - buyVal));
      })
      .catch(function () { });
  }

  function loadBrandPct(brand, days) {
    var unit = brandUnit[brand];
    var ids = ELIDS[brand];
    apiFetch(API[brand].percent + '?days=' + days + '&type=' + unit)
      .then(function (r) { return r.json(); })
      .then(function (json) {
        var pctEl = document.getElementById(ids.pct);
        var pctDaysEl = document.getElementById(ids.pctDays);
        if (!json.success || json.percent === null) { pctEl.textContent = '–'; pctEl.className = 'sv-card-pct'; return; }
        var sign = json.trend === 'up' ? '▲ +' : (json.trend === 'down' ? '▼ -' : '');
        pctEl.textContent = sign + json.percent + '%';
        pctEl.className = 'sv-card-pct ' + (json.trend === 'up' ? 'up' : 'down');
        if (pctDaysEl) pctDaysEl.textContent = days + ' ngày qua';
      }).catch(function () { });
  }

  function loadSharedChart() {
    var brand = activeBrand;
    var unit = brandUnit[brand];
    var mult = brandMult[brand];
    var days = activePeriod;
    var loading = document.getElementById('sv-chart-loading');
    var canvas = document.getElementById('svSharedChart');
    loading.style.display = 'flex'; canvas.style.display = 'none';

    apiFetch(API[brand].history + '?days=' + days + '&type=' + unit)
      .then(function (r) {
        if (!r.ok) { throw new Error('HTTP ' + r.status); }
        return r.json();
      })
      .then(function (json) {
        loading.style.display = 'none';
        if (!json.success || !json.data || json.data.dates.length === 0) {
          var msg = json.message || 'Chưa có dữ liệu lịch sử cho thương hiệu này';
          loading.innerHTML = '<span style="color:var(--muted);font-size:12px">⚠️ ' + msg + '</span>';
          loading.style.display = 'flex';
          return;
        }
        canvas.style.display = 'block';

        var lbl = json.type_label || unit;
        if (mult > 1) lbl = 'VND/' + mult + ' Lượng';
        document.getElementById('sv-chart-unit-lbl').textContent = lbl;

        var buys = json.data.buy_prices.map(function (v) { return v * mult; });
        var sells = json.data.sell_prices.map(function (v) { return v * mult; });

        if (sharedChart) { sharedChart.destroy(); }

        var crosshairPlugin = {
          id: 'svCrosshair',
          afterDraw: function (chart) {
            if (chart._crosshairX == null) return;
            var ctx2 = chart.ctx;
            var yAxis = chart.scales.y;
            var xAxis = chart.scales.x;
            ctx2.save();
            ctx2.setLineDash([4, 4]);
            ctx2.lineWidth = 1;
            ctx2.strokeStyle = 'rgba(255,255,255,0.18)';
            ctx2.beginPath(); ctx2.moveTo(chart._crosshairX, yAxis.top); ctx2.lineTo(chart._crosshairX, yAxis.bottom); ctx2.stroke();
            ctx2.beginPath(); ctx2.moveTo(xAxis.left, chart._crosshairY); ctx2.lineTo(xAxis.right, chart._crosshairY); ctx2.stroke();
            ctx2.restore();
          }
        };

        sharedChart = new Chart(canvas, {
          type: 'line',
          data: {
            labels: json.data.dates,
            datasets: [
              { label: 'Giá bán ra', data: sells, borderColor: '#4f7af8', backgroundColor: 'rgba(79,122,248,0.06)', borderWidth: 2, pointRadius: json.data.dates.length <= 15 ? 3 : 1.5, pointHoverRadius: 5, fill: true, tension: 0.38 },
              { label: 'Giá mua vào', data: buys, borderColor: '#22c97a', backgroundColor: 'rgba(34,201,122,0.08)', borderWidth: 2, pointRadius: json.data.dates.length <= 15 ? 3 : 1.5, pointHoverRadius: 5, fill: true, tension: 0.38 }
            ]
          },
          plugins: [crosshairPlugin],
          options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            onHover: function (event, _el, chart) {
              if (event.native) { chart._crosshairX = event.x; chart._crosshairY = event.y; }
            },
            plugins: {
              legend: { labels: { color: '#909ab2', font: { size: 11, family: 'Inter' }, usePointStyle: true, pointStyle: 'line', pointStyleWidth: 20, boxHeight: 2 } },
              tooltip: {
                backgroundColor: 'rgba(13,16,24,0.96)', borderColor: 'rgba(176,190,197,0.2)', borderWidth: 1,
                titleColor: '#e4e8f2', bodyColor: '#909ab2', padding: 10,
                callbacks: { label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString('vi-VN') + ' đ'; } }
              }
            },
            scales: {
              x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6e778c', font: { size: 10 }, maxTicksLimit: 10 } },
              y: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: { color: '#6e778c', font: { size: 10 }, callback: function (v) { return Number(v).toLocaleString('vi-VN'); } },
                title: { display: true, text: lbl, color: '#6e778c', font: { size: 10 } }
              }
            }
          }
        });

        canvas.onmouseleave = function () {
          if (sharedChart) { sharedChart._crosshairX = null; sharedChart._crosshairY = null; sharedChart.draw(); }
        };
      })
      .catch(function (err) {
        loading.style.display = 'flex';
        loading.innerHTML = '<span style="color:var(--muted);font-size:12px">⚠️ Chưa có dữ liệu lịch sử cho thương hiệu này</span>';
        console.warn('[SVChart] load error:', err);
      });
  }

  function loadAllPrices() {
    ['phuquy', 'ancarat', 'doji', 'kimnganphuc'].forEach(function (b) {
      loadBrandPrice(b);
      loadBrandPct(b, activePeriod);
    });
  }

  // ── Synced unit tabs: click Lượng/KG → tất cả brand chuyển theo ──
  document.querySelectorAll('.sv-tab').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var clickedUnit = btn.dataset.unit;
      var clickedMult = parseInt(btn.dataset.mult) || 1;
      var clickedBrand = btn.dataset.brand;

      var allBrands = ['phuquy', 'ancarat', 'doji', 'kimnganphuc'];
      allBrands.forEach(function (brand) {
        var options = brandUnitOptions[brand];
        // Tìm option khớp unit (KG hoặc LUONG) trong brand này
        var matchOption = null;
        for (var i = 0; i < options.length; i++) {
          if (options[i].unit === clickedUnit && (brand === clickedBrand ? options[i].mult === clickedMult : true)) {
            matchOption = options[i];
            break;
          }
        }
        // Brand không hỗ trợ unit này (vd: DOJI không có KG) → giữ nguyên
        if (!matchOption) return;

        brandUnit[brand] = matchOption.unit;
        brandMult[brand] = matchOption.mult;

        // Update active tab UI
        document.querySelectorAll('.sv-tab[data-brand="' + brand + '"]').forEach(function (b) { b.classList.remove('active'); });
        var matchTab = document.querySelector('.sv-tab[data-brand="' + brand + '"][data-unit="' + matchOption.unit + '"][data-mult="' + matchOption.mult + '"]');
        if (matchTab) matchTab.classList.add('active');

        loadBrandPrice(brand);
        loadBrandPct(brand, activePeriod);
      });

      renderChartUnitTabs();
      loadSharedChart();
    });
  });

  document.querySelectorAll('.sv-brand-card').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('.sv-brand-card').forEach(function (c) { c.classList.remove('active'); });
      card.classList.add('active');
      activeBrand = card.dataset.brand;
      document.querySelectorAll('.sv-chart-brand').forEach(function (b) { b.classList.remove('active'); });
      var chartTab = document.querySelector('.sv-chart-brand[data-brand="' + activeBrand + '"]');
      if (chartTab) chartTab.classList.add('active');
      renderChartUnitTabs();
      loadSharedChart();
    });
  });

  document.querySelectorAll('.sv-chart-brand').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.sv-chart-brand').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      activeBrand = btn.dataset.brand;
      document.querySelectorAll('.sv-brand-card').forEach(function (c) { c.classList.remove('active'); });
      var card = document.getElementById('sv-card-' + activeBrand);
      if (card) card.classList.add('active');
      renderChartUnitTabs();
      loadSharedChart();
    });
  });

  document.querySelectorAll('.sv-prd').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.sv-prd').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      activePeriod = parseInt(btn.dataset.days);
      ['phuquy', 'ancarat', 'doji', 'kimnganphuc'].forEach(function (b) { loadBrandPct(b, activePeriod); });
      loadSharedChart();
    });
  });

  var firstCard = document.getElementById('sv-card-phuquy');
  if (firstCard) firstCard.classList.add('active');

  renderChartUnitTabs();
  loadAllPrices();
  loadSharedChart();
  loadSilverTrend();
  setInterval(function () { loadAllPrices(); loadSharedChart(); }, 30 * 60 * 1000);

  // ── AI Trend Analysis ──
  function loadSilverTrend() {
    var body = document.getElementById('svTrendBody');
    var time = document.getElementById('svTrendTime');
    var statsRow = document.getElementById('svTrendStats');
    var changeEl = document.getElementById('svTrendChange');
    var highEl = document.getElementById('svTrendHigh');
    var lowEl = document.getElementById('svTrendLow');

    if (!body) return;

    apiFetch('/api/silver/trend')
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json.success || !json.data) {
          body.innerHTML = '<span class="sv-trend-error">⏳ Chưa đủ dữ liệu để phân tích xu hướng.</span>';
          return;
        }

        var d = json.data;

        // Hiển thị nội dung phân tích + highlight từ khóa
        body.classList.add('has-content');
        var text = d.analysis || '';
        // Escape HTML
        var tmp = document.createElement('div');
        tmp.textContent = text;
        var html = tmp.innerHTML;
        // Giữ xuống dòng (hỗ trợ cả \r\n và \n)
        html = html.split('\r\n').join('<br>');
        html = html.split('\n').join('<br>');

        // Highlight từ khóa — dùng split/join thay regex (Safari-safe)
        var hlMap = [
          ['NÊN MUA VÀO', 'hl-buy'], ['CÓ THỂ MUA VÀO', 'hl-buy'], ['NÊN MUA', 'hl-buy'],
          ['CÂN NHẮC BÁN RA', 'hl-sell'], ['NÊN BÁN RA', 'hl-sell'], ['NÊN BÁN', 'hl-sell'],
          ['NÊN CHỜ ĐỢI', 'hl-wait'], ['NÊN CHỜ THÊM', 'hl-wait'], ['NÊN CHỜ', 'hl-wait'],
          ['NÊN GIỮ', 'hl-wait'],
        ];
        hlMap.forEach(function (pair) {
          html = html.split(pair[0]).join('<span class="sv-hl ' + pair[1] + '">' + pair[0] + '</span>');
        });

        // Case-insensitive highlights (thủ công cho Safari)
        function hlReplace(str, keyword, cls) {
          var lower = str.toLowerCase();
          var kLower = keyword.toLowerCase();
          var result = '';
          var i = 0;
          while (i < str.length) {
            var idx = lower.indexOf(kLower, i);
            if (idx === -1) { result += str.substring(i); break; }
            result += str.substring(i, idx);
            var original = str.substring(idx, idx + keyword.length);
            result += '<span class="sv-hl ' + cls + '">' + original + '</span>';
            i = idx + keyword.length;
          }
          return result;
        }

        var ciMap = [
          ['tăng mạnh', 'hl-up'], ['tăng nhẹ', 'hl-up'], ['xu hướng tăng', 'hl-up'],
          ['giảm mạnh', 'hl-down'], ['giảm nhẹ', 'hl-down'], ['giảm sâu', 'hl-down'],
          ['cắm đầu giảm', 'hl-down'], ['xu hướng giảm', 'hl-down'],
          ['đi ngang', 'hl-flat'], ['tích lũy', 'hl-flat'], ['ổn định', 'hl-flat'],
          ['Khuyến nghị:', 'hl-label'],
        ];
        ciMap.forEach(function (pair) {
          html = hlReplace(html, pair[0], pair[1]);
        });

        body.innerHTML = html;

        // Thời gian cập nhật
        if (time && d.updated_at) {
          time.textContent = '🕐 ' + d.updated_at;
        }

        // Stats badges
        if (statsRow && d.stats) {
          var s = d.stats;
          var trendClass = s.trend === 'tăng' ? 'up' : (s.trend === 'giảm' ? 'down' : '');

          if (changeEl) {
            changeEl.textContent = (s.trend === 'tăng' ? '▲ ' : (s.trend === 'giảm' ? '▼ ' : '')) + s.pct_change;
            changeEl.className = 'sv-trend-stat ' + trendClass;
          }
          if (highEl) {
            highEl.textContent = '↑ ' + s.high;
          }
          if (lowEl) {
            lowEl.textContent = '↓ ' + s.low;
          }
          statsRow.style.display = 'flex';
        }
      })
      .catch(function () {
        body.innerHTML = '<span class="sv-trend-error">Không thể tải nhận định xu hướng.</span>';
      });
  }


})();
