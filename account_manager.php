<?php
	require_once( "controls/autoload.php" );
	$PAGE_NAME = "Manage Users";

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'users.png' );
	User::showListTasks();

?>	<fieldset><legend>User List</legend>
		<section id="user_list_section">
		</section>
	</fieldset>

	<script type="text/javascript">
		ajax( null, 'user_list_section', 'innerHTML', 'User::showList', null );
	</script>
<?php
?>
