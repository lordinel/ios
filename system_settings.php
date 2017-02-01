<?php
	require_once( "controls/autoload.php" );

	$PAGE_NAME = "System Settings";

	function inlineStyle()
	{
		?>	<style type="text/css">
		section {
			width: 1000px;
		}
	</style>
	<?php
	}
	

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
    $htmlLayout->loadStyle("form");
    $htmlLayout->loadScript("form");
	$htmlLayout->paint( "inlineStyle" );
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'config.png', true );

    
    if ( $htmlLayout->user->getUserID() != 'administrator' && $htmlLayout->user->getUserID() != 'ios_support' ) {
        redirectToHomePage();
    }
	
	
	$database = new Database();

    echo '<fieldset><section><div id="about_content">';

    echo '<div><p><span class="bad"><b>WARNING:</b> You are about to make changes that will affect the entire system. ' .
         'Use this page with care.<br />This page is available only for administrators.</span></p></div>';
    
    echo "<div>System Settings coming soon (Bug 000591)<br /><br /><br /><br /><br /></div>";
    
    echo '</div></section></fieldset>';
	
?>
