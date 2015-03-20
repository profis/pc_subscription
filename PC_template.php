<?php
/**
 * @var PC_controller_pc_subscription $this
 */

echo $this->site->text;
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#pc_subscribe_form a.pc_submit').click(function(ev){
		ev.preventDefault();
		$('#pc_subscribe_form').submit();
	});
});
</script>
<div class="pl20 pr20 pb20 pt20" id="search_form_body">
	<?php
	if (isset($_POST['pc_subscribe'])) {
		$r = $this->site->Get_data('pc_subscribe');
		if ($r) elang('subscription_subscribed');
		else elang('subscription_not_subscribed');
	}
	?>
	<form id="pc_subscribe_form" method="post">
		<div class="mt10 text_phrase">
			<label class="fl mr10"><?php elang('email'); ?>:</label>
			<input class="input01" type="text" name="pc_subscribe" value="<?php echo v($_POST['pc_subscribe']); ?>" />
			<div class="search_form_submit fr mr25">
				<a class="pc_submit red_button fr a1 mt20" href="#">
					<strong><?php elang('subscription_subscribe'); ?></strong>
					<span>&nbsp;</span>
				</a>
				<div class="clear"><!-- --></div>
			</div>
		</div>
	</form>
	<div class="clear"></div>
</div>