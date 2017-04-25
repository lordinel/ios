<?php
require_once('controls/autoload.php');

$PAGE_NAME = 'Known Issues';

function inlineStyle() {
	?>
	<style type="text/css">
		section {
			width : 700px;
		}
		
		section li {
			margin-bottom : 10px;
		}
	</style>
	<?php
}

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->paint('inlineStyle');
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('help.png', true);

?>
<fieldset>
	<section>
		<div id="about_content">
			<p>Known Issues as of <?php echo VERSION ?> and Future Plans:</p>
			<ul>
				<li>Deletion of Customer, Agent, Supplier, or User is currently not possible. This will be patched in the next stable release.</li>
			</ul>
			<div><br /><br />For more technical information about known issues in this application, please visit our 
							 <a href="buglist.php" title="View known bugs">Bug Tracking System</a>.<br /><br />
			</div>
		</div>
	</section>
</fieldset>
<?php
?>
