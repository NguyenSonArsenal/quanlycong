/* ═══ tool-sosanh page scripts ═══ */
(function() {
  var API = {
    phuquy:      '/api/silver/current',
    ancarat:     '/api/ancarat/current',
    doji:        '/api/doji/current',
    kimnganphuc: '/api/kimnganphuc/current',
  };

  var fmt = function(n) { return Number(n).toLocaleString('vi-VN'); };

  function refreshAll() {
    Object.keys(API).forEach(function(brand) {
      fetch(API[brand]).then(function(r){return r.json();}).then(function(json){
        if (!json.success || !json.data) return;
        Object.keys(json.data).forEach(function(unit) {
          var d = json.data[unit];
          var buyEl    = document.getElementById('cmp-buy-'    + brand + '-' + unit);
          var sellEl   = document.getElementById('cmp-sell-'   + brand + '-' + unit);
          var spreadEl = document.getElementById('cmp-spread-' + brand + '-' + unit);
          var timeEl   = document.getElementById('cmp-time-'   + brand);
          if (buyEl)    buyEl.textContent    = fmt(d.buy_price);
          if (sellEl)   sellEl.textContent   = fmt(d.sell_price);
          if (spreadEl) spreadEl.textContent = fmt(d.sell_price - d.buy_price);
        });
        if (json.updated_at) {
          var t = document.getElementById('cmp-time-' + brand);
          if (t) t.textContent = json.updated_at;
        }
        // Highlight best buy/sell after all refresh
        highlightBest();
      }).catch(function(){});
    });
  }

  function highlightBest() {
    var activeUnit = document.querySelector('.unit-tab.active').dataset.unit;
    var rows = document.querySelectorAll('.cmp-row[data-unit="' + activeUnit + '"]');
    var sells = [], buys = [];
    rows.forEach(function(row) {
      var brand = row.dataset.brand;
      var s = document.getElementById('cmp-sell-' + brand + '-' + activeUnit);
      var b = document.getElementById('cmp-buy-'  + brand + '-' + activeUnit);
      if (s) sells.push({ el: s, val: parseInt(s.textContent.replace(/\D/g,'')) });
      if (b) buys.push({ el: b, val: parseInt(b.textContent.replace(/\D/g,'')) });
    });
    // Remove old badges
    document.querySelectorAll('.best-badge,.low-badge').forEach(function(b){b.remove()});
    if (sells.length > 1) {
      var minSell = sells.reduce(function(a,b){return a.val < b.val ? a : b});
      var maxSell = sells.reduce(function(a,b){return a.val > b.val ? a : b});
      var badge1 = document.createElement('span'); badge1.className='best-badge'; badge1.textContent='Thấp nhất';
      var badge2 = document.createElement('span'); badge2.className='low-badge';  badge2.textContent='Cao nhất';
      if (minSell.el !== maxSell.el) { minSell.el.appendChild(badge1); maxSell.el.appendChild(badge2); }
    }
  }

  window.filterUnit = function(btn, unit) {
    document.querySelectorAll('.unit-tab').forEach(function(b){b.classList.remove('active')});
    btn.classList.add('active');
    document.querySelectorAll('.cmp-row').forEach(function(row){
      row.style.display = (row.dataset.unit === unit) ? '' : 'none';
    });
    highlightBest();
  };

  refreshAll();
  setInterval(refreshAll, 30 * 60 * 1000);
})();
