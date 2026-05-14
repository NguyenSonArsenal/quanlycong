/* ═══ tool-quydoi page scripts ═══ */
(function() {
  // SSR prices from PHP
  var PRICES = @json($prices);

  var activeBrand = 'phuquy';

  // gram per unit
  var GRAM_PER_UNIT = { LUONG: 37.5, CHI: 3.75, KG: 1000, GRAM: 1 };

  var qtyInput   = document.getElementById('qty-input');
  var unitSelect = document.getElementById('unit-select');
  var buyEl      = document.getElementById('result-buy');
  var sellEl     = document.getElementById('result-sell');
  var buySubEl   = document.getElementById('result-buy-sub');
  var sellSubEl  = document.getElementById('result-sell-sub');
  var noteEl     = document.getElementById('result-note');

  var fmt = function(n) { return Number(Math.round(n)).toLocaleString('vi-VN') + ' đ'; };

  function getBasePrice(brand, unit) {
    var p = PRICES[brand];
    if (!p) return null;
    // Try to get KG unit price first (most reliable)
    if (p['KG'])    return { buy: p['KG'].buy,    sell: p['KG'].sell,    gramsPer: 1000, label: 'KG' };
    if (p['LUONG']) return { buy: p['LUONG'].buy,  sell: p['LUONG'].sell, gramsPer: 37.5, label: 'Lượng' };
    return null;
  }

  function calculate() {
    var qty  = parseFloat(qtyInput.value) || 0;
    var unit = unitSelect.value;
    var grams = qty * GRAM_PER_UNIT[unit];

    var base = getBasePrice(activeBrand);
    if (!base || qty <= 0) {
      buyEl.textContent  = '–';
      sellEl.textContent = '–';
      buySubEl.textContent = ''; sellSubEl.textContent = '';
      noteEl.textContent = 'Đang tải giá...';
      return;
    }

    // price per gram
    var buyPerGram  = base.buy  / base.gramsPer;
    var sellPerGram = base.sell / base.gramsPer;

    var totalBuy  = buyPerGram  * grams;
    var totalSell = sellPerGram * grams;

    buyEl.textContent  = fmt(totalBuy);
    sellEl.textContent = fmt(totalSell);
    buySubEl.textContent  = '~' + Number(buyPerGram.toFixed(0)).toLocaleString('vi-VN') + ' đ/gram';
    sellSubEl.textContent = '~' + Number(sellPerGram.toFixed(0)).toLocaleString('vi-VN') + ' đ/gram';
    noteEl.textContent = grams.toLocaleString('vi-VN') + ' gram · Giá ' + base.label + '/gram từ ' + activeBrand;
  }

  window.selectBrand = function(btn, brand) {
    document.querySelectorAll('.brand-btn').forEach(function(b) {
      b.classList.remove('active');
      b.style.borderColor = '';
      b.style.background  = '';
    });
    btn.classList.add('active');
    activeBrand = brand;
    // Fetch live price for selected brand
    fetchLivePrice(brand);
    calculate();
  };

  function fetchLivePrice(brand) {
    var apiMap = {
      phuquy: '/api/silver/current', ancarat: '/api/ancarat/current',
      doji: '/api/doji/current', kimnganphuc: '/api/kimnganphuc/current'
    };
    var url = apiMap[brand];
    if (!url) return;
    fetch(url).then(function(r){return r.json();}).then(function(json){
      if (!json.success || !json.data) return;
      PRICES[brand] = {};
      Object.keys(json.data).forEach(function(unit) {
        PRICES[brand][unit] = { buy: json.data[unit].buy_price, sell: json.data[unit].sell_price };
      });
      calculate();
    }).catch(function(){});
  }

  qtyInput.addEventListener('input', calculate);
  unitSelect.addEventListener('change', calculate);

  // Init: fetch live prices for phuquy
  fetchLivePrice('phuquy');
  calculate();
})();
