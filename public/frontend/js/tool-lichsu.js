/* ═══ tool-lichsu page scripts ═══ */
(function() {
  var activeBrand = 'phuquy';
  var activeApi   = 'silver';
  var activeUnit  = 'KG';
  var activeDays  = 30;
  var histChart   = null;
  var chartData   = null; // cached data for CSV

  var fmt = function(n) { return Number(n).toLocaleString('vi-VN'); };

  function getApiUrl() {
    return '/api/' + activeApi + '/history?days=' + activeDays + '&type=' + activeUnit;
  }

  function loadChart() {
    var loading = document.getElementById('chart-loading');
    var canvas  = document.getElementById('histChart');
    loading.style.display = 'flex';
    canvas.style.display  = 'none';
    chartData = null;

    fetch(getApiUrl())
      .then(function(r){if(!r.ok)throw new Error(r.status);return r.json();})
      .then(function(json) {
        loading.style.display = 'none';
        if (!json.success || !json.data || json.data.dates.length === 0) {
          loading.innerHTML = '<span style="color:var(--muted);font-size:12px">⚠️ Chưa có dữ liệu lịch sử cho kỳ này</span>';
          loading.style.display = 'flex';
          return;
        }
        chartData = json;
        canvas.style.display = 'block';

        var dates  = json.data.dates;
        var buys   = json.data.buy_prices;
        var sells  = json.data.sell_prices;

        // Stats
        var maxSell = Math.max.apply(null, sells);
        var minSell = Math.min.apply(null, sells);
        var maxIdx  = sells.indexOf(maxSell);
        var minIdx  = sells.indexOf(minSell);
        var firstSell = sells[0], lastSell = sells[sells.length - 1];
        var chg = lastSell - firstSell;
        var pct = firstSell > 0 ? (chg / firstSell * 100).toFixed(2) : 0;

        var isIntraday = (activeDays === 1);
        document.getElementById('stat-sell-now').textContent = fmt(lastSell);
        document.getElementById('stat-unit-lbl').textContent = json.type_label || activeUnit;
        document.getElementById('stat-high').textContent = fmt(maxSell);
        document.getElementById('stat-high-date').textContent = isIntraday ? ('lúc ' + (dates[maxIdx] || '')) : (dates[maxIdx] || '');
        document.getElementById('stat-high-label').textContent = isIntraday ? 'Cao nhất hôm nay' : 'Cao nhất kỳ';
        document.getElementById('stat-low').textContent  = fmt(minSell);
        document.getElementById('stat-low-date').textContent  = isIntraday ? ('lúc ' + (dates[minIdx] || '')) : (dates[minIdx] || '');
        document.getElementById('stat-low-label').textContent = isIntraday ? 'Thấp nhất hôm nay' : 'Thấp nhất kỳ';
        document.getElementById('stat-change').textContent = (chg >= 0 ? '+' : '') + fmt(chg);
        document.getElementById('stat-change').className = 'stat-val ' + (chg >= 0 ? 'stat-up' : 'stat-down');
        document.getElementById('stat-change-pct').textContent = (chg >= 0 ? '▲ +' : '▼ ') + pct + '%' + (isIntraday ? ' trong ngày' : ' kỳ ' + activeDays + ' ngày');
        document.getElementById('chart-sub').textContent = isIntraday
          ? ('Hôm nay · ' + dates[0] + ' → ' + dates[dates.length-1])
          : (activeDays + ' ngày · ' + dates[0] + ' → ' + dates[dates.length-1]);

        // Crosshair plugin
        var crosshair = {
          id:'hCrosshair',
          afterDraw:function(c){
            if(c._chX==null)return;
            var ctx=c.ctx,y=c.scales.y,x=c.scales.x;
            ctx.save();ctx.setLineDash([4,4]);ctx.lineWidth=1;
            ctx.strokeStyle='rgba(255,255,255,0.18)';
            ctx.beginPath();ctx.moveTo(c._chX,y.top);ctx.lineTo(c._chX,y.bottom);ctx.stroke();
            ctx.beginPath();ctx.moveTo(x.left,c._chY);ctx.lineTo(x.right,c._chY);ctx.stroke();
            ctx.restore();
          }
        };

        if (histChart) histChart.destroy();
        histChart = new Chart(canvas, {
          type: 'line',
          data: {
            labels: dates,
            datasets: [
              {label:'Giá bán ra',  data:sells, borderColor:'#4f7af8', backgroundColor:'rgba(79,122,248,0.07)', borderWidth:2, pointRadius:dates.length<=20?3:1.5, pointHoverRadius:5, fill:true, tension:0.35},
              {label:'Giá mua vào', data:buys,  borderColor:'#22c97a', backgroundColor:'rgba(34,201,122,0.06)', borderWidth:2, pointRadius:dates.length<=20?3:1.5, pointHoverRadius:5, fill:true, tension:0.35}
            ]
          },
          plugins: [crosshair],
          options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{mode:'index',intersect:false},
            onHover:function(e,_,c){if(e.native){c._chX=e.x;c._chY=e.y;}},
            plugins:{
              legend:{labels:{color:'#909ab2',font:{size:11,family:'Inter'},usePointStyle:true,pointStyle:'line',pointStyleWidth:20,boxHeight:2}},
              tooltip:{backgroundColor:'rgba(13,16,24,0.97)',borderColor:'rgba(255,255,255,0.1)',borderWidth:1,
                titleColor:'#e4e8f2',bodyColor:'#909ab2',padding:10,
                callbacks:{label:function(ctx){return ' '+ctx.dataset.label+': '+Number(ctx.raw).toLocaleString('vi-VN')+' đ';}}}
            },
            scales:{
              x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#6e778c',font:{size:10},maxTicksLimit:12}},
              y:{grid:{color:'rgba(255,255,255,0.04)'},
                ticks:{color:'#6e778c',font:{size:10},callback:function(v){return Number(v).toLocaleString('vi-VN');}},
                title:{display:true,text:json.type_label||activeUnit,color:'#6e778c',font:{size:10}}
              }
            }
          }
        });

        canvas.onmouseleave = function(){if(histChart){histChart._chX=null;histChart._chY=null;histChart.draw();}};

        // Populate data table (last 50 rows, reversed — newest first)
        buildTable(dates, buys, sells);
      })
      .catch(function(err){
        document.getElementById('chart-loading').innerHTML='<span style="color:var(--muted);font-size:12px">⚠️ Lỗi tải dữ liệu</span>';
        document.getElementById('chart-loading').style.display='flex';
        console.warn('HistChart error:', err);
      });
  }

  function buildTable(dates, buys, sells) {
    var tbody = document.getElementById('data-tbody');
    var rows  = [];
    for (var i = 0; i < dates.length; i++) {
      var chg   = (i > 0) ? sells[i] - sells[i-1] : null;
      rows.push({date: dates[i], buy: buys[i], sell: sells[i], chg: chg});
    }
    rows.reverse(); // newest first
    var limit = Math.min(rows.length, 60);
    document.getElementById('data-count').textContent = rows.length + (activeDays === 1 ? ' điểm dữ liệu' : ' ngày dữ liệu');
    var html = '';
    for (var j = 0; j < limit; j++) {
      var r   = rows[j];
      var chgCls = r.chg === null ? '' : (r.chg > 0 ? 'chg-up' : (r.chg < 0 ? 'chg-down' : ''));
      var chgStr = r.chg === null ? '–' : ((r.chg > 0 ? '▲ +' : (r.chg < 0 ? '▼ ' : '')) + fmt(Math.abs(r.chg)));
      html += '<tr><td>'+ r.date +'</td>'
            + '<td class="price price-buy">'+ fmt(r.buy)  +'</td>'
            + '<td class="price price-sell">'+ fmt(r.sell) +'</td>'
            + '<td class="price">'+ fmt(r.sell - r.buy)  +'</td>'
            + '<td class="'+ chgCls +'">'+ chgStr +'</td></tr>';
    }
    tbody.innerHTML = html;
  }


  // Brand buttons
  document.querySelectorAll('.brand-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.brand-btn').forEach(function(b){b.classList.remove('active')});
      btn.classList.add('active');
      activeBrand = btn.dataset.brand;
      activeApi   = btn.dataset.api;
      // Reset unit to KG for brands that have it, LUONG for DOJI
      if (activeBrand === 'doji') {
        activeUnit = 'LUONG';
        document.querySelectorAll('.ctrl-btn[data-unit]').forEach(function(b){
          b.classList.toggle('active', b.dataset.unit === 'LUONG');
        });
      }
      document.getElementById('chart-title').textContent = 'Lịch sử giá bán ra — ' + btn.textContent.trim();
      loadChart();
    });
  });

  // Unit buttons
  document.querySelectorAll('.ctrl-btn[data-unit]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.ctrl-btn[data-unit]').forEach(function(b){b.classList.remove('active')});
      btn.classList.add('active');
      activeUnit = btn.dataset.unit;
      loadChart();
    });
  });

  // Period buttons
  document.querySelectorAll('.ctrl-btn[data-days]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.ctrl-btn[data-days]').forEach(function(b){b.classList.remove('active')});
      btn.classList.add('active');
      activeDays = parseInt(btn.dataset.days);
      loadChart();
    });
  });

  // Init
  loadChart();
})();
