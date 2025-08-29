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


<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0/dist/chartjs-plugin-annotation.min.js"></script>

<script>
$(function() {
    // --- Chart Initialization ---
    const ctx = document.getElementById('scurveMainChart').getContext('2d');
    let scurveMainChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Cumulative Plan',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }, {
                label: 'Cumulative Actual',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                annotation: {
                    annotations: {
                        todayLine: {
                            type: 'line',
                            scaleID: 'x',
                            value: null, // We will set this dynamically
                            borderColor: 'red',
                            borderWidth: 2,
                            label: {
                                enabled: true,
                                content: 'Today',
                                position: 'end'
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Cumulative Progress (%)' }
                },
                x: {
                    title: { display: true, text: 'Period' }
                }
            }
        }
    });

    // --- Data Loading and Processing ---
    function loadChartData(startDate = null, endDate = null) {
        // Prepare data for POST request, including the CSRF token
        const postData = {
            start_date: startDate,
            end_date: endDate,
            [csrfData.token_name]: csrfData.hash // THIS IS THE FIX FOR 403 ERROR
        };
        
        const projectId = "<?php echo $project->id; ?>"; // Get project ID from PHP
        
        $.ajax({
            url: "<?php echo admin_url('scurve_report/get_chart_data/'); ?>" + projectId,
            type: "POST",
            data: postData,
            dataType: "json",
            success: function(response) {
                // Update CSRF token for next request
                csrfData.hash = response.csrfHash;

                const data = response.chartData;

                if (data.labels.length > 0) {
                  // Update Chart
                  scurveMainChart.data.labels = data.labels;
                  scurveMainChart.data.datasets[0].data = data.plan;
                  scurveMainChart.data.datasets[1].data = data.actual;
                  
                  // Update "Today" line annotation
                  scurveMainChart.options.plugins.annotation.annotations.todayLine.value = data.todayIndex;
                  
                  scurveMainChart.update();
                  
                  // Update Table
                  updateTable(data);
                } else {
                  // Handle no data case
                  $('#scurveDataTable tbody').html('<tr><td colspan="6" class="text-center"><?php echo _l("scurve_no_data"); ?></td></tr>');
                }
            },
            error: function() {
                alert('Failed to load chart data. Please check browser console for errors.');
                $('#scurveDataTable tbody').html('<tr><td colspan="6" class="text-center">Error loading data.</td></tr>');
            }
        });
    }

    function updateTable(data) {
        const tbody = $("#scurveDataTable tbody");
        tbody.empty();
        let prevPlan = 0;
        let prevActual = 0;

        for (let i = 0; i < data.labels.length; i++) {
            const cumPlan = parseFloat(data.plan[i]);
            const cumActual = parseFloat(data.actual[i]);
            
            // Calculate incremental progress for this period
            const incrementalPlan = (cumPlan - prevPlan).toFixed(2);
            const incrementalActual = (cumActual - prevActual).toFixed(2);
            
            const deviation = (cumActual - cumPlan).toFixed(2);

            tbody.append(`
                <tr>
                    <td>${data.labels[i]}</td>
                    <td>${incrementalPlan}%</td>
                    <td>${cumPlan.toFixed(2)}%</td>
                    <td>${incrementalActual}%</td>
                    <td>${cumActual.toFixed(2)}%</td>
                    <td class="${deviation < 0 ? 'text-danger' : 'text-success'}">${deviation}%</td>
                </tr>
            `);

            // Update previous values for the next iteration
            prevPlan = cumPlan;
            prevActual = cumActual;
        }
    }

    // --- Event Handlers ---
    $('#scurve_date_range').daterangepicker({
        opens: 'left',
        autoUpdateInput: false, // Important for custom ranges
        locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' }
    });

    $('#scurve_date_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        loadChartData(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
    });

    $('#scurve_time_filter').on('change', function() {
        const viewType = $(this).val();
        let start, end;

        if (viewType === 'all') {
            $('#scurve_date_range_container').hide();
            loadChartData(); // Load all data
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

    // --- Initial Load ---
    loadChartData();
});
</script>