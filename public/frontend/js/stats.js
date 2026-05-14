/* ═══ stats page scripts ═══ */
(function(){
  var labels = @json($dailyViews->pluck('date'));
  var views  = @json($dailyViews->pluck('views'));
  var uniq   = @json($dailyViews->pluck('unique_ips'));

  new Chart(document.getElementById('daily-chart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Lượt xem',
          data: views,
          borderColor: '#4f7af8',
          backgroundColor: 'rgba(79,122,248,0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.35,
          pointRadius: 3,
          pointHoverRadius: 6,
        },
        {
          label: 'Unique IP',
          data: uniq,
          borderColor: '#a78bfa',
          backgroundColor: 'rgba(167,139,250,0.06)',
          borderWidth: 2,
          fill: true,
          tension: 0.35,
          pointRadius: 3,
          pointHoverRadius: 6,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { color: '#909ab2', font: { size: 12 } } },
      },
      scales: {
        x: { ticks: { color: '#6e778c', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
        y: { ticks: { color: '#6e778c', font: { size: 11 } }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true }
      }
    }
  });
})();
