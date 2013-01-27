<div id="filter-list">
	<ul>
		<?php foreach($this->_vars['filters'] as $filter){ ?>
			<?php if(isset($this->_vars['selected'][(string)$filter]) && $this->_vars['selected'][(string)$filter]['active'] == 1) $selected = ' class="selected"'; else $selected = ''; ?>
			<li<?php echo $selected; ?>>
				<a href="do/models/Filter/toggle/?id=<?php echo $filter; ?>&field=<?php echo $filter->field; ?>&value=<?php echo str_replace('#', '', is_array($filter->value) ? implode(',', $filter->value) : $filter->value); ?>"><?php echo $filter->name; ?>: <?php echo is_array($filter->value)|| $filter->value == 'true' || $filter->value == 'false' || substr($filter->value, 0, 1) == '[' || substr($filter->value, 0, 1) == '(' ? '' : $filter->value; ?> <span>(<?php echo $filter->updates; ?>)</span></a>
			</li>
		<?php } ?>
		<li><a href="" class="manage-filters">+</a></li>
	</ul>
</div>