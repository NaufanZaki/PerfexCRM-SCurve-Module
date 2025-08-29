<div class="row">
    <div class="col-md-12">
        <h4>S-Curve Baseline Input</h4>
        <form method="post" action="<?php echo admin_url('scurve_report/save_baseline/' . $project->id); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
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
                                <td><input type="text" name="rows[<?= $row['id'] ?>][period_label]" class="form-control"
                                        value="<?= html_escape($row['period_label']) ?>" required></td>
                                <td><input type="date" name="rows[<?= $row['id'] ?>][date_point]" class="form-control"
                                        value="<?= html_escape($row['date_point']) ?>" required></td>
                                <td><input type="number" step="0.01" name="rows[<?= $row['id'] ?>][plan_cumulative]"
                                        class="form-control" value="<?= html_escape($row['plan_cumulative']) ?>"></td>
                                <td><input type="number" step="0.01" name="rows[<?= $row['id'] ?>][actual_cumulative]"
                                        class="form-control" value="<?= html_escape($row['actual_cumulative']) ?>"></td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td><input type="text" name="rows[new_1][period_label]" class="form-control"
                                    placeholder="e.g. W1" required></td>
                            <td><input type="date" name="rows[new_1][date_point]" class="form-control" required></td>
                            <td><input type="number" step="0.01" name="rows[new_1][plan_cumulative]" class="form-control">
                            </td>
                            <td><input type="number" step="0.01" name="rows[new_1][actual_cumulative]" class="form-control">
                            </td>
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
  // A counter to ensure every new row has a unique ID
  let newRowCounter = 1;

  document.getElementById('addRow').addEventListener('click', function() {
    newRowCounter++; // Increment the counter
    
    // Use the counter to create a unique name for the new row's inputs
    let newRowName = `rows[new_${newRowCounter}]`;

    let row = `
      <tr>
        <td><input type="text" name="${newRowName}[period_label]" class="form-control" placeholder="e.g. W2" required></td>
        <td><input type="date" name="${newRowName}[date_point]" class="form-control" required></td>
        <td><input type="number" step="0.01" name="${newRowName}[plan_cumulative]" class="form-control"></td>
        <td><input type="number" step="0.01" name="${newRowName}[actual_cumulative]" class="form-control"></td>
      </tr>`;
      
    document.getElementById('baselineRows').insertAdjacentHTML('beforeend', row);
  });
</script>