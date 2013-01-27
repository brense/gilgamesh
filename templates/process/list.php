<div id="process-list" class="overflow">
	<div class="spacing">
		<h3>Processen beheren</h3>
		<ul class="buttons">
			<li><a href="do/process/start/">Start alles</a></li>
			<li><a href="do/process/restart/">Herstart alles</a></li>
			<li><a href="do/process/stop/">Stop alles</a></li>
		</ul>
		<ul class="table-controls">
			<li><a href="" class="reload">Herladen</a></li>
		</ul>
	</div>
	<table>
		<thead>
			<tr><th>Script:</th><th>Error file:</th><th>Status:</th><th colspan="3">&nbsp;</th></tr>
		</thead>
		<tbody>
			<?php $n = 0; foreach($this->_vars['processes'] as $process){ ?>
			<tr<?php if($n % 2 != '0'){ echo ' class="even"'; } ?>>
				<td><?php echo $process->script; ?></td>
				<td><?php echo $process->error_file; ?></td>
				<td><?php echo $process->status; ?></td>
				<td style="width:1px;">
					<a href="do/process/start/?id=<?php echo $process->id; ?>">start</a>
				</td>
				<td style="width:1px;">
					<a href="do/process/restart/?id=<?php echo $process->id; ?>">herstart</a>
				</td>
				<td style="width:1px;">
					<a href="do/process/stop/?id=<?php echo $process->id; ?>">stop</a>
				</td>
			</tr>
			<?php $n++; } ?>
		</tbody>
	</table>
</div>