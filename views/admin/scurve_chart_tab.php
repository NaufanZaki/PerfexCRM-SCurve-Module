<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
  <div class="col-md-12">
    <h4 class="tw-font-semibold tw-text-lg"><?php echo _l('scurve_progress_chart'); ?></h4>
  </div>
</div>

<div class="row mbot15">
  <div class="col-md-3">
    <label for="scurve_time_filter" class="control-label"><strong>View By:</strong></label>
    <select id="scurve_time_filter" class="form-control selectpicker" data-width="100%">
      <option value="all" selected>All Time</option>
      <option value="this_month">This Month</option>
      <option value="last_month">Last Month</option>
      <option value="custom">Custom Range</option>
    </select>
  </div>
  <div class="col-md-3" id="scurve_date_range_container" style="display: none;">
    <label for="scurve_date_range" class="control-label"><strong>Date Range:</strong></label>
    <input type="text" id="scurve_date_range" class="form-control" placeholder="Select date range" />
  </div>
</div>

<!-- Two small charts above the main chart -->
<div class="row mbot15">
  <div class="col-md-6">
    <div class="panel_s">
      <div class="panel-body" style="height: 200px;">
        <canvas id="scurvePlanChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel_s">
      <div class="panel-body" style="height: 200px;">
        <canvas id="scurveActualChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Main chart below -->
<div class="row">
  <div class="col-md-12">
    <div class="panel_s">
      <div class="panel-body" style="height: 400px;">
        <canvas id="scurveMainChart"></canvas>
      </div>
    </div>
  </div>
</div>

<hr />

<div class="row">
  <div class="col-md-12">
    <h4>Progress Data</h4>
    <div class="table-responsive">
      <table class="table table-bordered" id="scurveDataTable">
        <thead>
          <tr>
            <th>Period</th>
            <th>Plan Progress</th>
            <th>Cumulative Plan</th>
            <th>Actual Progress</th>
            <th>Cumulative Actual</th>
            <th>Deviation</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="6" class="text-center">Loading data...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart.js + plugins -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0/dist/chartjs-plugin-annotation.min.js"></script>

