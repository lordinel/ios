<?php
	$PAGE_NAME = "Bug Tracker";
	
	$stableBuild = array( 'version' => '4.10',
						  'label'	=> '4.10' );

	$builds = array( '4.9'    => '4.9',
					 '4.8'    => '4.8',
					 '4.7'    => '4.7',
					 '4.6'    => '4.6',
					 '4.5'    => '4.5',
					 '4.0b1'  => '4.0',
					 '3.0b1'  => '3.0 Beta',
					 '2.0b2'  => '2.0 Beta 2',
					 '2.0b1'  => '2.0 Beta 1',
					 '1.12'	  => '1.12',
					 '1.11'	  => '1.11',
					 '1.10'	  => '1.10',
					 '1.9'	  => '1.9',
					 '1.8'	  => '1.8',
					 '1.7'	  => '1.7',
					 '1.6'	  => '1.6',
					 '1.5'	  => '1.5',
					 '1.4'	  => '1.4',
					 '1.3'	  => '1.3',
					 '1.2'	  => '1.2',
					 '1.1'    => '1.1',
					 '1.0'    => '1.0',
					 '1.0b4'  => '1.0 Beta 4',
					 '1.0b3'  => '1.0 Beta 3',
					 '1.0b2'  => '1.0 Beta 2',
					 '1.0b1'  => '1.0 Beta 1',
					 '1.0a10' => '1.0 Alpha 10',
					 '1.0a9'  => '1.0 Alpha 9',
					 '1.0a8'  => '1.0 Alpha 8',
					 '1.0a7'  => '1.0 Alpha 7',
					 '1.0a6'  => '1.0 Alpha 6',
					 '1.0a5'  => '1.0 Alpha 5',
					 '1.0a4'  => '1.0 Alpha 4',
					 '1.0a3'  => '1.0 Alpha 3',
					 '1.0a2'  => '1.0 Alpha 2',
					 '1.0a1'  => '1.0 Alpha 1' );

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'bugs.png', true );

	$database = new Database();

	?>  <fieldset><section><div id="about_content">
			<h3>Build Information</h3>
            <section>
                <div>
                    <?php echo PROG_NAME_LONG ?><br />
                    Version <?php echo VERSION ?><br />
                    Build <?php echo BUILD ?><br />
                    Codename <?php echo CODENAME ?>
                </div>
			</section>

			<h3>Blockers for <?php echo $stableBuild['label'] ?></h3>
			<ul>
<?php
				$resultSet = $database->query( "SELECT * FROM bugs WHERE blocking = '" . $stableBuild['version'] . "' AND ( status != 'dropped' OR status IS NULL ) ORDER BY number DESC" );
				if ( $database->getResultCount( $resultSet ) > 0 )
				{
					while( $bug = $database->getResultRow( $resultSet ) )
					{
						echo "\t\t\t\t<li";
						if ( $bug['status'] == NULL )
							echo " style=\"color: red\"";
						elseif ( $bug['status'] == "ready to land" )
							echo " style=\"color: blue\"";
						echo ">Bug " . $bug['number'] . " - " . Filter::output( $bug['description'] );
						if ( $bug['status'] != NULL )
							echo " (<i>" . $bug['status'] . "</i>)";
						echo "</li>\n";
					}
				}
				else
					echo "\t\t\t\tnone\n";
?>			</ul>

			<br />

			<h3>Non-Blocker Bugs</h3>
			<ul>
<?php
				$resultSet = $database->query( "SELECT * FROM bugs WHERE blocking = 'nb' AND ( status != 'dropped' OR status IS NULL ) ORDER BY number DESC" );
				if ( $database->getResultCount( $resultSet ) > 0 )
				{
					while( $bug = $database->getResultRow( $resultSet ) )
					{
						echo "\t\t\t\t<li";
						if ( $bug['status'] == NULL )
							echo " style=\"color: red\"";
						elseif ( $bug['status'] == "ready to land" )
							echo " style=\"color: blue\"";
						echo ">Bug " . $bug['number'] . " - " . Filter::output( $bug['description'] );
						if ( $bug['status'] != NULL )
							echo " (<i>" . $bug['status'] . "</i>)";
						echo "</li>\n";
					}
				}
				else
					echo "\t\t\t\tnone\n";
?>			</ul>

			<br />


			<h3>Fixed Bugs</h3>
			<ul>
<?php			$buildVersions = array_keys( $builds );
				
				foreach ( $buildVersions as $version )
				{
?>				<li><?php echo $builds[$version] ?>
					<ul>
<?php
					$resultSet = $database->query( "SELECT * FROM bugs WHERE blocking = '" . $version . "' AND status = 'resolved' ORDER BY number DESC" );
					if ( $database->getResultCount( $resultSet ) > 0 )
					{
						while( $bug = $database->getResultRow( $resultSet ) )
						{
							echo "\t\t\t\t<li>Bug " . $bug['number'] . " - " . Filter::output( $bug['description'] );
							if ( $bug['status'] != NULL )
								echo " (<i>" . $bug['status'] . "</i>)";
							echo "</li>\n";
						}
					}
					else
						echo "\t\t\t\tnone\n";
?>					</ul>
					<br />
				</li>
<?php			}
?>			</ul>

			<br />


			<h3>Dropped Bugs</h3>
			<ul>
<?php
				$resultSet = $database->query( "SELECT * FROM bugs WHERE status = 'dropped' ORDER BY number DESC" );
				if ( $database->getResultCount( $resultSet ) > 0 )
				{
					while( $bug = $database->getResultRow( $resultSet ) )
					{
						echo "\t\t\t\t<li><strike>Bug " . $bug['number'] . "</strike> - " . Filter::output( $bug['description'] );
						if ( $bug['status'] != NULL )
							echo " (<i>" . $bug['status'] . "</i>)";
						echo "</li>\n";
					}
				}
				else
					echo "\t\t\t\tnone\n";
?>			</ul>

			<br />

		</div>
	</section></fieldset>
<?php
?>
