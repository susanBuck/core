<th>Logs Directory</th>
<?php if (is_dir(APPPATH) AND is_dir(APPPATH.'logs') AND is_writable(APPPATH.'logs')): ?>
	<td class="pass"><?php echo APPPATH.'logs/' ?></td>
<?php else: $failed = TRUE ?>
	<td class="fail">The <code><?php echo APPPATH.'logs/' ?></code> directory is not writable.</td>
<?php endif ?>