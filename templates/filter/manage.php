<?php
$template = new \models\Template('filter/add', array('event' => $this->_vars['event']));
echo $template->render();
?>
<div id="manage-filters">
	<h3>Filters beheren</h3>
	<table>
		<thead>
			<tr><th>Naam:</th><th>Type:</th><th>Waarde:</th><th>&nbsp;</th></tr>
		</thead>
		<tbody>
		<?php $n = 0; foreach($this->_vars['filters'] as $filter){ ?>
			<tr<?php if($n % 2 != 0){ echo ' class="even"'; } ?>>
				<td><?php echo $filter->name; ?></td>
				<td><?php echo $filter->type; ?></td>
				<td><?php echo is_array($filter->value)|| $filter->value == 'true' || $filter->value == 'false' || substr($filter->value, 0, 1) == '[' || substr($filter->value, 0, 1) == '(' ? '' : $filter->value; ?></td>
				<td><a href="do/models/Filter/remove/?event=<?php echo $this->_vars['event']; ?>&field=<?php echo $filter->field; ?>&value=<?php echo is_array($filter->value) ? implode(',', $filter->value) : rawurlencode($filter->value); ?>" class="delete-filter">Verwijderen</a></td>
			</tr>
		<?php $n++; } ?>
		</tbody>
	</table>
</div>