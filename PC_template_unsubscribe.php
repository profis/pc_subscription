<script type="text/javascript">
$(document).ready(function(){
	$('#pc_unsubscribe_form a.pc_submit').click(function(ev){
		ev.preventDefault();
		$('#pc_unsubscribe_form').submit();
	});
});
</script>
<div class="pl20 pr20 pb20 pt20" id="search_form_body">
	<?php
	$show_form = true;
	if (isset($this->route[2]) || isset($_POST['pc_unsubscribe'])) {
		$r = $this->site->Get_data('pc_unsubscribe');
		if ($r) {
			elang('subscription_unsubscribed');
			$show_form = false;
		}
		else elang('subscription_not_unsubscribed');
	}
	if ($show_form) {
	?>
	<form id="pc_unsubscribe_form" method="post">
		<div class="mt10 text_phrase">
			<label class="fl mr10"><?php elang('subscription_hash'); ?>:</label>
			<input class="input01" type="text" name="pc_unsubscribe" value="<?php echo v($_POST['pc_unsubscribe']); ?>" />
			<div class="search_form_submit fr mr25">
				<a class="pc_submit red_button fr a1 mt20" href="#">
					<strong><?php elang('subscription_unsubscribe'); ?></strong>
					<span>&nbsp;</span>
				</a>
				<div class="clear"><!-- --></div>
			</div>
		</div>
	</form>
	<div class="clear"></div>
	<?php
	}
	?>
</div>