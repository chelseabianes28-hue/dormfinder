// Dashboard charts (Chart.js) loaded with the Fetch API.
const COLORS = ['#111111', '#8a8a8a', '#d9b8e6', '#c8c8c8', '#5b3a66', '#e9e9e9'];

const drawChart = (canvasId, type, items, options = {}) => {
  const canvas = document.querySelector(canvasId);
  if (!canvas) return;

  if (!items || items.every((item) => item.value === 0)) {
    canvas.replaceWith(Object.assign(document.createElement('p'), { className: 'text-body-secondary mb-0', textContent: 'No data yet.' }));
    return;
  }
  const short = (text) => (text.length > 22 ? `${text.slice(0, 21)}…` : text);

  new Chart(canvas, {
    type,
    data: {
      labels: items.map((item) => short(item.label)),
      datasets: [{ data: items.map((item) => item.value), backgroundColor: COLORS, borderWidth: 0, borderRadius: type === 'bar' ? 6 : 0 }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: type === 'doughnut', position: 'bottom' } },
      scales: type === 'bar' ? { x: { ticks: { precision: 0 } }, y: { ticks: { precision: 0 } }, ...options.scales } : {},
      indexAxis: options.indexAxis ?? 'x',
    },
  });
};

const loadCharts = async () => {
  const box = document.querySelector('#dashboardCharts');
  if (!box) return;

  try {
    const response = await fetch(box.dataset.endpoint, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');

    const { charts } = data;
    drawChart('#chartStatus', 'doughnut', charts.status);
    if (data.role === 'admin') {
      drawChart('#chartRoles', 'doughnut', charts.roles);
      drawChart('#chartCities', 'bar', charts.cities, { indexAxis: 'y' });
    } else {
      drawChart('#chartInquiries', 'bar', charts.inquiries, { indexAxis: 'y' });
    }
  } catch (error) {
    box.innerHTML = '<div class="col-12"><div class="alert alert-danger" role="alert"></div></div>';
    box.querySelector('.alert').textContent = error.message || 'Unable to load the charts.';
  }
};

document.addEventListener('DOMContentLoaded', loadCharts);
