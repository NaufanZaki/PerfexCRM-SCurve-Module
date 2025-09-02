<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
  <div class="col-md-12">
    <h4 class="mbot20"><?php echo _l('scurve_progress_chart'); ?></h4>
  </div>
</div>

<div class="row mbot20">
  <div class="col-md-3">
    <label for="timeFilter"><strong>View By:</strong></label>
    <select id="timeFilter" class="form-control">
      <option value="week" selected>Per Week</option>
      <option value="month">Per Month</option>
      <option value="year">Per Year</option>
    </select>
  </div>
</div>


<!-- Top small charts -->
<div class="row">
  <div class="col-md-6">
    <div class="panel_s">
      <div class="panel-body text-center">
        <h4><?php echo _l('scurve_planned_progress'); ?></h4>
        <h2 id="planValue">0%</h2>
        <canvas id="planChart" height="150"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel_s">
      <div class="panel-body text-center">
        <h4><?php echo _l('scurve_actual_progress'); ?></h4>
        <h2 id="actualValue">0%</h2>
        <canvas id="actualChart" height="150"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Main chart -->
<div class="row">
  <div class="col-md-12">
    <div class="panel_s">
      <div class="panel-body">
        <canvas id="mainChart" height="300"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ChartJS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0"></script>
<script>
const dataSets = {
  week: {
    labels: ["W1","W2","W3","W4","W5","W6"],
    plan:   [10, 35, 55, 70, 78, 95],
    actual: [12, 40, 60, 72, 85, 100],
    nowIndex: 4, // W5
    nowLabel: "Now"
  },
  month: {
    labels: ["Jan","Feb","Mar","Apr","May","Jun"],
    plan:   [15, 40, 65, 80, 90, 100],
    actual: [18, 42, 70, 82, 88, 97],
    nowIndex: 4, // May
    nowLabel: "Now"
  },
  year: {
    labels: ["2021","2022","2023","2024","2025"],
    plan:   [20, 45, 70, 85, 100],
    actual: [22, 48, 68, 82, 96],
    nowIndex: 4, // 2025
    nowLabel: "Now"
  }
};

let planChart, actualChart, mainChart;

function createSmallChart(ctx, data, color, labels, nowIndex, nowLabel) {
  return new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [{
        data: data,
        borderColor: color,
        borderWidth: 3,
        fill: false,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        annotation: {
          annotations: {
            line1: {
              type: "line",
              xMin: nowIndex,
              xMax: nowIndex,
              borderColor: "red",
              borderWidth: 2,
              label: {
                content: nowLabel,
                enabled: true,
                position: "end"
              }
            }
          }
        }
      },
      scales: {
        y: {
          min: 0,
          max: 100,
          ticks: { callback: val => val + "%" }
        }
      }
    }
  });
}

function updateCharts(viewType) {
  const { labels, plan, actual, nowIndex, nowLabel } = dataSets[viewType];

  // Destroy old charts before redrawing
  if (planChart) planChart.destroy();
  if (actualChart) actualChart.destroy();
  if (mainChart) mainChart.destroy();

  // Update cumulative numbers
  document.getElementById("planValue").textContent   = plan[plan.length-1] + "%";
  document.getElementById("actualValue").textContent = actual[actual.length-1] + "%";

  // Rebuild charts
  planChart   = createSmallChart(document.getElementById("planChart"), plan, "green", labels, nowIndex, nowLabel);
  actualChart = createSmallChart(document.getElementById("actualChart"), actual, "purple", labels, nowIndex, nowLabel);

  mainChart = new Chart(document.getElementById("mainChart"), {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        { label: "Planned", data: plan, borderColor: "green", borderWidth: 3, fill: false, tension: 0.3 },
        { label: "Actual", data: actual, borderColor: "purple", borderWidth: 3, fill: false, tension: 0.3 }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: "bottom" },
        annotation: {
          annotations: {
            line1: {
              type: "line",
              xMin: nowIndex,
              xMax: nowIndex,
              borderColor: "red",
              borderWidth: 2,
              label: { content: nowLabel, enabled: true, position: "end" }
            }
          }
        }
      },
      scales: {
        y: { min: 0, max: 100, ticks: { callback: v => v + "%" } }
      }
    }
  });
}

// Initial load
updateCharts("week");

