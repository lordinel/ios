<?php
	require_once( "controls/autoload.php" );

	$PAGE_NAME = "Feedback";

	function inlineStyle()
	{
?>	<style type="text/css">
		section {
			width: 650px;
		}
	</style>
<?php
	}

	function inlineScript()
	{
?>	<script type="text/javascript">
	<!--
		window.onload = function()
		{
			document.location='mailto:lordinel@gmail.com';
		}
	// -->
	</script>
<?php
	}

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->paint( "inlineStyle", null );
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'feedback.png', true );

?>	<fieldset><section><div id="about_content">
	<section>
	<h3>Thank You For Your Feedback!</h3>

	<div>As part of our vision to give our clients only the best software - robust, efficient, flexible, and bug-free - our team highly values your response.
		Your feedback will be our basis on improving our applications.<br /><br /></div>

	<div>As you use this application, please take note of the outputs and designs. We will greatly appreciate any comments, suggestions, reactions, or bug reports.<br /><br /></div>

	<div>If you are reporting an error, it will be very helpful if you can create a screenshot of the error, the description, and possible the steps in order to
		reproduce the error.<br /><br /></div>

	<div>To continue sending feedback, click on this <a href="mailto:lordinel@gmail.com" title="Launch e-mail client">link</a> to launch your installed e-mail client (e.g. Microsoft Office Outlook, Windows Live Mail, Mozilla Thunderbird, etc.).</div>
	</section>

	<section>
	<div><?php echo COMPANY ?></div>
	</section>

	</div></section></fieldset>
<?php
?>
