/* ==========================================================
   GiáVàng.vn — index.js
   jQuery + Chart.js · Mock Data Demo
========================================================== */
$(function () {

  /* ============================================================
     MOCK DATA
  ============================================================ */
  const VND_RATE = 25450; // USD -> VND

  const brands = [
    { id: 'sjc', name: 'SJC', site: 'sjc.com.vn', icon: '🏅', bg: 'linear-gradient(135deg,#b8860b,#6b4c08)' },
    { id: 'doji', name: 'DOJI', site: 'doji.vn', icon: '💎', bg: 'linear-gradient(135deg,#6b21a8,#3b0f6d)' },
    { id: 'pnj', name: 'PNJ', site: 'pnj.com.vn', icon: '💍', bg: 'linear-gradient(135deg,#9d174d,#5c0f2d)' },
    { id: 'phuquy', name: 'Phú Quý', site: 'phuquy.com.vn', icon: '⭐', bg: 'linear-gradient(135deg,#0e7490,#064e63)' },
    { id: 'btmc', name: 'Bảo Tín Minh Châu', site: 'baotinminhchau.com', icon: '🌟', bg: 'linear-gradient(135deg,#15803d,#064e25)' },
  ];

  // Gold prices per brand (nghìn/chỉ)
  const goldData = {
    sjc: [
      { type: 'Vàng miếng SJC 1L, 10L, 1KG', unit: 'nghìn/chỉ', buy: 89500, sell: 91800, change: +200 },
      { type: 'Vàng nhẫn SJC 99.99 (1-10 chỉ)', unit: 'nghìn/chỉ', buy: 87600, sell: 89400, change: +150 },
      { type: 'Vàng trang sức 18K SJC', unit: 'nghìn/chỉ', buy: 64200, sell: 68500, change: +100 },
    ],
    doji: [
      { type: 'Vàng miếng SJC (DOJI)', unit: 'nghìn/chỉ', buy: 89450, sell: 91750, change: +180 },
      { type: 'Vàng nhẫn 24K DOJI', unit: 'nghìn/chỉ', buy: 87800, sell: 89600, change: +200 },
      { type: 'Vàng trang sức 18K DOJI', unit: 'nghìn/chỉ', buy: 63800, sell: 68200, change: +80 },
    ],
    pnj: [
      { type: 'Vàng miếng SJC (PNJ)', unit: 'nghìn/chỉ', buy: 89400, sell: 91700, change: +160 },
      { type: 'Vàng nhẫn PNJ 9999', unit: 'nghìn/chỉ', buy: 87200, sell: 89000, change: +120 },
      { type: 'Vàng trang sức 18K PNJ', unit: 'nghìn/chỉ', buy: 63500, sell: 67800, change: +60 },
    ],
    phuquy: [
      { type: 'Vàng miếng SJC (Phú Quý)', unit: 'nghìn/chỉ', buy: 89350, sell: 91650, change: +170 },
      { type: 'Vàng nhẫn Phú Quý 9999', unit: 'nghìn/chỉ', buy: 87300, sell: 89100, change: +130 },
      { type: 'Vàng trang sức 18K Phú Quý', unit: 'nghìn/chỉ', buy: 63200, sell: 67500, change: +80 },
    ],
    btmc: [
      { type: 'Vàng 9999 BTMC', unit: 'nghìn/chỉ', buy: 87500, sell: 89300, change: +130 },
      { type: 'Vàng miếng SJC (BTMC)', unit: 'nghìn/chỉ', buy: 89300, sell: 91600, change: +100 },
      { type: 'Vàng trang sức 18K BTMC', unit: 'nghìn/chỉ', buy: 63000, sell: 67600, change: +70 },
    ],
  };

  const silverData = [
    {
      brand: 'SJC', site: 'sjc.com.vn', icon: '⚪', bg: 'linear-gradient(135deg,#546e7a,#263238)',
      items: [
        { type: 'Bạc miếng SJC', unit: 'nghìn/chỉ', buy: 950, sell: 1050, change: +10 },
        { type: 'Bạc nguyên liệu 999', unit: 'nghìn/chỉ', buy: 880, sell: 980, change: +5 },
      ]
    },
    {
      brand: 'DOJI', site: 'doji.vn', icon: '⚪', bg: 'linear-gradient(135deg,#6b21a8,#3b0f6d)',
      items: [
        { type: 'Bạc 99.9 DOJI', unit: 'nghìn/chỉ', buy: 945, sell: 1045, change: +8 },
        { type: 'Bạc 925 trang sức', unit: 'nghìn/chỉ', buy: 820, sell: 920, change: +5 },
      ]
    },
    {
      brand: 'PNJ', site: 'pnj.com.vn', icon: '⚪', bg: 'linear-gradient(135deg,#9d174d,#5c0f2d)',
      items: [
        { type: 'Bạc nguyên liệu 99.9', unit: 'nghìn/chỉ', buy: 940, sell: 1040, change: +6 },
        { type: 'Bạc trang sức 925 PNJ', unit: 'nghìn/chỉ', buy: 810, sell: 910, change: 0 },
      ]
    },
  ];

  // Comparison table rows (loại vàng × brands)
  const compareRows = [
    {
      label: 'Vàng miếng SJC', unit: 'nghìn/chỉ',
      prices: { sjc: [89500, 91800], doji: [89450, 91750], pnj: [89400, 91700], phuquy: [89350, 91650], btmc: [89300, 91600] }
    },
    {
      label: 'Vàng nhẫn 9999', unit: 'nghìn/chỉ',
      prices: { sjc: [87600, 89400], doji: [87800, 89600], pnj: [87200, 89000], phuquy: [87300, 89100], btmc: [87500, 89300] }
    },
    {
      label: 'Vàng trang sức 18K', unit: 'nghìn/chỉ',
      prices: { sjc: [64200, 68500], doji: [63800, 68200], pnj: [63500, 67800], phuquy: [63200, 67500], btmc: [63000, 67600] }
    },
  ];

  // World spot (USD/oz)
  const worldSpot = {
    gold: { price: 2940.5, change: -4.80, pct: -0.16 },
    silver: { price: 33.48, change: +0.24, pct: +0.72 },
    platinum: { price: 1010.20, change: +3.10, pct: +0.31 },
    palladium: { price: 954.00, change: -8.20, pct: -0.85 },
  };

  // Analysis text
  const analysisText = {
    tags: ['Vàng SJC', 'Bạc 999', 'Thế giới'],
    author: 'Ban Phân Tích GiáVàng.vn',
    body: `
      <p>Hôm nay, <strong>giá vàng SJC</strong> tiếp tục tăng nhẹ, hiện ở mức <strong>91.800 nghìn/chỉ</strong> chiều bán ra — cao hơn 200 nghìn so với phiên trước. Nguyên nhân chủ yếu do đồng USD suy yếu trên thị trường quốc tế, tạo áp lực tăng cho vàng spot.</p>
      <hr class="ac-divider"/>
      <p><strong>Vàng thế giới (XAU/USD)</strong> đang giao dịch quanh mốc <strong>$2.940/oz</strong>, giảm nhẹ $4.80 so với đóng cửa phiên Mỹ. Dù vậy, xu hướng dài hạn vẫn tích cực khi Fed duy trì tín hiệu cắt giảm lãi suất trong nửa cuối 2026.</p>
      <hr class="ac-divider"/>
      <p><strong>Chênh lệch nội địa – thế giới</strong> duy trì ở mức ~18–19%, phản ánh thuế và phí nhập khẩu. Nhà đầu tư ngắn hạn nên lưu ý chênh lệch mua–bán (spread) của từng thương hiệu trước khi giao dịch để tối ưu chi phí.</p>
      <div class="ac-footer"><span class="ac-footer-icon">💡</span> Gợi ý: Spread thấp nhất hôm nay là <strong style="color:var(--green)">Phú Quý</strong> — tham khảo mục Xếp Hạng Spread bên dưới.</div>
    `
  };

  /* ============================================================
     HELPERS
  ============================================================ */
  const fmt = n => n.toLocaleString('vi-VN');
  const fmtUSD = n => '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const fmtVND = usd => fmt(Math.round(usd * VND_RATE / 1000) * 1000);
  const spread = (buy, sell) => (((sell - buy) / buy) * 100).toFixed(2);

  function chgHtml(val, unit = '') {
    if (val > 0) return `<span class="ch-up">▲ +${fmt(val)}${unit}</span>`;
    if (val < 0) return `<span class="ch-dn">▼ ${fmt(val)}${unit}</span>`;
    return `<span class="ch-nc">– 0</span>`;
  }

  function getNow() {
    const d = new Date();
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  /* ============================================================
     CLOCK
  ============================================================ */
  function updateClock() {
    const d = new Date();
    const pad = n => String(n).padStart(2, '0');
    $('#live-clock').text(`${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`);
  }
  setInterval(updateClock, 1000);
  updateClock();

  function updateTimestamps() {
    const d = new Date();
    const str = d.toLocaleString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    $('#last-updated').text('Cập nhật: ' + str);
    $('#sidebar-updated-time').text(getNow());
  }

  /* ============================================================
     TOAST
  ============================================================ */
  let _toastTimer;
  function showToast(msg, icon = '✅') {
    clearTimeout(_toastTimer);
    $('#toast-msg').text(msg);
    $('#toast .t-icon').text(icon);
    $('#toast').addClass('show');
    _toastTimer = setTimeout(() => $('#toast').removeClass('show'), 3200);
  }

  /* ============================================================
     TICKER
  ============================================================ */
  function buildTicker() {
    const items = [
      ...Object.keys(goldData).map(id => {
        const b = brands.find(x => x.id === id);
        const item = goldData[id][0];
        return { name: `${b.icon} ${b.name} Vàng SJC`, price: item.sell, chg: item.change };
      }),
      ...silverData.map(s => ({ name: `⚪ ${s.brand} Bạc`, price: s.items[0].sell, chg: s.items[0].change })),
      { name: '🌐 Vàng TG', price: worldSpot.gold.price, chg: worldSpot.gold.change, isUsd: true },
    ];

    const html = items.map(it => {
      const priceStr = it.isUsd ? fmtUSD(it.price) : fmt(it.price);
      const chg = it.chg > 0 ? `<span class="ch-up">▲+${it.isUsd ? it.chg.toFixed(2) : fmt(it.chg)}</span>`
        : it.chg < 0 ? `<span class="ch-dn">▼${it.isUsd ? it.chg.toFixed(2) : fmt(it.chg)}</span>`
          : `<span class="ch-nc">–</span>`;
      return `<span class="ticker-item"><span class="ticker-name">${it.name}</span><span class="ticker-price">${priceStr}</span>${chg}</span>`;
    }).join('');
    $('#ticker-inner').html(html + html); // duplicate for seamless loop
  }

  /* ============================================================
     BRAND TABS & PANELS
  ============================================================ */
  // Compare table (all brands overview)
  function renderCompareTable() {
    const brandIds = ['sjc', 'doji', 'pnj', 'phuquy', 'btmc'];
    let html = '';
    compareRows.forEach(row => {
      // Find best buy (highest = buy from brand) and best sell (lowest = cheapest to buy)
      let bestBuyVal = -Infinity, bestSellVal = Infinity;
      brandIds.forEach(id => {
        const [buy, sell] = row.prices[id];
        if (buy > bestBuyVal) bestBuyVal = buy;
        if (sell < bestSellVal) bestSellVal = sell;
      });

      html += `<tr>
        <td>
          <div class="ct-row-label">${row.label}</div>
          <div class="ct-unit">${row.unit}</div>
        </td>`;
      brandIds.forEach(id => {
        const [buy, sell] = row.prices[id];
        const isBestBuy = buy === bestBuyVal;
        const isBestSell = sell === bestSellVal;
        html += `
          <td class="${isBestBuy ? 'ct-best-buy' : ''}">
            <div class="ct-buy">${fmt(buy)}</div>
            ${isBestBuy ? '<div class="ct-best-tag">TỐT NHẤT</div>' : ''}
          </td>
          <td class="${isBestSell ? 'ct-best-sell' : ''}">
            <div class="ct-sell">${fmt(sell)}</div>
            ${isBestSell ? '<div class="ct-best-tag" style="color:var(--gold2);background:rgba(245,197,24,0.12)">RẺ NHẤT</div>' : ''}
          </td>`;
      });
      html += '</tr>';
    });
    $('#compare-table-body').html(html);
  }

  // Individual brand card
  function renderBrandPanel(brandId) {
    const b = brands.find(x => x.id === brandId);
    const data = goldData[brandId];
    const now = getNow();

    const rows = data.map(it => {
      const sp = spread(it.buy, it.sell);
      return `<tr>
        <td>
          <div class="bp-name">${it.type}</div>
          <div class="bp-unit">${it.unit}</div>
        </td>
        <td><span class="bp-buy">${fmt(it.buy)}</span></td>
        <td><span class="bp-sell">${fmt(it.sell)}</span></td>
        <td><span class="bp-spread">${sp}%</span></td>
        <td class="bp-change">${chgHtml(it.change)}</td>
      </tr>`;
    }).join('');

    const html = `
      <div class="brand-detail-card">
        <div class="bdc-head">
          <div class="bdc-logo" style="background:${b.bg}">${b.icon}</div>
          <div class="bdc-info">
            <h3>${b.name}</h3>
            <div class="bdc-site">🔗 ${b.site}</div>
          </div>
          <div class="bdc-time">
            <div class="bdc-time-label">Cập nhật lúc</div>
            <div class="bdc-time-val">${now}</div>
          </div>
        </div>
        <table class="brand-price-table">
          <thead>
            <tr>
              <th style="width:40%">Loại</th>
              <th>Mua vào</th>
              <th>Bán ra</th>
              <th>Spread</th>
              <th>+/–</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
    $(`#price-panel-${brandId}`).html(html);
  }

  // Silver panel
  function renderSilverPanel() {
    let html = '<div class="brand-grid-2col">';
    silverData.forEach(s => {
      const now = getNow();
      const rows = s.items.map(it => {
        const sp = spread(it.buy, it.sell);
        return `<tr>
          <td>
            <div class="bp-name">${it.type}</div>
            <div class="bp-unit">${it.unit}</div>
          </td>
          <td><span class="bp-buy" style="color:var(--silver)">${fmt(it.buy)}</span></td>
          <td><span class="bp-sell" style="color:var(--silver2)">${fmt(it.sell)}</span></td>
          <td><span class="bp-spread">${sp}%</span></td>
          <td class="bp-change">${chgHtml(it.change)}</td>
        </tr>`;
      }).join('');

      html += `
        <div class="brand-detail-card" style="margin-bottom:14px">
          <div class="bdc-head">
            <div class="bdc-logo" style="background:${s.bg}">${s.icon}</div>
            <div class="bdc-info">
              <h3>${s.brand}</h3>
              <div class="bdc-site">🔗 ${s.site}</div>
            </div>
            <div class="bdc-time">
              <div class="bdc-time-label">Cập nhật lúc</div>
              <div class="bdc-time-val">${now}</div>
            </div>
          </div>
          <table class="brand-price-table">
            <thead><tr>
              <th style="width:40%">Loại</th>
              <th>Mua vào</th><th>Bán ra</th><th>Spread</th><th>+/–</th>
            </tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>`;
    });
    html += '</div>';
    $('#price-panel-bac').html(html);
  }

  // Handle tab switching
  $('.brand-tab').on('click', function () {
    const brand = $(this).data('brand');
    $('.brand-tab').removeClass('active').attr('aria-selected', 'false');
    $(this).addClass('active').attr('aria-selected', 'true');
    $('.price-panel').hide();
    $(`#price-panel-${brand}`).show();
  });

  // Sidebar brand quick links
  $('.bql').on('click', function (e) {
    e.preventDefault();
    const brand = $(this).data('brand');
    $(`.brand-tab[data-brand="${brand}"]`).trigger('click');
    $('html,body').animate({ scrollTop: $('#prices').offset().top - 100 }, 400);
    if (window.innerWidth <= 900) closeSidebar();
  });

  /* ============================================================
     COMPARE WORLD
  ============================================================ */
  function renderCompareWorld() {
    // Mock prices (89,500 etc.) = nghìn/lượng (89.5 triệu/lượng)
    // 1 lượng = 37.5g = 37.5/31.1035 oz ≈ 1.2057 oz/lượng
    const OZ_PER_LUONG = 37.5 / 31.1035; // ≈ 1.2057
    const worldGoldPerLuong = worldSpot.gold.price * VND_RATE * OZ_PER_LUONG / 1000; // nghìn/lượng
    const sjcSell = goldData.sjc[0].sell; // nghìn/lượng
    const premiumGold = (((sjcSell - worldGoldPerLuong) / worldGoldPerLuong) * 100).toFixed(1);

    // Silver: stored in nghìn/chỉ (smaller amounts)
    const OZ_PER_CHI = 31.1035 / 3.75; // ≈ 8.2943 chỉ/oz
    const worldSilverVND = worldSpot.silver.price * VND_RATE / OZ_PER_CHI / 1000; // nghìn/chỉ
    const sjcSilverSell = silverData[0].items[0].sell;
    const premiumSilver = (((sjcSilverSell - worldSilverVND) / worldSilverVND) * 100).toFixed(1);

    const cards = [
      {
        label: '🥇 Vàng SJC vs Vàng Thế Giới',
        rows: [
          { name: 'Giá TG quy đổi', val: fmt(Math.round(worldGoldPerLuong)) + ' nghìn/lượng', cls: '' },
          { name: 'Giá nội địa SJC (bán)', val: fmt(sjcSell) + ' nghìn/lượng', cls: '' },
          { name: 'Chênh lệch tuyệt đối', val: fmt(Math.round(sjcSell - worldGoldPerLuong)) + ' nghìn', cls: '' },
        ],
        premium: { val: premiumGold, cls: +premiumGold > 0 ? 'high' : 'low' },
      },
      {
        label: '🌐 Vàng Thế Giới (XAU/USD)',
        rows: [
          { name: 'Giá spot', val: fmtUSD(worldSpot.gold.price) + '/oz', cls: '' },
          { name: 'Quy đổi VNĐ', val: fmtVND(worldSpot.gold.price) + ' ₫/oz', cls: '' },
          { name: 'Thay đổi', valHtml: chgHtml(worldSpot.gold.change, ' USD'), cls: '' },
        ],
        premium: null,
      },
      {
        label: '⚪ Bạc vs Bạc Thế Giới',
        rows: [
          { name: 'Bạc thế giới (quy đổi)', val: fmt(Math.round(worldSilverVND)) + ' nghìn/chỉ', cls: '' },
          { name: 'Bạc SJC (bán)', val: fmt(sjcSilverSell) + ' nghìn/chỉ', cls: '' },
          { name: 'Chênh lệch', val: fmt(Math.round(sjcSilverSell - worldSilverVND)) + ' nghìn', cls: '' },
        ],
        premium: { val: premiumSilver, cls: +premiumSilver > 0 ? 'high' : 'low' },
      },
      {
        label: '⬜ Bạch Kim & Paladi',
        rows: [
          { name: 'Bạch kim (XPT)', val: fmtUSD(worldSpot.platinum.price) + '/oz', cls: '' },
          { name: 'Paladi (XPD)', val: fmtUSD(worldSpot.palladium.price) + '/oz', cls: '' },
          { name: 'Bạc (XAG)', val: fmtUSD(worldSpot.silver.price) + '/oz', cls: '' },
        ],
        premium: null,
      },
    ];

    let html = '';
    cards.forEach(c => {
      const rowsHtml = c.rows.map(r => `
        <div class="cwg-row">
          <span class="cwg-name">${r.name}</span>
          ${r.valHtml ? `<span class="cwg-val">${r.valHtml}</span>` : `<span class="cwg-val">${r.val}</span>`}
        </div>`).join('');
      const premiumHtml = c.premium
        ? `<div class="cwg-row">
             <span class="cwg-name">Premium nội địa</span>
             <span class="cwg-premium ${c.premium.cls}">${c.premium.cls === 'high' ? '▲' : '▼'} ${Math.abs(c.premium.val)}%</span>
           </div>`
        : '';
      html += `<div class="cwg-card"><div class="cwg-label">${c.label}</div>${rowsHtml}${premiumHtml}</div>`;
    });
    $('#compare-world-grid').html(html);
  }

  /* ============================================================
     SPREAD RANKING
  ============================================================ */
  function renderSpread() {
    // Calculate spread for Vàng miếng SJC for each brand
    const spreadList = Object.keys(goldData).map(id => {
      const b = brands.find(x => x.id === id);
      const item = goldData[id][0]; // Vàng miếng SJC
      const sp = parseFloat(spread(item.buy, item.sell));
      return { ...b, buy: item.buy, sell: item.sell, spreadPct: sp };
    });

    // Sort ascending (lowest spread = best)
    spreadList.sort((a, b) => a.spreadPct - b.spreadPct);

    const maxSpread = Math.max(...spreadList.map(s => s.spreadPct));

    let html = '';
    spreadList.forEach((s, i) => {
      const rank = i + 1;
      let rankBadge = rank <= 3 ? `<div class="sc-rank-badge">${rank === 1 ? '🏆 TỐT NHẤT' : rank === 2 ? '🥈 Thứ 2' : '🥉 Thứ 3'}</div>` : '';
      const rankClass = rank <= 3 ? `rank-${rank}` : '';
      const barPct = Math.min((s.spreadPct / maxSpread) * 100, 100);
      const quality = s.spreadPct < 2.0 ? 'good' : s.spreadPct < 2.8 ? 'medium' : 'bad';

      html += `
        <div class="spread-card ${rankClass}" role="listitem">
          ${rankBadge}
          <div class="sc-top">
            <div class="sc-logo" style="background:${s.bg}">${s.icon}</div>
            <div>
              <div class="sc-name">${s.name}</div>
              <div class="sc-type">Vàng miếng SJC</div>
            </div>
          </div>
          <div class="sc-spread-val ${quality}">${s.spreadPct.toFixed(2)}%</div>
          <div class="sc-bar-track">
            <div class="sc-bar-fill ${quality}" style="width:${barPct}%"></div>
          </div>
          <div class="sc-prices">
            <div class="sc-price-group">
              <div class="sc-pl">Mua vào</div>
              <div class="sc-pv" style="color:var(--gold)">${fmt(s.buy)}</div>
            </div>
            <div class="sc-price-group">
              <div class="sc-pl">Bán ra</div>
              <div class="sc-pv" style="color:var(--gold2)">${fmt(s.sell)}</div>
            </div>
            <div class="sc-price-group">
              <div class="sc-pl">Chênh lệch</div>
              <div class="sc-pv" style="color:var(--muted2)">${fmt(s.sell - s.buy)}</div>
            </div>
          </div>
        </div>`;
    });
    $('#spread-grid').html(html);
  }

  /* ============================================================
     HISTORY CHART
  ============================================================ */
  let priceChart = null;
  let currentAsset = 'sjc';
  let currentDays = 7;

  function generateHistory(basePrice, days, volatility) {
    const labels = [];
    const data = [];
    const today = new Date();
    for (let i = days - 1; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(d.getDate() - i);
      labels.push(d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));
      const noise = (Math.random() - 0.48) * volatility;
      const prev = data.length > 0 ? data[data.length - 1] : basePrice;
      data.push(Math.round((prev + noise) * 10) / 10);
    }
    return { labels, data };
  }

  const assetConfig = {
    sjc: { base: 91800, vol: 200, color: '#f5c518', label: 'Vàng SJC (nghìn/chỉ)' },
    world: { base: 2940, vol: 15, color: '#4f7af8', label: 'Vàng TG (USD/oz)' },
    silver: { base: 1050, vol: 20, color: '#b0bec5', label: 'Bạc 999 (nghìn/chỉ)' },
  };

  function renderChart() {
    const cfg = assetConfig[currentAsset];
    const { labels, data } = generateHistory(cfg.base, currentDays, cfg.vol);

    const ctx = document.getElementById('priceChart').getContext('2d');

    if (priceChart) { priceChart.destroy(); }

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, cfg.color + '30');
    gradient.addColorStop(1, cfg.color + '00');

    priceChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: cfg.label,
          data,
          borderColor: cfg.color,
          backgroundColor: gradient,
          borderWidth: 2.5,
          tension: 0.45,
          pointRadius: currentDays <= 7 ? 4 : currentDays <= 30 ? 2 : 0,
          pointHoverRadius: 6,
          pointBackgroundColor: cfg.color,
          pointBorderColor: '#07090f',
          pointBorderWidth: 2,
          fill: true,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#131724',
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            titleColor: '#909ab2',
            bodyColor: cfg.color,
            bodyFont: { family: 'JetBrains Mono', weight: '700', size: 14 },
            padding: 12,
            callbacks: {
              label: ctx => `  ${ctx.dataset.label}: ${ctx.raw.toLocaleString('vi-VN')}`,
            }
          }
        },
        scales: {
          x: {
            grid: { color: 'rgba(255,255,255,0.04)', drawTicks: false },
            ticks: { color: '#6e778c', font: { size: 11 }, maxTicksLimit: currentDays <= 7 ? 7 : 10 },
            border: { color: 'rgba(255,255,255,0.06)' },
          },
          y: {
            grid: { color: 'rgba(255,255,255,0.04)', drawTicks: false },
            ticks: { color: '#6e778c', font: { size: 11, family: 'JetBrains Mono' }, callback: v => v.toLocaleString('vi-VN') },
            border: { color: 'rgba(255,255,255,0.06)', dash: [4, 4] },
          }
        }
      }
    });

    // Legend
    $('#chart-legend').html(`
      <div class="cl-item">
        <div class="cl-dot" style="background:${cfg.color}"></div>
        <span>${cfg.label} · ${currentDays} ngày</span>
      </div>`);
  }

  $('.ct-btn').on('click', function () {
    currentAsset = $(this).data('asset');
    $('.ct-btn').removeClass('active');
    $(this).addClass('active');
    renderChart();
  });
  $('.cp-btn').on('click', function () {
    currentDays = parseInt($(this).data('days'));
    $('.cp-btn').removeClass('active');
    $(this).addClass('active');
    renderChart();
  });

  /* ============================================================
     ANALYSIS
  ============================================================ */
  function renderAnalysis() {
    const d = new Date();
    const dateStr = d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
    $('#analysis-date').text(dateStr);

    const tagHtml = analysisText.tags.map(t => {
      const cls = t.includes('Vàng SJC') ? 'gold' : t.includes('Bạc') ? 'silver' : 'world';
      return `<span class="ac-tag ${cls}">${t}</span>`;
    }).join('');

    const now = getNow();
    $('#analysis-card').html(`
      <div class="ac-meta">
        <div class="ac-avatar">📊</div>
        <div>
          <div class="ac-author">${analysisText.author}</div>
          <div class="ac-time">Hôm nay lúc ${now}</div>
        </div>
      </div>
      <div class="ac-tags">${tagHtml}</div>
      <div class="ac-body">${analysisText.body}</div>
    `);
  }

  /* ============================================================
     PROFIT CALCULATOR
  ============================================================ */
  function calcResult() {
    const amount = parseFloat($('#calc-amount').val()) || 0;
    const buyPrice = parseFloat($('#calc-buy-price').val()) || 0;
    const sellPrice = parseFloat($('#calc-sell-price').val()) || 0;
    const feeRate = parseFloat($('#calc-fee').val()) || 0;

    if (!amount || !buyPrice || !sellPrice) {
      $('#calc-result').html(`<div class="cr-placeholder"><div class="cr-icon">⚠️</div><div>Vui lòng nhập đầy đủ thông tin</div></div>`);
      return;
    }

    const chi = amount / (buyPrice * 1000);   // số chỉ mua được
    const grossRev = chi * sellPrice * 1000;        // doanh thu thô
    const feeAmt = grossRev * (feeRate / 100);    // phí
    const netRev = grossRev - feeAmt;
    const profitLoss = netRev - amount;
    const profitPct = (profitLoss / amount * 100).toFixed(2);
    const isProfit = profitLoss >= 0;

    const fmtM = v => {
      if (Math.abs(v) >= 1e9) return (v / 1e9).toFixed(3) + ' tỷ';
      if (Math.abs(v) >= 1e6) return (v / 1e6).toFixed(2) + ' triệu';
      return fmt(Math.round(v));
    };

    $('#calc-result').html(`
      <div class="cr-content">
        <div class="cr-headline">Kết quả tính toán</div>
        <div class="cr-stat">
          <span class="cr-stat-label">Số chỉ mua được</span>
          <span class="cr-stat-val" style="color:var(--gold2)">${chi.toFixed(4)} chỉ</span>
        </div>
        <div class="cr-stat">
          <span class="cr-stat-label">Vốn đầu tư</span>
          <span class="cr-stat-val">${fmtM(amount)} ₫</span>
        </div>
        <div class="cr-stat">
          <span class="cr-stat-label">Doanh thu thô</span>
          <span class="cr-stat-val">${fmtM(grossRev)} ₫</span>
        </div>
        ${feeRate > 0 ? `<div class="cr-stat">
          <span class="cr-stat-label">Phí giao dịch (${feeRate}%)</span>
          <span class="cr-stat-val" style="color:var(--red)">– ${fmtM(feeAmt)} ₫</span>
        </div>` : ''}
        <div class="cr-result-banner ${isProfit ? 'profit' : 'loss'}">
          <div class="cr-verdict ${isProfit ? 'profit' : 'loss'}">${isProfit ? '+' : ''}${fmtM(profitLoss)} ₫</div>
          <div class="cr-verdict-label">${isProfit ? '🟢 Lãi' : '🔴 Lỗ'} ${Math.abs(profitPct)}% so với vốn</div>
        </div>
      </div>`);
  }

  $('#calc-btn').on('click', calcResult);
  // Real-time calc on input
  $('#calc-amount, #calc-buy-price, #calc-sell-price, #calc-fee').on('input', function () {
    clearTimeout(window._calcTimer);
    window._calcTimer = setTimeout(calcResult, 400);
  });

  /* ============================================================
     PRICE ALERTS
  ============================================================ */
  const ALERT_KEY = 'giavang_alerts';

  function getAlerts() {
    try { return JSON.parse(localStorage.getItem(ALERT_KEY)) || []; }
    catch { return []; }
  }
  function saveAlerts(arr) {
    localStorage.setItem(ALERT_KEY, JSON.stringify(arr));
  }

  const assetLabels = {
    vang_sjc: '🥇 Vàng miếng SJC',
    vang_nhan: '💛 Vàng nhẫn 9999',
    bac: '⚪ Bạc 999',
    world: '🌐 Vàng TG (USD/oz)',
  };

  function renderAlertList() {
    const alerts = getAlerts();
    const $list = $('#alert-list');

    if (!alerts.length) {
      $list.html('<div class="al-empty">Chưa có cảnh báo nào. Đặt mốc ở bên trái!</div>');
      return;
    }

    // Check triggers (compare against current prices)
    const currentPrices = {
      vang_sjc: goldData.sjc[0].sell,
      vang_nhan: goldData.sjc[1].sell,
      bac: silverData[0].items[0].sell,
      world: worldSpot.gold.price,
    };

    const html = alerts.map(a => {
      const cur = currentPrices[a.asset];
      const triggered = a.type === 'above' ? cur >= a.price : cur <= a.price;
      return `
        <div class="al-item${triggered ? ' al-triggered' : ''}" data-id="${a.id}" role="listitem">
          <div class="al-item-icon">${a.type === 'above' ? '📈' : '📉'}</div>
          <div class="al-item-info">
            <div class="al-item-title">${assetLabels[a.asset]}</div>
            <div class="al-item-sub">${a.type === 'above' ? 'Vượt lên trên' : 'Giảm xuống dưới'} <strong>${fmt(a.price)}</strong>${triggered ? ' <span style="color:var(--gold2)">✅ Đã kích hoạt!</span>' : ''}</div>
          </div>
          <button class="al-item-del" data-id="${a.id}">Xoá</button>
        </div>`;
    }).join('');

    $list.html(html);

    // Check for triggered alerts → toast
    const triggered = alerts.filter(a => {
      const cur = currentPrices[a.asset];
      return a.type === 'above' ? cur >= a.price : cur <= a.price;
    });
    if (triggered.length) showToast(`⚡ ${triggered.length} cảnh báo giá đã kích hoạt!`, '🔔');
  }

  $('#alert-btn').on('click', function () {
    const asset = $('#alert-asset').val();
    const type = $('#alert-type').val();
    const price = parseFloat($('#alert-price').val());

    if (!price || price <= 0) {
      showToast('Vui lòng nhập mốc giá hợp lệ', '⚠️');
      return;
    }

    const alerts = getAlerts();
    alerts.push({ id: Date.now(), asset, type, price });
    saveAlerts(alerts);
    renderAlertList();
    $('#alert-price').val('');
    showToast(`Đã đặt cảnh báo: ${assetLabels[asset]} ${type === 'above' ? '>' : '<'} ${fmt(price)}`, '🔔');
  });

  $(document).on('click', '.al-item-del', function () {
    const id = parseInt($(this).data('id'));
    const alerts = getAlerts().filter(a => a.id !== id);
    saveAlerts(alerts);
    renderAlertList();
    showToast('Đã xoá cảnh báo', '🗑️');
  });

  $('#alert-clear-all').on('click', function () {
    saveAlerts([]);
    renderAlertList();
    showToast('Đã xoá tất cả cảnh báo', '🗑️');
  });

  /* ============================================================
     REFRESH BUTTON
  ============================================================ */
  $('#btn-refresh').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true);
    $btn.find('svg').addClass('is-spinning');

    setTimeout(() => {
      renderAll();
      $btn.prop('disabled', false);
      $btn.find('svg').removeClass('is-spinning');
      showToast('Dữ liệu đã được cập nhật!', '✅');
    }, 1200);
  });

  /* ============================================================
     SIDEBAR NAV ACTIVE STATE (scroll spy)
  ============================================================ */
  const sections = ['prices', 'compare', 'spread', 'chart', 'analysis', 'calculator', 'alert'];
  $(window).on('scroll', function () {
    const scrollTop = $(window).scrollTop() + 120;
    let active = sections[0];
    sections.forEach(id => {
      const el = document.getElementById(id);
      if (el && el.offsetTop <= scrollTop) active = id;
    });
    $('.sn-link').removeClass('active');
    $(`.sn-link[href="#${active}"]`).addClass('active');

    // Back to top
    if ($(window).scrollTop() > 400) {
      $('#back-to-top').addClass('visible');
    } else {
      $('#back-to-top').removeClass('visible');
    }
  });

  // Smooth scroll header nav & sidebar
  $(document).on('click', '.sn-link, .hn-link', function (e) {
    const href = $(this).attr('href');
    if (href && href.startsWith('#')) {
      e.preventDefault();
      const target = $(href);
      if (target.length) {
        const offset = target.offset().top - 100;
        $('html,body').animate({ scrollTop: offset }, 450, 'swing');
        if (window.innerWidth <= 900) closeSidebar();
      }
    }
  });

  /* ============================================================
     MOBILE SIDEBAR TOGGLE
  ============================================================ */
  function openSidebar() {
    $('#sidebar').addClass('open');
    $('#sidebar-overlay').addClass('show');
    $('#hamburger').addClass('open').attr('aria-expanded', 'true');
  }
  function closeSidebar() {
    $('#sidebar').removeClass('open');
    $('#sidebar-overlay').removeClass('show');
    $('#hamburger').removeClass('open').attr('aria-expanded', 'false');
  }
  $('#hamburger').on('click', function () {
    $('#sidebar').hasClass('open') ? closeSidebar() : openSidebar();
  });
  $('#sidebar-overlay').on('click', closeSidebar);

  /* ============================================================
     BACK TO TOP
  ============================================================ */
  $('#back-to-top').on('click', function () {
    $('html,body').animate({ scrollTop: 0 }, 400);
  });

  /* ============================================================
     RENDER ALL
  ============================================================ */
  function renderAll() {
    updateTimestamps();
    renderCompareTable();
    brands.forEach(b => renderBrandPanel(b.id));
    renderSilverPanel();
    renderCompareWorld();
    renderSpread();
    renderChart();
    renderAnalysis();
    renderPrediction($('.pred-tab.active').data('pred') || 'vang');
    renderAlertList();
    buildTicker();
  }

  // Auto refresh every 5 minutes
  setInterval(function () {
    renderAll();
    showToast('Dữ liệu cập nhật tự động', '🔄');
  }, 5 * 60 * 1000);

  /* ============================================================
     INIT
  ============================================================ */
  renderAll();
  // Pre-render brand panels (hidden, for instant tab switch)
  brands.forEach(b => renderBrandPanel(b.id));
  renderSilverPanel();
});
