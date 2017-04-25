<?php
require_once('controls/autoload.php');
$PAGE_NAME = 'Event Log';

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('log.png', true);

echo '<fieldset><legend>Audit Trace</legend><section>';

$database  = new Database();
$resultSet = $database->query("SELECT * FROM event_log ORDER BY date DESC LIMIT 0,100");

echo '<table id="event_log" class="item_input_table report_table"><thead><tr>' .
	 '<th id="event_date">Date</th>' .
	 '<th id="category">Category</th>' .
	 '<th id="encoder">Encoder</th>' .
	 '<th id="event">Event</th>' .
	 '</tr></thead><tbody>';

while ($log = $database->getResultRow($resultSet)) {
	$logDate = new DateTime($log['date']);
	
	echo '<tr class="item_row">' .
		 '<td>' . $logDate->format('M j, Y g:i:s A') . '</td>' .
		 '<td>';
	
	if ($log['category'] == 'info') {
		echo '<img src="images/info.png" title="Information" />';
	} elseif ($log['category'] == 'warning') {
		echo '<img src="images/warning.png" title="Warning" />';
	} else {
		echo '<img src="images/error.png" title="Error" />';
	}
	
	echo '</td>' .
		 '<td>' . $log['encoder'] . '</td>' .
		 '<td>' . stripslashes($log['event']) . '</td>' .
		 '</tr>';
}

echo '</tbody></table></section></fieldset>';
?>