<script>
(function waitForjQuery() {
  if (typeof window.jQuery === "undefined") {
    console.warn("⏳ Waiting for jQuery...");
    return setTimeout(waitForjQuery, 200);
  }

  jQuery(function ($) {
    // Explicitly register the annotation plugin with Chart.js
    // This is now inside the jQuery ready function to ensure all scripts are loaded.
    Chart.register(ChartAnnotation);

    const csrfData = {
      token_name: '<?php echo $this->security->get_csrf_token_name(); ?>',
      hash: '<?php echo $this->security->get_csrf_hash(); ?>'
    };
    const projectId = <?php echo json_encode(isset($project->id) ? (int) $project->id : 0); ?>;

    const ctxMain = document.getElementById('scurveMainChart').getContext('2d');
    const ctxPlan = document.getElementById('scurvePlanChart').getContext('2d');
    const ctxActual = document.getElementById('scurveActualChart').getContext('2d');

    var scurveMainChart = new Chart(ctxMain, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest', intersect: false },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var dateLabel = ctx.label || '';
                return ctx.dataset.label + ': ' + ctx.formattedValue + '% (' + dateLabel + ')';
              }
            }
          },
          annotation: { // This initial block is fine, it gets overwritten by our dynamic one
            annotations: {}
          }
        },
        scales: {
          y: { beginAtZero: true, max: 100, title: { display: true, text: 'Cumulative Progress (%)' } },
          x: {
            type: 'time',
            time: {
              parser: 'yyyy-MM-dd',
              tooltipFormat: 'PPP',
              unit: 'day',
              displayFormats: { day: 'yyyy-MM-dd' }
            },
            title: { display: true, text: 'Date / Period' }
          }
        }
      }
    });

    var scurvePlanChart = new Chart(ctxPlan, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: 100, title: { display: true, text: 'Cumulative Plan (%)' } },
          x: {
            type: 'time',
            time: { parser: 'yyyy-MM-dd', unit: 'day', displayFormats: { day: 'yyyy-MM-dd' }},
            title: { display: true, text: 'Date / Period' }
          }
        }
      }
    });

    var scurveActualChart = new Chart(ctxActual, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: 100, title: { display: true, text: 'Cumulative Actual (%)' } },
          x: {
            type: 'time',
            time: { parser: 'yyyy-MM-dd', unit: 'day', displayFormats: { day: 'yyyy-MM-dd' }},
            title: { display: true, text: 'Date / Period' }
          }
        }
      }
    });

    function loadChartData(startDate, endDate) {
      startDate = (typeof startDate !== 'undefined') ? startDate : null;
      endDate = (typeof endDate !== 'undefined') ? endDate : null;
      var postData = { start_date: startDate, end_date: endDate };
      postData[csrfData.token_name] = csrfData.hash;

      $.ajax({
        url: "<?php echo admin_url('scurve_report/get_chart_data/'); ?>" + projectId,
        type: "POST",
        data: postData,
        dataType: "json",
        success: function (response) {
          csrfData.hash = response.csrfHash;
          var data = response.chartData || {};
          var dates = (data && data.dates) || null;
          var periods = (data && data.labels) || null;
          var xLabels = dates ? dates : (periods ? periods : []);

          if (data && xLabels.length > 0) {
            scurveMainChart.data.labels = xLabels;
            scurveMainChart.data.datasets = [
              { label: 'Cumulative Plan', data: data.plan, borderColor: 'rgb(75,192,192)', tension: 0.3, fill: false, pointBackgroundColor: 'rgb(75,192,192)' },
              { label: 'Cumulative Actual', data: data.actual, borderColor: 'rgb(255,99,132)', tension: 0.3, fill: false, pointBackgroundColor: 'rgb(255,99,132)' }
            ];

            const today = moment().format('YYYY-MM-DD');
            const todayValue = data.todayValue;
            const annotations = {
              todayLine: {
                type: 'line',
                scaleID: 'x',
                value: today,
                borderColor: 'rgba(255, 99, 132, 0.5)',
                borderWidth: 2,
                borderDash: [6, 6],
                label: {
                  enabled: true,
                  content: 'Today',
                  position: 'start',
                  backgroundColor: 'rgba(255, 99, 132, 0.7)',
                  color: '#fff',
                  yAdjust: -15,
                }
              }
            };

            if (todayValue !== null) {
              annotations.todayValuePoint = {
                type: 'point',
                xValue: today,
                yValue: todayValue,
                backgroundColor: 'rgba(255, 99, 132, 1)',
                borderColor: '#fff',
                borderWidth: 2,
                radius: 6,
                label: {
                  enabled: true,
                  content: `Actual: ${todayValue}%`,
                  position: 'end',
                  backgroundColor: 'rgba(255, 99, 132, 0.8)',
                  color: '#fff',
                  yAdjust: -15,
                }
              };
            }

            scurveMainChart.options.plugins.annotation.annotations = annotations;
            scurveMainChart.update();

            scurvePlanChart.data.labels = xLabels;
            scurvePlanChart.data.datasets = [{ label: 'Cumulative Plan', data: data.plan, borderColor: 'rgb(75,192,192)', tension: 0.3, fill: false }];
            scurvePlanChart.update();

            scurveActualChart.data.labels = xLabels;
            scurveActualChart.data.datasets = [{ label: 'Cumulative Actual', data: data.actual, borderColor: 'rgb(255,99,132)', tension: 0.3, fill: false }];
            scurveActualChart.update();

            updateTable(data, xLabels, periods);
          } else {
            $('#scurveDataTable tbody').html('<tr><td colspan="6" class="text-center">No data</td></tr>');
            scurveMainChart.data.labels = []; scurveMainChart.data.datasets = []; scurveMainChart.update();
            scurvePlanChart.data.labels = []; scurvePlanChart.data.datasets = []; scurvePlanChart.update();
            scurveActualChart.data.labels = []; scurveActualChart.data.datasets = []; scurveActualChart.update();
          }
        },
        error: function (xhr, status, error) {
          console.error("❌ AJAX error", xhr, status, error);
        }
      });
    }

    function updateTable(data, datesArray, periodsArray) {
      datesArray = (typeof datesArray !== 'undefined') ? datesArray : null;
      periodsArray = (typeof periodsArray !== 'undefined') ? periodsArray : null;
      var tbody = $("#scurveDataTable tbody");
      tbody.empty();
      var prevPlan = 0, prevActual = 0;
      var length = (datesArray ? datesArray.length : (data.labels ? data.labels.length : 0));
      for (var i = 0; i < length; i++) {
        var cumPlan = parseFloat(data.plan[i]) || 0;
        var cumActual = parseFloat(data.actual[i]) || 0;
        var incrementalPlan = (cumPlan - prevPlan).toFixed(2);
        var incrementalActual = (cumActual - prevActual).toFixed(2);
        var deviation = (cumActual - cumPlan).toFixed(2);
        var deviationNum = parseFloat(deviation);
        var deviationClass = deviationNum < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
        var deviationSymbol = deviationNum < 0 ? '🔽' : '🔼';
        var periodLabel = (periodsArray && periodsArray[i]) ? periodsArray[i] : '';
        var dateLabel = (datesArray && datesArray[i]) ? datesArray[i] : '';
        var firstCol = periodLabel && dateLabel ? (periodLabel + ' — ' + dateLabel) : (dateLabel ? dateLabel : periodLabel);
        tbody.append('<tr><td>' + firstCol + '</td><td>' + incrementalPlan + '%</td><td>' + cumPlan.toFixed(2) + '%</td><td>' + incrementalActual + '%</td><td>' + cumActual.toFixed(2) + '%</td><td class="' + deviationClass + '">' + deviation + '% ' + deviationSymbol + '</td></tr>');
        prevPlan = cumPlan; prevActual = cumActual;
      }
    }

    $('#scurve_time_filter').on('change', function () {
      var viewType = $(this).val();
      var start, end;
      if (viewType === 'all') {
        $('#scurve_date_range_container').hide();
        loadChartData();
      } else if (viewType === 'this_month') {
        $('#scurve_date_range_container').hide();
        start = moment().startOf('month').format('YYYY-MM-DD');
        end = moment().endOf('month').format('YYYY-MM-DD');
        loadChartData(start, end);
      } else if (viewType === 'last_month') {
        $('#scurve_date_range_container').hide();
        start = moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
        end = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
        loadChartData(start, end);
      } else if (viewType === 'custom') {
        $('#scurve_date_range_container').show();
      }
    });

    var startDefault = moment().startOf('month').format('YYYY-MM-DD');
    var endDefault = moment().endOf('month').format('YYYY-MM-DD');
    $('#scurve_time_filter').val('this_month');
    loadChartData(startDefault, endDefault);
  });
})();
</script>


