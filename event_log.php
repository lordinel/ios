<?php
	require_once( "controls/autoload.php" );
	$PAGE_NAME = "Event Log";

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'log.png', true );
	
	echo <<<END
		<fieldset><legend>Audit Trace</legend>
			<section>
END;

	$database = new Database();
	
	$sqlQuery = "SELECT * FROM event_log ORDER BY date DESC LIMIT 0,100";
	$resultSet = $database->query( $sqlQuery );
	
	echo <<<END
		<table id="event_log" class="item_input_table report_table">
			<thead>
				<tr>
					<th id="event_date">Date</th>
					<th id="category">Category</th>
					<th id="encoder">Encoder</th>
					<th id="event">Event</th>
				</tr>
			</thead>
			<tbody>
END;

	while ( $log = $database->getResultRow( $resultSet ) ) {
		$logDate = new DateTime( $log['date'] );
		
		echo '<tr class="item_row">' .
			 '<td>' . $logDate->format( 'M j, Y g:i:s A' ) . '</td>' .
			 '<td>';
		if ( $log['category'] == "info" ) {
			echo '<img src="images/info.png" title="Information" />';
		} elseif ( $log['category'] == "warning" ) {
			echo '<img src="images/warning.png" title="Warning" />';
		} else {
			echo '<img src="images/error.png" title="Error" />';
		}
		echo '</td>' .
			 '<td>' . $log['encoder'] . '</td>' .
			 '<td>' . stripslashes( $log['event'] ) . '</td>' .
			 '</tr>';
	}

	echo <<<END
			</tbody>
		</table>
			</section>
		</fieldset>
END;
?>