// Listen to dropdown
document.getElementById("timeFilter").addEventListener("change", function() {
  updateCharts(this.value);
});
</script>

<div class="row mbot20">
  <div class="col-md-3">
    <label for="timeFilter"><strong>View By:</strong></label>
    <select id="timeFilter" class="form-control">
      <option value="week" selected>Per Week</option>
      <option value="month">Per Month</option>
      <option value="year">Per Year</option>
    </select>
  </div>

  <div class="col-md-4">
    <label for="dateRange"><strong>Date Range:</strong></label>
    <input type="text" id="dateRange" class="form-control" />
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <canvas id="scurveChart" height="100"></canvas>
  </div>
</div>

<hr>

<div class="row">
  <div class="col-md-12">
    <h4>Progress Data</h4>
    <table class="table table-bordered" id="scurveDataTable">
      <thead>
        <tr>
          <th>Period</th>
          <th>Plan Progress</th>
          <th>Cumulative Plan</th>
          <th>Actual Progress</th>
          <th>Cumulative Actual</th>
          <th>Deviasi</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<?php app_select_plugin_js('moment'); ?>
<?php app_select_plugin_js('daterangepicker'); ?>
<?php app_select_plugin_css('daterangepicker'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation"></script>

<script>
let currentView = "week";
let projectId = "<?php echo isset($project_id) ? $project_id : 0; ?>"; // Set this from your backend

const ctx = document.getElementById('scurveChart').getContext('2d');
let scurveChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: [],
    datasets: [
      { label: 'Cumulative Plan', data: [], borderColor: 'green', fill: false },
      { label: 'Cumulative Actual', data: [], borderColor: 'blue', fill: false }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      annotation: {
        annotations: {
          currentLine: {
            type: 'line',
            xMin: null,
            xMax: null,
            borderColor: 'red',
            borderWidth: 2,
            label: { enabled: true, content: 'Today' }
          }
        }
      }
    },
    scales: {
      y: { beginAtZero: true, max: 100 }
    }
  }
});

function updateTable(data) {
  const tbody = $("#scurveDataTable tbody");
  tbody.empty();

  let cumPlan = 0, cumActual = 0;
  for (let i = 0; i < data.labels.length; i++) {
    cumPlan += data.plan[i];
    cumActual += data.actual[i];
    const deviasi = cumActual - cumPlan;
    tbody.append(`
      <tr>
        <td>${data.labels[i]}</td>
        <td>${data.plan[i]}%</td>
        <td>${cumPlan}%</td>
        <td>${data.actual[i]}%</td>
        <td>${cumActual}%</td>
        <td>${deviasi}%</td>
      </tr>
    `);
  }
}

function loadChartData(projectId, viewType="week", rangeStart=null, rangeEnd=null) {
  let url = admin_url + "scurve_report/get_chart_data/" + projectId + "?viewType=" + viewType;
  if (rangeStart && rangeEnd) {
    url += "&start=" + rangeStart + "&end=" + rangeEnd;
  }
  $.get(url, function(resp) {
    const data = JSON.parse(resp);

    scurveChart.data.labels = data.labels;
    scurveChart.data.datasets[0].data = data.plan;
    scurveChart.data.datasets[1].data = data.actual;

    // Set annotation line to current index if provided
    let todayIdx = data.nowIndex !== undefined ? data.nowIndex : Math.floor(data.labels.length / 2);
    scurveChart.options.plugins.annotation.annotations.currentLine.xMin = todayIdx;
    scurveChart.options.plugins.annotation.annotations.currentLine.xMax = todayIdx;
    scurveChart.options.plugins.annotation.annotations.currentLine.label.content = data.nowLabel || 'Today';

    scurveChart.update();
    updateTable(data);
  });
}

// Filter by time unit
$("#timeFilter").on("change", function() {
  currentView = this.value;
  loadChartData(projectId, currentView);
});

// Date range picker
$('#dateRange').daterangepicker({
  opens: 'right',
  autoUpdateInput: true,
  locale: { format: 'YYYY-MM-DD' },
  startDate: moment().startOf('month'),
  endDate: moment().endOf('month')
}, function(start, end) {
  loadChartData(projectId, currentView, start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
});

// Init default
loadChartData(projectId, currentView);
</script>