<?php
/**
 * @var PC_controller_pc_subscription $this
 */

$newsletterPage = $this->site->Get_data("pc_subscription/page");

$bgcol = '';
$bgimg = '';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=8" />
	<?php echo $this->site->Get_seo_html(); ?>
</head>
<body style="color: #6C6D6D; font-family: arial; font-size: 14px; height: 100%; line-height: 20px; margin: 0;">
	<div style="width:100%; height:auto; min-height:100%; background-color:<?php echo $bgcol; ?>; background-image:url(<?php echo $bgimg; ?>); background-repeat:repeat-x; background-position:left top;">
		<?php
		echo $newsletterPage['text'];
		//print_pre($newsletterPage);
		?>
	</div>
	#unsubscribe:<?php echo $this->core->Get_plugin_variable('subscription_unsubscribe', 'pc_subscription'); ?>#
</body>
</html>