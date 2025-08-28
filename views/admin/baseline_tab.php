<div class="row">
  <div class="col-md-12">
    <h4>S-Curve Baseline Input</h4>
    <form method="post" action="<?php echo admin_url('scurve_report/save_baseline/'.$project_id); ?>">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Period Label</th>
            <th>Date</th>
            <th>Plan Cumulative (%)</th>
            <th>Actual Cumulative (%)</th>
          </tr>
        </thead>
        <tbody id="baselineRows">
          <?php if (!empty($baseline)) {
            foreach ($baseline as $row) { ?>
              <tr>
                <td><input type="text" name="rows[][period_label]" class="form-control" value="<?= $row['period_label'] ?>"></td>
                <td><input type="date" name="rows[][date_point]" class="form-control" value="<?= $row['date_point'] ?>"></td>
                <td><input type="number" step="0.01" name="rows[][plan_cumulative]" class="form-control" value="<?= $row['plan_cumulative'] ?>"></td>
                <td><input type="number" step="0.01" name="rows[][actual_cumulative]" class="form-control" value="<?= $row['actual_cumulative'] ?>"></td>
              </tr>
          <?php } } else { ?>
              <tr>
                <td><input type="text" name="rows[][period_label]" class="form-control" placeholder="e.g. W1"></td>
                <td><input type="date" name="rows[][date_point]" class="form-control"></td>
                <td><input type="number" step="0.01" name="rows[][plan_cumulative]" class="form-control"></td>
                <td><input type="number" step="0.01" name="rows[][actual_cumulative]" class="form-control"></td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
      <button type="button" id="addRow" class="btn btn-info">Add Row</button>
      <button type="submit" class="btn btn-primary">Save Baseline</button>
    </form>
  </div>
</div>

<script>
  document.getElementById('addRow').addEventListener('click', function() {
    let row = `
      <tr>
        <td><input type="text" name="rows[][period_label]" class="form-control" placeholder="e.g. W2"></td>
        <td><input type="date" name="rows[][date_point]" class="form-control"></td>
        <td><input type="number" step="0.01" name="rows[][plan_cumulative]" class="form-control"></td>
        <td><input type="number" step="0.01" name="rows[][actual_cumulative]" class="form-control"></td>
      </tr>`;
    document.getElementById('baselineRows').insertAdjacentHTML('beforeend', row);
  });
</script>
