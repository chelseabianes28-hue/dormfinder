// Dashboard charts (Chart.js), loaded with the Fetch API, refreshed live every 30 seconds, and theme-aware.
const css = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
const palette = () => ({
  text: css('--bs-secondary-color', '#6c757d'),
  grid: css('--bs-border-color', '#e9ecef'),
  ink: css('--bs-body-color', '#111111'),
});
const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

let colors = palette();
const instances = [];

const STATUS_COLORS = { Approved: '#2f9e6b', Pending: '#f2b84b', Rejected: '#e5636b' };
const accent = () => (isDark() ? '#5B8CFF' : '#2D6AF0');
const navy = () => (isDark() ? '#8E9BE0' : '#2C3A72');
const sand = () => (isDark() ? '#6B645C' : '#D8CFC4');
const colorFor = (label, index) =>
  STATUS_COLORS[label]
  ?? { Students: accent(), Owners: navy(), Admins: sand() }[label]
  ?? [accent(), navy(), sand(), '#f2b84b', '#2f9e6b'][index % 5];

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;

// Shows the total in the middle of a doughnut.
const centerTotal = {
  id: 'centerTotal',
  afterDraw(chart) {
    if (chart.config.type !== 'doughnut') return;
    const total = chart.data.datasets[0].data.reduce((sum, value) => sum + value, 0);
    const { ctx, chartArea: { left, right, top, bottom } } = chart;
    const x = (left + right) / 2;
    const y = (top + bottom) / 2;
    ctx.save();
    ctx.textAlign = 'center';
    ctx.fillStyle = colors.ink;
    ctx.font = `600 28px ${Chart.defaults.font.family}`;
    ctx.fillText(String(total), x, y + 6);
    ctx.fillStyle = colors.text;
    ctx.font = `12px ${Chart.defaults.font.family}`;
    ctx.fillText('total', x, y + 24);
    ctx.restore();
  },
};

// Writes the number at the end of each bar.
const barValues = {
  id: 'barValues',
  afterDatasetsDraw(chart) {
    if (chart.config.type !== 'bar') return;
    const { ctx } = chart;
    ctx.save();
    ctx.fillStyle = colors.ink;
    ctx.font = `600 12px ${Chart.defaults.font.family}`;
    ctx.textBaseline = 'middle';
    chart.getDatasetMeta(0).data.forEach((bar, index) => {
      ctx.fillText(String(chart.data.datasets[0].data[index]), bar.x + 8, bar.y);
    });
    ctx.restore();
  },
};

// Find the chart's canvas. If an earlier load showed "No data yet", put a fresh canvas back.
const ensureCanvas = (canvasId) => {
  const id = canvasId.slice(1);
  let canvas = document.getElementById(id);
  if (canvas) return canvas;
  const holder = document.querySelector(`[data-chart-for="${id}"]`);
  if (!holder) return null;
  canvas = Object.assign(document.createElement('canvas'), { id });
  canvas.setAttribute('role', 'img');
  holder.replaceChildren(canvas);
  return canvas;
};

const drawChart = (canvasId, type, items, title) => {
  const canvas = ensureCanvas(canvasId);
  if (!canvas) return;

  if (!items || items.every((item) => item.value === 0)) {
    canvas.parentElement.dataset.chartFor = canvasId.slice(1);
    canvas.parentElement.replaceChildren(Object.assign(document.createElement('p'), {
      className: 'text-body-secondary mb-0 pt-5 text-center', textContent: 'No data yet.',
    }));
    return;
  }
  canvas.setAttribute('aria-label', `${title}: ${items.map((item) => `${item.label} ${item.value}`).join(', ')}`);

  const isDoughnut = type === 'doughnut';
  Chart.defaults.color = colors.text;
  instances.push(new Chart(canvas, {
    type,
    data: {
      labels: items.map((item) => item.label),
      datasets: [{
        data: items.map((item) => item.value),
        backgroundColor: items.map((item, index) => (isDoughnut ? colorFor(item.label, index) : accent())),
        borderWidth: 0,
        borderRadius: isDoughnut ? 10 : 8,
        borderSkipped: false,
        spacing: isDoughnut ? 4 : 0,
        hoverOffset: isDoughnut ? 6 : 0,
        barThickness: isDoughnut ? undefined : 16,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: isDoughnut ? '72%' : undefined,
      indexAxis: isDoughnut ? 'x' : 'y',
      layout: { padding: isDoughnut ? 4 : { right: 28 } },
      animation: { duration: 900, easing: 'easeOutQuart' },
      plugins: {
        legend: {
          display: isDoughnut,
          position: 'bottom',
          labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 16 },
        },
        tooltip: {
          backgroundColor: isDark() ? '#2b2f33' : '#111111',
          borderColor: colors.grid,
          borderWidth: 1,
          padding: 10,
          cornerRadius: 10,
          boxPadding: 4,
          titleFont: { weight: '600' },
        },
      },
      scales: isDoughnut ? {} : {
        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: colors.grid }, border: { display: false } },
        y: {
          grid: { display: false },
          border: { display: false },
          ticks: {
            callback(value) {
              const label = this.getLabelForValue(value);
              return label.length > 18 ? `${label.slice(0, 17)}…` : label;
            },
          },
        },
      },
    },
    plugins: [centerTotal, barValues],
  }));
};

// Live dashboard: the numbers are checked again every 30 seconds (only while the tab is visible).
// The charts are redrawn only when something actually changed.
const REFRESH_MS = 30000;
let lastData = '';

const liveLabel = (box) => {
  let label = document.querySelector('#chartsLive');
  if (!label) {
    label = Object.assign(document.createElement('p'), { id: 'chartsLive', className: 'small text-body-secondary mb-2' });
    label.setAttribute('aria-live', 'polite');
    box.before(label);
  }
  return label;
};

const render = (data) => {
  instances.splice(0).forEach((chart) => chart.destroy());
  const { charts } = data;
  drawChart('#chartStatus', 'doughnut', charts.status, 'Listings by status');
  if (data.role === 'admin') {
    drawChart('#chartRoles', 'doughnut', charts.roles, 'Users by role');
    drawChart('#chartCities', 'bar', charts.cities, 'Approved listings by city');
  } else {
    drawChart('#chartInquiries', 'bar', charts.inquiries, 'Inquiries per listing');
  }
};

const loadCharts = async ({ force = false } = {}) => {
  const box = document.querySelector('#dashboardCharts');
  if (!box) return;

  try {
    const response = await fetch(box.dataset.endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');

    const text = JSON.stringify(data.charts);
    if (force || text !== lastData) {
      lastData = text;
      render(data);
    }
    const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit' });
    liveLabel(box).innerHTML = `<i class="bi bi-broadcast text-success" aria-hidden="true"></i> Live · updates every 30 seconds · last checked ${time}`;
  } catch (error) {
    if (lastData) return;                 // keep the charts we already have if one refresh fails
    box.innerHTML = '<div class="col-12"><div class="alert alert-danger" role="alert"></div></div>';
    box.querySelector('.alert').textContent = error.message || 'Unable to load the charts.';
  }
};

// Redraw the charts with the right colors when the theme changes.
document.addEventListener('themechange', () => {
  colors = palette();
  loadCharts({ force: true });
});

document.addEventListener('DOMContentLoaded', () => {
  loadCharts({ force: true });
  setInterval(() => { if (!document.hidden) loadCharts(); }, REFRESH_MS);
});
document.addEventListener('visibilitychange', () => { if (!document.hidden) loadCharts(); });
