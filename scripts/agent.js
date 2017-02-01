// customer.js
// Complementary script for Customer class (Customer.php)

$( function() {
	$( "#agent_name" ).keyup( function() {
		autoSuggest('Agent::showAutoSuggest','agent_name','autosuggest_agent_name');
	});


	$( "#agent_name" ).keydown( function() {
		resetAgentFields();
	} );


	$( "#agent_name" ).blur( function() {
		agentInfo = fillInputFields( "agent", "Agent::autoFill", 'agentName=' + encodeURIComponent( $('#agent_name').val() ) );
		
		if ( agentInfo != null )
		{
			if ( fillFields == true )
			{
				document.getElementById( "branch" ).value = agentInfo["branch"];
				document.getElementById( "department" ).value = agentInfo["department"];
				document.getElementById( "position" ).value = agentInfo["position"];
			
				if ( lockFields == true )
				{
					document.getElementById( "branch" ).disabled = true;
					document.getElementById( "department" ).disabled = true;
					document.getElementById( "position" ).disabled = true;
				}
			}
		}
	} );
});


function resetAgentFields()
{
	if ( enableInputFields( "agent" ) == true )
	{
		document.getElementById( "branch" ).value = "";
		document.getElementById( "branch" ).disabled = false;
		document.getElementById( "department" ).value = "";
		document.getElementById( "department" ).disabled = false;
		document.getElementById( "position" ).value = "";
		document.getElementById( "position" ).disabled = false;
	}
}



function resetAgentForm()
{
	resetAgentFields();
	editLocked = false;
}
