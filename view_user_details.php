<?php
	$PAGE_NAME = "User Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	$htmlLayout->loadScript( "user" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'users.png' );

	if ( isset( $_POST['submit_form'] ) ) {
		if ( $_POST['user_query_mode'] == "new" ) {
			//$userID = User::save();
			$database = new Database();
			$userID = $htmlLayout->user->save();
			$htmlLayout->user->showDetailsTasks( $userID, $database );
			User::viewDetails( $userID );
		} else {
			//User::save( 'edit', $_POST['user_id'] );
			$htmlLayout->user->save( 'edit', $_POST['user_id'] );
			$htmlLayout->user->showDetailsTasks( $_POST['user_id'], $database );
			$htmlLayout->user->viewDetails( $_POST['user_id'] );
		}
		unset( $_POST );
	} elseif ( isset( $_GET['id'] ) ) {
		$htmlLayout->user->showDetailsTasks( $_GET['id'] );
		User::viewDetails( $_GET['id'] );
	} else {	// required parameters missing, redirect page
?>
		<script type="text/javascript">
		<!--
			document.location = "index.php";
		// -->
		</script>
<?php
	}

?>
