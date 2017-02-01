// customer.js
// Complementary script for Customer class (Customer.php)

var origPersonID = null;


// ---------------------------------------------------------------------------------------------------------------
// class Customer
// ---------------------------------------------------------------------------------------------------------------
$( function() {
	$('#customer_name').bind({
		keyup: function() {
			autoSuggest('Customer::showAutoSuggest','customer_name','autosuggest_customer_name');
		},
		keydown: function() {
			resetCustomerFields();
		},
		blur: function() {
			customerInfo = fillInputFields( "customer", "Customer::autoFill", 'customerName=' + encodeURIComponent( $('#customer_name').val() ) );
		
			if ( customerInfo != null )	{
				if ( fillFields == true ) {
					// credit limit
					var creditLimit = parseFloat( customerInfo["credit_limit"] );
					$("#credit_limit").val( creditLimit.toFixed( 3 ) );
					
					// remaining credits
					var creditRemaining = parseFloat( customerInfo["credit_remaining"] );
					if ( $( "#order_id" ).val() != null ) {
						if ( $( "#order_query_mode" ).val() == "edit" && origPersonID == customerInfo["id"] )
							creditRemaining = creditRemaining + parseFloat( $('#payment_due').val() );
					}
					$( "#remaining_credits" ).val( creditRemaining.toFixed( 3 ) );
					$( "#remaining_credits_field" ).show( 'fast' );
			
					// credit terms
					var selectedIndex;
					switch( customerInfo["credit_terms"] ) {
						case "30 days" : selectedIndex = 0; break;
						case "60 days" : selectedIndex = 1; break;
						case "90 days" : selectedIndex = 2; break;
						case "120 days": selectedIndex = 3; break;
						case "150 days": selectedIndex = 4; break;
					}
					document.getElementById( "credit_terms" ).selectedIndex = selectedIndex;
					
					// branch assignments
					if ( customerInfo['branch_id'] != null ) {
						var branches = customerInfo['branch_id'].split(',');
						for (var i = 0; i < branches.length; i++) {
							$("#branch_" + branches[i]).attr("checked", true);
						}
					}
					
					if ( lockFields == true ) {
						$("#credit_limit").attr("disabled",true);
						$("#credit_terms").attr("disabled",true);
						$("#branch_assignments input:checkbox").attr("disabled",true);
					}
					
					// recheck order with credit remaining
					checkCreditLimit();
				}
			}
		}
	});
	
	
	$('#credit_limit').bind({
		focus: function() {
			var data = new Data();
			data.selectField( $(this), 'float' );
		},
		keyup: function() {
			checkCreditLimit();
		},
		blur: function() {
			var data = new Data();
			data.validateField( $(this), 'float' );
			checkCreditLimit();
		}
	});
});



function resetCustomerFields() {
	if ( enableInputFields( "customer" ) == true ) {
		$("#credit_limit").val( "0.000" );
		$("#credit_limit").attr("disabled", false);
		
		if ($('#customer_query_mode').val() == 'new' || $('#customer_query_mode').val() == 'locked') {
			$( "#remaining_credits_field" ).hide( 'fast', function() {
				$( "#remaining_credits" ).val( "0.000" );
			});
		}
		
		document.getElementById( "credit_terms" ).selectedIndex = 0;
		document.getElementById( "credit_terms" ).disabled = false;
		
		// recheck order with credit remaining
		if ($("#order_id").val() != null) {
			if ( parseFloat( $( "#net_amount" ).val() ) > 0 ) {
				$("#net_amount").css('color', badInputStyle);
			} else {
				$("#net_amount").css('color', goodInputStyle);
			}
		}
		
		$("#branch_assignments input:checkbox").attr("checked",false);
		$("#branch_assignments input:checkbox").attr("disabled",false);
	}
}



function resetCustomerForm()
{
	resetCustomerFields();
	editLocked = false;
}



function checkCreditLimit()
{
	if ( $( "#order_id" ).val() != null )
	{
		if ( $( "#customer_query_mode" ).val() != "new" )
		{
			if ( parseFloat( $( "#net_amount" ).val() ) > parseFloat( $( "#remaining_credits" ).val() ) )
				$( "#net_amount" ).css( 'color', badInputStyle );
			else
				$( "#net_amount" ).css( 'color', goodInputStyle );
		}
		else
		{
			if ( parseFloat( $( "#net_amount" ).val() ) > parseFloat( $( "#credit_limit" ).val() ) )
				$( "#net_amount" ).css( 'color', badInputStyle );
			else
				$( "#net_amount" ).css( 'color', goodInputStyle );
		}
	}
}
