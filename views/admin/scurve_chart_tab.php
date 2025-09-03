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

    jQuery(function($) {
        const csrfData = {
            token_name: '<?php echo $this->security->get_csrf_token_name(); ?>',
            hash: '<?php echo $this->security->get_csrf_hash(); ?>'
        };
        const projectId = <?php echo (int)$project->id; ?>;

        // --- Chart Init ---
        const ctx = document.getElementById('scurveMainChart').getContext('2d');
        let scurveMainChart = new Chart(ctx, {
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
                            label: (ctx) => ctx.dataset.label + ': ' + ctx.formattedValue + '%'
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: 100, title: { display: true, text: 'Cumulative Progress (%)' } },
                    x: { title: { display: true, text: 'Period' } }
                }
            }
        });

        // --- Load Data ---
        function loadChartData(startDate = null, endDate = null) {
            console.log("🔄 loadChartData", { startDate, endDate });

            const postData = {
                start_date: startDate,
                end_date: endDate,
                [csrfData.token_name]: csrfData.hash
            };

            $.ajax({
                url: "<?php echo admin_url('scurve_report/get_chart_data/'); ?>" + projectId,
                type: "POST",
                data: postData,
                dataType: "json",
                success: function(response) {
                    csrfData.hash = response.csrfHash;
                    const data = response.chartData;

                    if (data && data.labels.length > 0) {
                        // Chart update
                        scurveMainChart.data.labels = data.labels;
                        scurveMainChart.data.datasets = [
                            {
                                label: 'Cumulative Plan',
                                data: data.plan,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.3,
                                fill: false,
                                pointBackgroundColor: 'rgb(75, 192, 192)'
                            },
                            {
                                label: 'Cumulative Actual',
                                data: data.actual,
                                borderColor: 'rgb(255, 99, 132)',
                                tension: 0.3,
                                fill: false,
                                pointBackgroundColor: 'rgb(255, 99, 132)'
                            }
                        ];
                        scurveMainChart.update();

                        // Table update
                        updateTable(data);
                    } else {
                        $('#scurveDataTable tbody').html('<tr><td colspan="6" class="text-center">No data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("❌ AJAX error", { xhr, status, error });
                }
            });
        }

        function updateTable(data) {
            const tbody = $("#scurveDataTable tbody");
            tbody.empty();
            let prevPlan = 0, prevActual = 0;

            for (let i = 0; i < data.labels.length; i++) {
                const cumPlan = parseFloat(data.plan[i]);
                const cumActual = parseFloat(data.actual[i]);

                const incrementalPlan = (cumPlan - prevPlan).toFixed(2);
                const incrementalActual = (cumActual - prevActual).toFixed(2);
                const deviation = (cumActual - cumPlan).toFixed(2);

                const deviationClass = deviation < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
                const deviationSymbol = deviation < 0 ? '🔻' : '🔼';

                tbody.append(`
                    <tr>
                        <td>${data.labels[i]}</td>
                        <td>${incrementalPlan}%</td>
                        <td>${cumPlan.toFixed(2)}%</td>
                        <td>${incrementalActual}%</td>
                        <td>${cumActual.toFixed(2)}%</td>
                        <td class="${deviationClass}">${deviation}% ${deviationSymbol}</td>
                    </tr>
                `);

                prevPlan = cumPlan;
                prevActual = cumActual;
            }
        }

        // // --- Date Range Picker (custom range)
        // $('#scurve_date_range').daterangepicker({
        //     opens: 'left',
        //     autoUpdateInput: false,
        //     locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' }
        // });

        // $('#scurve_date_range').on('apply.daterangepicker', function(ev, picker) {
        //     const start = picker.startDate.format('YYYY-MM-DD');
        //     const end = picker.endDate.format('YYYY-MM-DD');
        //     $(this).val(start + ' - ' + end);

        //     // filter chart + table
        //     loadChartData(start, end);
        // });

        // $('#scurve_date_range').on('cancel.daterangepicker', function(ev, picker) {
        //     $(this).val('');
        // });

        // --- Dropdown Filter ---
        $('#scurve_time_filter').on('change', function() {
            const viewType = $(this).val();
            let start, end;

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

        // --- Default load: this month ---
        const startDefault = moment().startOf('month').format('YYYY-MM-DD');
        const endDefault = moment().endOf('month').format('YYYY-MM-DD');
        $('#scurve_time_filter').val('this_month');
        loadChartData(startDefault, endDefault);
    });
})();
</script>
