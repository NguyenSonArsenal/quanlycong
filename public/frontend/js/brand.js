/* ═══ brand page scripts ═══ */
(function () {
  var cfg = window.BRAND_CONFIG || {};
  var JS_COMPUTED = cfg.jsComputed || {};
  var BRAND   = cfg.brand || '';
  var API_KEY = cfg.apiKey || '';
  var API_URLS = {
    current: '/api/' + API_KEY + '/current',
    history: '/api/' + API_KEY + '/history',
  };

  var activeUnitBtn = document.querySelector('.unit-btn.active');
  var activeUnit  = activeUnitBtn ? activeUnitBtn.dataset.unit : (cfg.defaultUnit || 'KG');
  var activeMult  = activeUnitBtn ? (parseInt(activeUnitBtn.dataset.mult) || 1) : 1;
  var activeDays  = 7;
  var brandChart  = null;

  var UNIT_LABELS = cfg.unitLabels || {};

  var fmt = function(n) { return Number(n).toLocaleString('vi-VN'); };

  /* ── Update SSR table with live data ── */
  function refreshPrices() {
    apiFetch(API_URLS.current)
      .then(function(r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function(json) {
        if (!json.success || !json.data) return;
        Object.keys(json.data).forEach(function(unit) {
          var d = json.data[unit];
          var buyEl    = document.getElementById('ssr-buy-'    + unit);
          var sellEl   = document.getElementById('ssr-sell-'   + unit);
          var spreadEl = document.getElementById('ssr-spread-' + unit);
          if (buyEl)    buyEl.textContent    = fmt(d.buy_price);
          if (sellEl)   sellEl.textContent   = fmt(d.sell_price);
          if (spreadEl) spreadEl.textContent = fmt(d.sell_price - d.buy_price);
        });
        // Handle computed units (e.g. LUONG_5 = LUONG × 5)
        Object.keys(JS_COMPUTED).forEach(function(targetKey) {
          var cfg = JS_COMPUTED[targetKey];
          if (!json.data[cfg.from]) return;
          var d = json.data[cfg.from];
          var buyEl    = document.getElementById('ssr-buy-'  + targetKey);
          var sellEl   = document.getElementById('ssr-sell-' + targetKey);
          var spEl     = document.getElementById('ssr-spread-' + targetKey);
          if (buyEl)  buyEl.textContent  = fmt(d.buy_price  * cfg.mult);
          if (sellEl) sellEl.textContent = fmt(d.sell_price * cfg.mult);
          if (spEl)   spEl.textContent   = fmt((d.sell_price - d.buy_price) * cfg.mult);
        });
        if (json.updated_at) {
          var el = document.getElementById('tbl-updated');
          if (el) el.textContent = 'Cập nhật: ' + json.updated_at;
        }
      })
      .catch(function(){});
  }

  /* ── Load chart ── */
  function loadChart() {
    var loading = document.getElementById('chart-loading');
    var canvas  = document.getElementById('brandChart');
    loading.style.display = 'flex'; canvas.style.display = 'none';

    var url = API_URLS.history + '?days=' + activeDays + '&type=' + activeUnit;
    apiFetch(url)
      .then(function(r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function(json) {
        loading.style.display = 'none';
        if (!json.success || !json.data || json.data.dates.length === 0) {
          loading.innerHTML = '<span style="color:var(--muted);font-size:12px">⚠️ ' + (json.message || 'Chưa có dữ liệu lịch sử') + '</span>';
          loading.style.display = 'flex';
          console.info('[Chart] no data:', BRAND, activeUnit, activeDays);
          return;
        }
        canvas.style.display = 'block';

        var buys  = json.data.buy_prices.map(function(v) { return v * activeMult; });
        var sells = json.data.sell_prices.map(function(v) { return v * activeMult; });
        var unitLabel = UNIT_LABELS[activeUnit] || activeUnit;
        if (activeMult > 1) unitLabel = activeMult + ' ' + unitLabel;

        if (brandChart) brandChart.destroy();

        /* ── Crosshair: đường dọc + ngang mờ khi hover ── */
        var crosshairPlugin = {
          id: 'brandCrosshair',
          afterDraw: function(chart) {
            if (chart._chX == null) return;
            var c = chart.ctx, y = chart.scales.y, x = chart.scales.x;
            c.save();
            c.setLineDash([4, 4]);
            c.lineWidth = 1;
            c.strokeStyle = 'rgba(255,255,255,0.18)';
            c.beginPath(); c.moveTo(chart._chX, y.top);   c.lineTo(chart._chX, y.bottom); c.stroke();
            c.beginPath(); c.moveTo(x.left, chart._chY);  c.lineTo(x.right,  chart._chY);  c.stroke();
            c.restore();
          }
        };

        brandChart = new Chart(canvas, {
          type: 'line',
          data: {
            labels: json.data.dates,
            datasets: [
              { label:'Giá bán ra',  data:sells, borderColor:'#4f7af8', backgroundColor:'rgba(79,122,248,0.07)',  borderWidth:2, pointRadius:json.data.dates.length<=15?3:1.5, pointHoverRadius:5, fill:true, tension:0.35 },
              { label:'Giá mua vào', data:buys,  borderColor:'#22c97a', backgroundColor:'rgba(34,201,122,0.06)',  borderWidth:2, pointRadius:json.data.dates.length<=15?3:1.5, pointHoverRadius:5, fill:true, tension:0.35 }
            ]
          },
          plugins: [crosshairPlugin],
          options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            onHover: function(event, _el, chart) {
              if (event.native) { chart._chX = event.x; chart._chY = event.y; }
            },
            plugins:{
              legend:{ labels:{ color:'#909ab2', font:{size:11,family:'Inter'}, usePointStyle:true, pointStyle:'line', pointStyleWidth:20, boxHeight:2 } },
              tooltip:{ backgroundColor:'rgba(13,16,24,0.97)', borderColor:'rgba(255,255,255,0.1)', borderWidth:1,
                titleColor:'#e4e8f2', bodyColor:'#909ab2', padding:10,
                callbacks:{ label:function(ctx){ return ' '+ctx.dataset.label+': '+Number(ctx.raw).toLocaleString('vi-VN')+' đ'; } }
              }
            },
            scales:{
              x:{ grid:{color:'rgba(255,255,255,0.04)'}, ticks:{color:'#6e778c',font:{size:10},maxTicksLimit:10} },
              y:{ grid:{color:'rgba(255,255,255,0.04)'},
                ticks:{color:'#6e778c',font:{size:10},callback:function(v){ return Number(v).toLocaleString('vi-VN'); }},
                title:{display:true,text:'VND/'+unitLabel,color:'#6e778c',font:{size:10}}
              }
            }
          }
        });

        canvas.onmouseleave = function() {
          if (brandChart) { brandChart._chX = null; brandChart._chY = null; brandChart.draw(); }
        };
      })
      .catch(function(err) {
        loading.style.display = 'flex';
        loading.innerHTML = '<span style="color:var(--muted);font-size:12px">⚠️ Chưa có dữ liệu lịch sử</span>';
        console.warn('[Chart] error:', err);
      });
  }

  /* ── Unit tab clicks ── */
  document.querySelectorAll('.unit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.unit-btn').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      activeUnit = btn.dataset.unit;
      activeMult = parseInt(btn.dataset.mult) || 1;
      loadChart();
    });
  });

  /* ── Period tab clicks ── */
  document.querySelectorAll('.prd-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.prd-btn').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      activeDays = parseInt(btn.dataset.days);
      loadChart();
    });
  });

  /* ── FAQ accordion ── */
  document.querySelectorAll('.faq-q').forEach(function(q) {
    q.addEventListener('click', function() {
      var item = q.closest('.faq-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function(i){ i.classList.remove('open'); });
      if (!isOpen) item.classList.add('open');
    });
  });

  /* ── Init ── */
  refreshPrices();
  loadChart();
  setInterval(refreshPrices, 30 * 60 * 1000);

})();
