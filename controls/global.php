<?php
	// global functions


	// redirect to specified page
	function redirect( $page )
	{
		$currentURL = $_SERVER['PHP_SELF'];
		$currentHost = pathinfo( $currentURL );
		$newURL = $currentHost['dirname'] . '/' . $page;
		header( 'Location: ' . $newURL ) ;
	}


	// reload same page
	function reloadPage( $newParam = null, $overwriteOldParam = false )
	{
		$currentURL = $_SERVER['PHP_SELF'];
		$currentHost = pathinfo( $currentURL );
		$newURL = $currentHost['dirname'];

		header( 'Location: ' . $newURL );
	}
	
	
	// redirect user to homepage
	function redirectToHomePage( $urlParam = null )
	{
		if ( $urlParam != null ) {
			$urlParamStr = '?' . $urlParam;
		} else {
			$urlParamStr = '';
		}
		
		
		?>
			<script type="text/javascript">
            <!--
                document.location = "index.php<?php echo $urlParamStr ?>";
            //-->
            </script>
        <?php
	}


	// add comma separator to numbers, plus optional symbols
	function numberFormat( $number, $type = "int", $decimal = 3, $decimalPoint = '.', $thousandSeparator = ',', $noTags = false )
	{
		switch ( $type )
		{
			case "int": {
				return number_format( (int) $number, 0, $decimalPoint, $thousandSeparator );
				break;
			}
			case "float": {
				if ( $noTags == true ) {
					return number_format( (double) $number, $decimal, $decimalPoint, $thousandSeparator );
				} else {
					$str    = number_format((double)$number, $decimal, $decimalPoint, $thousandSeparator);
					$needle = strrchr($str, ".");
					return str_replace($needle, '<span class="dec">'.$needle.'</span>', $str);
				}
				break;
			}
			case "currency": {
				if ( $noTags == true ) {
					return CURRENCY." ".number_format((double)$number, $decimal, $decimalPoint, $thousandSeparator);
				} else {
					$str    = CURRENCY." ".number_format((double)$number, $decimal, $decimalPoint, $thousandSeparator);
					$needle = strrchr($str, ".");
					return str_replace($needle, '<span class="dec">'.$needle.'</span>', $str);
				}
				break;
			}
			case "percent": {
				if ( $noTags == true ) {
					return number_format((double)$number, $decimal, $decimalPoint, $thousandSeparator)." %";
				} else {
					$str    = number_format((double)$number, $decimal, $decimalPoint, $thousandSeparator)." %";
					$needle = strrchr($str, ".");
					return str_replace($needle, '<span class="dec">'.$needle.'</span>', $str);
				}
				break;
			}
			default: {
				return number_format( (int) $number, 0, '.', ',' );
			}
		}
	}


	// format date for saving to database
	function dateFormatInput( $date, $saveFormat = DATETIME_SAVE_FORMAT, $inputFormat = DATETIME_INPUT_FORMAT )
	{
		$dateObj = DateTime::createFromFormat( $inputFormat, $date );
		return $dateObj->format( $saveFormat );
	}


	// format date for output to screen
	function dateFormatOutput( $date, $outputFormat = DATETIME_OUTPUT_FORMAT, $saveFormat = DATETIME_SAVE_FORMAT )
	{
		$dateObj = DateTime::createFromFormat( $saveFormat, $date );
		if ( $dateObj ) {
			return $dateObj->format( $outputFormat );
		} else {
			echo 'Error: Cannot convert date<br />' .
				 'Input date: ' . $date . '<br />' .
				 'Input date format: ' . $saveFormat . '<br />' .
				 'Output date format: ' . $outputFormat . '<br />';
			die();
		}
	}


	// cut address display
	function cutAddress( $address )
	{
		$maxStrLength = 50;		// maximum number of characters

		$address = str_replace( "\r\n", " ", $address );

		/*if ( $position > $maxStrLength || $position < 0 )
			$position = $maxStrLength;*/

		if ( strlen( $address ) < $maxStrLength )
		{
			$position = strlen( $address );
			$showEllipsis = false;
		}
		else
		{
			$position = $maxStrLength;
			$showEllipsis = true;
		}

		$newAddress = Filter::output( substr( $address, 0, $position ) );
		if ( $showEllipsis == true )
			$newAddress = $newAddress . "...";

		return $newAddress;
	}


	/* set formatting of proper names such as customer name, supplier name, contact person, agent name, address, and inventory */
	function capitalizeWords( $string )
	{
		$string = ucwords( strtolower( $string ) );

		$string = preg_replace_callback( '/(?<=[0-9~\/\\\@;().,!?_-])./', create_function( '$matches', 'return strtoupper( $matches[0] );' ), $string );

		return $string;
	}
?>
