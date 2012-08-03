<div class="mt15">
	<div class="page_title mt10">Subscribe</div>
	<?php
	if (isset($_POST['pc_subscribe'])) {
		$r = $this->site->Get_data('pc_subscription');
		if ($r) {
			echo 'Subscription successful.';
		}
		else {
			echo 'Error while trying to subscribe.';
		}
	}
	?>
	<form method="post">
		<input style="margin:5px 0;" type="text" name="pc_subscribe" value="<?php echo v($_POST['pc_subscribe']); ?>" /><br />
		<input class="vote_button" type="submit" value="Subscribe">
	</form>
</div>