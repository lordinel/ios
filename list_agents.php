<?php
	$PAGE_NAME = "Agents";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'agents.png' );
	Agent::showListTasks();

?>	<fieldset><legend>Agent List</legend>
		<section id="agent_list_section">
		</section>
	</fieldset>

	<script type="text/javascript">
		ajax( null, 'agent_list_section', 'innerHTML', 'Agent::showList', null );
	</script>
<?php
?>
