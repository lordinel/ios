<?php


// class definition for user session management
class User extends Layout
{
	const SESSION_NAME = "ios";

	private $userID;
	private $name;
	private $permissions = array();

	private static $encryption = 'md5';



	// constructor, start session
	public function __construct()
	{
		session_name( self::SESSION_NAME );
		session_set_cookie_params( 36000 );
		session_start();

		if ( $this->isLoggedIn() ) {
			$this->userID = $_SESSION['userID'];
			$this->name = $_SESSION['userName'];
		}
	}



	// display login form
	public static function showLoginForm( $action = null )
	{
?>
	<div id="content">
		<div id="login_content">
		<div id="login_heading">
			<img src="images/blocks.png" />
			<div id="welcome_text">
				<h1><?php echo PROG_NAME . " " . VERSION ?></h1>
				<p><?php echo PROG_NAME_LONG ?><br />
				<?php echo CLIENT ?><br />
				&copy; <?php echo YEAR ?><br /></p>
			</div>
		</div>
		<div id="login_form">
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
				<div class="login_parameter">
					<label for="username">Username:</label>
					<input type="text" name="username" id="username" maxlength="100" autofocus="autofocus" required="required" />
				</div>
				<div class="login_parameter">
					<label for="password">Password:</label>
					<input type="password" name="password" id="password" maxlength="100" required="required" />
				</div>
<?php
				self::showButtons( ButtonSet::LOGIN );
?>
			</form>
		</div>
		</div>
<?php
		if ( $action == "logout" )
		{
?>
		<div id="msg">
			You are now logged out.
		</div>
        <script type="text/javascript">
		<!--
			window.setTimeout( 'clearMessage()', 3000 );
			
			function clearMessage()
			{
				$('#msg').fadeOut(1000);
			}
		// -->
        </script>
<?php
		}
		elseif ( $action == "denied" )
		{
?>
		<div id="msg">
			<span class="now">Access Denied:</span> Your username and password does not match.
		</div>
		<div>
			<br /><a href="#" title="Help on login" style="cursor: help">Trouble logging in?</a>
		</div>
<?php
		}
		elseif ( $action == "denied_and_locked" )
		{
?>
		<div id="msg">
			<span class="now">Access Denied:</span> Your username and password does not match.
			<br />Your account is now locked due to multiple failed login attempts.
			<br />Please contact your administrator to unlock your account.
		</div>
		<div>
			<br /><a href="#" title="Help on login" style="cursor: help">Trouble logging in?</a>
		</div>
<?php
		}
		elseif ( $action == "locked" )
		{
			?>
		<div id="msg">
			<span class="now">Access Denied:</span> Your account is currently locked.
			<br />Please contact your administrator to unlock your account.
		</div>
		<div>
			<br /><a href="#" title="Help on login" style="cursor: help">Trouble logging in?</a>
		</div>
			<?php
		}
		elseif ( $action == "invalid" )
		{
			?>
		<div id="msg">
			<span class="now">Access Denied:</span> Your account is no longer valid.
			<br />Please contact your administrator to extend the<br />validity of your account.
		</div>
		<div>
			<br /><a href="#" title="Help on login" style="cursor: help">Trouble logging in?</a>
		</div>
			<?php
		}
		elseif ( $action == "nosession" )
		{
?>
		<div id="msg">
			<span class="now">Access Denied:</span> Please login first.
		</div>
        <script type="text/javascript">
		<!--
			window.setTimeout( 'clearMessage()', 3000 );
			
			function clearMessage()
			{
				$('#msg').fadeOut(1000);
			}
		// -->
        </script>
<?php
		}
		else {
?>
		<div id="msg">
			<img src="images/security.png" alt="security" style="float: left; margin: 5px 0 0 20px">
			By logging in, you agree to the terms and conditions<br />
			of this application and to the corporate<br />
			IT security policies of <?php echo CLIENT ?>.
		</div>
		<script type="text/javascript">
		<!--
			window.setTimeout( 'clearMessage()', 10000 );

			function clearMessage()
			{
				$('#msg').fadeOut(1000);
			}
		// -->
		</script>
<?php
		}

	echo "\t</div>\n";
	}



	// login user
	public function login( $userID, $password, Database &$database )
	{
		// check user in database
		self::$database = $database;
		$sqlQuery = "SELECT * FROM user WHERE id = '" . Filter::input( $userID ) . "'";
		$resultSet = self::$database->query( $sqlQuery );


		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			// user is found in database, get user details
			$user = self::$database->getResultRow( $resultSet );

			// check if user is locked
			if ( $user['locked'] ) {
				EventLog::addEntry( self::$database, 'warning', 'user', 'select', 'denied',
				                    'A user from Terminal: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) .
				                    ' (IP Address: ' . $_SERVER['REMOTE_ADDR'] . ') attempts to log in as ' .
				                    '<a href="view_user_details.php?id=' . $userID . '">' .
				                    capitalizeWords( Filter::output( $user['name'] ) ) . ' (' . $userID . ')</a>' .
				                    ' but <span class="event_log_action bad">denied</span> because the account is locked',
				                    'system' );
				return 'locked';
			}

			// perform case-sensitive comparison for password
			if ( strcmp( $user['password'], self::encryptPassword( $password ) ) == 0 )
			{
				// case matched, check if user is still valid
				$currentDate = date( "Y-m-d" );
				if ( $user['validity_start'] > $currentDate || $currentDate > $user['validity_end'] ) {
					return 'invalid';
				}

				// successful login
				$this->userID = $user['id'];
				$this->name = $user['name'];

				// setup session
				$_SESSION['userID'] = $this->userID;
				$_SESSION['userName'] = capitalizeWords( Filter::output( $this->name ) );

				// update last successful login
				$sqlQuery = "UPDATE user SET " .
							"last_successful_login_date=NOW(), " .
							"last_successful_login_terminal='" . gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) . "', " .
							"last_successful_login_ip_address='" . $_SERVER['REMOTE_ADDR'] . "', " .
							"failed_login_attempts=0 " .      // reset failed login attempts
							"WHERE id='" . $this->userID . "'";
				self::$database->query( $sqlQuery );
				
				// log event
				EventLog::addEntry( self::$database, 'info', 'user', 'select', 'login',
									'<a href="view_user_details.php?id=' . $this->userID . '">' .
									capitalizeWords( Filter::output( $this->name ) ) . ' (' . $this->userID .
									')</a> logged in from Terminal: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) .
									' (IP Address: ' . $_SERVER['REMOTE_ADDR'] . ')',
									$_SESSION['userID'] );

				// check if this is user's first time to login
				if ( $user['first_login'] ){
					return 'first_login';
				}

				// check if password had already expired
				if ( $user['password_expiry_date'] != null && $user['password_expiry_date'] <= $currentDate ) {
					return 'password_expired';
				}

				return 'login_ok';
			} else {
				// failed login

				$user['failed_login_attempts']++;

				// check if user will be locked due to multiple failed attempts
				// do not lock administrators
				if ( $user['failed_login_attempts'] >= Registry::get( 'security.max_failed_login_to_lock' ) &&
					 $user['role'] != 'administrator' ) {
					$lockUser = true;
				} else {
					$lockUser = false;
				}

				// update last failed login
				$sqlQuery = "UPDATE user SET " .
							"last_failed_login_date=NOW(), " .
							"last_failed_login_terminal='" . gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) . "', " .
							"last_failed_login_ip_address='" . $_SERVER['REMOTE_ADDR'] . "', " .
							"failed_login_attempts=failed_login_attempts+1 " .
							( $lockUser ? ", locked=TRUE " : "" ) .
							"WHERE id='" . $userID . "'";
				self::$database->query( $sqlQuery );


				// log event
				if ( $user['failed_login_attempts'] == 1 ) {
					$failedLoginAttemptCount = '1st';
				} elseif ( $user['failed_login_attempts'] == 2 ) {
					$failedLoginAttemptCount = '2nd';
				} elseif ( $user['failed_login_attempts'] == 3 ) {
					$failedLoginAttemptCount = '3rd';
				} else {
					$failedLoginAttemptCount = $user['failed_login_attempts'] . 'th';
				}

				EventLog::addEntry( self::$database, 'warning', 'user', 'select', 'denied',
									'A user from Terminal: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) .
									' (IP Address: ' . $_SERVER['REMOTE_ADDR'] . ') attempts to log in as ' .
									'<a href="view_user_details.php?id=' . $userID . '">' .
									capitalizeWords( Filter::output( $user['name'] ) ) . ' (' . $userID . ')</a>' .
									' but <span class="event_log_action bad">denied</span> due to wrong password (' .
									$failedLoginAttemptCount . ' attempt)',
									'system' );


				if ( $lockUser ) {
					sleep(1);   // delay 1 sec before creating a log about locking of user
					EventLog::addEntry( self::$database, 'warning', 'user', 'select', 'locked',
										'<a href="view_user_details.php?id=' . $userID . '">' .
										capitalizeWords( Filter::output( $user['name'] ) ) . ' (' . $userID . ')</a> ' .
										'is <span class="event_log_action bad">locked</span> due to multiple failed login attempts',
										'system' );
					return 'denied_and_locked';
				} else {
					return 'denied';
				}
			}
		} else {
			// user is not found in the database
			
			// log event
			EventLog::addEntry( self::$database, 'warning', 'user', 'select', 'denied',
								'A user from Terminal: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) .
								' (IP Address: ' . $_SERVER['REMOTE_ADDR'] . ')' .
								' attempts to log in as ' . $userID .
								' but <span class="event_log_action bad">denied</span> because username does not exists',
								'system' );
			return 'denied';
		}
	}



	// check if user is logged in
	public function isLoggedIn()
	{
		if ( isset( $_SESSION ) ) {						// session cookies are active
			if ( isset( $_SESSION['userName'] ) ) {		// session cookie username is set
				return true;
			} else {
				return false;
			}
		} else {
			$this->logout();
			return false;
		}
	}



	// get user name
	public function getUserID()
	{
		return $this->userID;
	}


	public static function getUserIDStatic()
	{
		return $_SESSION['userID'];
	}


	// get user name
	public function getUserName()
	{
		return $this->name;
	}


	public function getUserNameStatic()
	{
		return $_SESSION['userName'];
	}



	// tasks for account settings
	public function showDetailsTasks( $userToDisplay = null, Database &$database = null )
	{
		if ( $database == null ) {
			$database = new Database();
		}
		
		$canManageUsers = self::checkPermission('manage_users', $database);
		
		echo '<div id="tasks"><ul>';

		if ( $userToDisplay == null || $userToDisplay == $this->getUserID() ) {
			// user is viewing his own account
			echo '<li id="task_edit_account"><a href="account_settings.php?mode=edit"><img src="images/task_buttons/edit.png" />Edit My Account</a></li>';
			echo '<li id="task_change_password"><a href="javascript:void(0)" onclick="showChangePasswordDialog(\'' .
				 $this->getUserID() . '\')"><img src="images/task_buttons/key.png" />Change Password...</a></li>';
		} else {
			// user is viewing other account
			if ( $this->getUserID() == "administrator" || $canManageUsers ) {
				// only administrators can edit account of others
				echo '<li id="task_edit_user"><a href="edit_user.php?id=' . $userToDisplay . '"><img src="images/task_buttons/edit.png" />Edit User</a></li>';
				echo '<li id="task_reset_password"><a href="javascript:void(0)" onclick="showResetPasswordDialog(\'' .
					 $userToDisplay . '\')"><img src="images/task_buttons/key.png" />Reset Password...</a></li>';

				$userIsLocked = self::isLocked( $userToDisplay, $database );

				echo '<li id="task_unlock_user"' .
					 ( $userIsLocked ? '' : ' style="display: none"' ) .
					 '><a href="javascript:void(0)" onclick="showLockUnlockUserDialog(\'' .
					 $userToDisplay . '\',\'unlock\')"><img src="images/task_buttons/unlock.png" />Unlock User...</a></li>';
				echo '<li id="task_lock_user"' .
					 ( $userIsLocked ? ' style="display: none"' : '' ) .
					 '><a href="javascript:void(0)" onclick="showLockUnlockUserDialog(\'' .
					 $userToDisplay . '\',\'lock\')"><img src="images/task_buttons/lock.png" />Lock User...</a></li>';
			}
		}

		if ( $this->getUserID() == "administrator" || $canManageUsers  ) {
			if ( $userToDisplay == null || $userToDisplay == 'administrator' || $canManageUsers ) {
				// display Manage Users task if user is administrator
				echo '<li id="task_manage_users"><a href="account_manager.php"><img src="images/task_buttons/manage_users.png" />Manage Users</a></li>';
			} else {
				echo '<li id="task_manage_users"><a href="account_manager.php"><img src="images/task_buttons/back_to_list.png" />Back to List</a></li>';
			}
		}

		echo '</ul></div></div>';
	}



	// display user details, non static
	public function view()
	{
		self::$database = new Database();
		$this->viewDetails( $this->userID, self::$database );
	}


	// display user details, static
	public static function viewDetails( $userID, &$database = null ) {
		$currentDate = date( "Y-m-d" );

		// check if user is viewing his own ID
		$isViewingOwnID = ( $userID == self::getUserIDStatic() );


		// get main user info
		if ( self::$database == null ) {
			self::$database = new Database();
		}
		
		$branchList = self::getBranches();
		
		$resultSet = self::$database->query(
			"SELECT * FROM user WHERE id = '" . Filter::input( $userID ) . "'"
		);

		if ( self::$database->getResultCount( $resultSet ) > 0 ) {		// user ID is found in database
			$user = self::$database->getResultRow( $resultSet );

			$name		= capitalizeWords( Filter::output( $user['name'] ) );
			$address	= capitalizeWords( Filter::output( $user['address'] ) );
			$telephone	= Filter::output( $user['telephone'] );
			$mobile		= Filter::output( $user['mobile'] );
			$fax		= Filter::output( $user['fax'] );
			$email		= Filter::output( $user['email'] );
			$branch		= explode(',', $user['branch_id'] );
			$department	= capitalizeWords( Filter::output( $user['department'] ) );
			$position	= capitalizeWords( Filter::output( $user['position'] ) );

			if ( $isViewingOwnID ) {
				echo '<fieldset><legend>Personal Info</legend>';
			} else {
				HtmlLayout::setPageTitleStatic('User » '.htmlspecialchars_decode($name));
				echo '<fieldset><legend>User Info</legend>';
			}


			// show basic user info
			echo '<section class="main_record_label">' .
				 '<div>' . $name . '</div>' .
				 '</section>';

			echo '<section><div>' .
				 '<span class="record_label">Username:</span>' .
				 '<span class="record_data">' . $userID . '</span>' .
				 '</div><div>' .
				 '<span class="record_label">Password:</span>' .
				 '<span class="record_data">';
			if ( $isViewingOwnID ) {
				echo '<a href="javascript:void(0)" onclick="showChangePasswordDialog(\'' . $userID . '\')">[ Change password... ]</a>';
			} else {
				echo '<a href="javascript:void(0)" onclick="showResetPasswordDialog(\'' . $userID . '\')">[ Reset password... ]</a>';
			}
			echo '</span></div></section>';


			// show password status
			echo '<section><div>' .
				 '<span class="record_label">Password Expires:</span>' .
				 '<span class="record_data"><span id="password_expiry_date">';
			if ( $user['password_expiry_date'] != null ) {
				if ( $user['password_expiry_date'] >= $currentDate ) {
					echo dateFormatOutput( Filter::output( $user['password_expiry_date'] ),  "F j, Y, D", "Y-m-d" );
				} else {
					// password is already expired
					echo '<span class="bad">' .
						 dateFormatOutput( Filter::output( $user['password_expiry_date'] ),  "F j, Y, D", "Y-m-d" ) .
						 '</span>';
				}
			} else {
				echo 'No expiration';
			}
			
			echo '</span></span></div><div>' .
				 '<span class="record_label">Last Password Change:</span>' .
				 '<span class="record_data"><span id="last_password_change">';
			if ( $user['last_password_change_date'] != null ) {
				echo dateFormatOutput( Filter::output( $user['last_password_change_date'] ), DATETIME_OUTPUT_FORMAT ) .
					 ' on Terminal: ' . Filter::output( $user['last_password_change_terminal'] ) .
					 ' (IP Address: ' . Filter::output( $user['last_password_change_ip_address'] ) . ')';
			} else {
				echo 'No recorded password change';
			}
			
			echo '</span></span></div><div>' .
				 '<span class="record_label">Account Status:</span>' .
				 '<span class="record_data">';
			if ( $user['locked'] ) {
				// user is locked
				echo '<span id="account_status" class="bad">Locked</span>';
			} else {
				echo '<span id="account_status" class="good">Not Locked</span>';
			}
			echo '</span></div></section>';


			// show last login info
			echo '<section><div>' .
				 '<span class="record_label">Last Successful Login:</span>' .
				 '<span class="record_data">';
			if ( $user['last_successful_login_date'] != null ) {
				 echo dateFormatOutput( Filter::output( $user['last_successful_login_date'] ), DATETIME_OUTPUT_FORMAT ) .
					 ' on Terminal: ' . Filter::output( $user['last_successful_login_terminal'] ) .
					 ' (IP Address: ' . Filter::output( $user['last_successful_login_ip_address'] ) . ')';
			} else {
				echo '<span>No recorded successful login</span>';
			}
			
			echo '</span></div>' .
				 '<div>' .
				 '<span class="record_label">Last Failed Login:</span>' .
				 '<span class="record_data">';
			if ( $user['last_failed_login_date'] != null ) {
				 echo dateFormatOutput( Filter::output( $user['last_failed_login_date'] ), DATETIME_OUTPUT_FORMAT ) .
					 ' on Terminal: ' . Filter::output( $user['last_failed_login_terminal'] ) .
					 ' (IP Address: ' . Filter::output( $user['last_failed_login_ip_address'] ) . ')';
			} else {
				 echo '<span class="good">No recorded failed login</span>';
			}
			echo '</span></div></section>';


			// show address and contact info
			if ( $address != null ) {
				echo '<section><div>' .
					 '<span class="record_label">Address:</span>' .
					 '<span class="record_data">' . $address . '</span>' .
					 '</div></section>';
			}

			if ( $telephone != null || $mobile != null ||
				 $fax != null       || $email != null ) {
				echo '<section>';

				if ( $telephone != null ) {
					echo '<div>' .
						'<span class="record_label">Telephone:</span>' .
						'<span class="record_data">' . $telephone . '</span>' .
						'</div>';
				}

				if ( $mobile != null ) {
					echo '<div>' .
						'<span class="record_label">Mobile:</span>' .
						'<span class="record_data">' . $mobile . '</span>' .
						'</div>';
				}

				if ( $fax != null ) {
					echo '<div>' .
						'<span class="record_label">Fax:</span>' .
						'<span class="record_data">' . $fax . '</span>' .
						'</div>';
				}

				if ( $email != null ) {
					echo '<div>' .
						'<span class="record_label">E-mail:</span>' .
						'<span class="record_data">' . $email . '</span>' .
						'</div>';
				}

				echo '</section>';
			}


			// show work info
			echo '<section><div>' .
				 '<span class="record_label">Branch Assignments:</span>' .
				 '<span class="record_data">';
			if ( $branch[0] != null ) {
				$i = 1;
				foreach ($branch as $branchKey) {
					if ($i > 1) {
						echo ", ";
					}
					echo capitalizeWords(Filter::output($branchList[$branchKey]));
					$i++;
				}
			} else {
				echo "-- None --";
			}
			echo '</span></div></section>';

			
			if ( $department != null || $position != null ) {
				echo '<section>';
				
				if ($department != null) {
					echo '<div>' .
						'<span class="record_label">Department:</span>' .
						'<span class="record_data">' . $department . '</span>' .
						'</div>';
				}

				if ($position != null) {
					echo '<div>' .
						'<span class="record_label">Position:</span>' .
						'<span class="record_data">' . $position . '</span>' .
						'</div>';
				}

				echo '</section>';
			}


			// show validity period
			echo '<section><div>' .
				 '<span class="record_label">Valid From:</span>' .
				 '<span class="record_data">';
			if ( Filter::output( $user['validity_start'] ) <= $currentDate ) {
				echo dateFormatOutput( Filter::output( $user['validity_start'] ), "F j, Y, D", "Y-m-d" );
			} else {
				// validity start date is not yet reached
				echo '<span class="bad">' .
					 dateFormatOutput( Filter::output( $user['validity_start'] ), "F j, Y, D", "Y-m-d" ) .
					 '</span>';
			}
			echo '</span></div><div>' .
				 '<span class="record_label">Valid Until:</span>' .
				 '<span class="record_data">';
			if ( $currentDate <= Filter::output( $user['validity_end'] ) ) {
				echo dateFormatOutput( Filter::output( $user['validity_end'] ), "F j, Y, D", "Y-m-d" );
			} else {
				// validity end date has already lapsed
				echo '<span class="bad">' .
					dateFormatOutput( Filter::output( $user['validity_end'] ), "F j, Y, D", "Y-m-d" ) .
					'</span>';
			}
			echo '</span></div></section>';


			// show role and permissions
			echo '<section><div>' .
				 '<span class="record_label">Role:</span>' .
				 '<span class="record_data">';
			if ( $user['role'] != null ) {
				echo Filter::output( $user['role'] );
			} else {
				echo 'limited';
			}
			echo '</span></div></section>';


			echo '</fieldset>';

			// display permissions form
			self::showPermissionsForm( $userID, $isViewingOwnID, 'view', $database );

		} else {
			// user ID is not exsiting, redirect to home page
?>
		<script type="text/javascript">
			<!--
			document.location = "index.php";
			// -->
		</script>
<?php
		}
	}

	
	
	// show settings form
	public static function showSettingsForm( $mode = "edit", $userID = null )
	{
		if ( self::$database == null ) {
			self::$database = new Database();
		}
		
		$branchList = self::getBranches();
		
		$isViewingOwnID = false;

		if ( $mode == "edit" ) {
			// get user details
			if ( $userID == null ) {
				$userID = self::getUserIDStatic();
				$isViewingOwnID = true;
			}
			$resultSet = self::$database->query(
				"SELECT * " .
				"FROM user " .
				"WHERE id = '" . Filter::input( $userID ) . "'"
			);
			if ( self::$database->getResultCount( $resultSet ) == 1 ) {
				$userInfo = self::$database->getResultRow( $resultSet );
			} else {
				redirectToHomePage();
			}
		} else {
			// new user
			$userInfo = null;
		}
		

		// display legend
		if ( $isViewingOwnID ) {
			echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" autocomplete="off">';
			echo '<fieldset><legend>Personal Settings</legend>';
		} else {
			echo '<form name="new_user_form" id="new_user_form" action="view_user_details.php';
			if ( $userID != null ) {
				echo '?id=' . $userID;
			}
			echo '" method="post" autocomplete="off">';
			echo '<fieldset><legend>User Settings</legend>';
		}


		// username
		echo '<section><div>' .
			 '<label for="username" class="required_label">Username:</label>' .
			 '<input type="text" name="username" id="username" list="autosuggest_username" class="form_input_text" maxlength="100" required="required" ' .
			 ( $userInfo == null ? 'autofocus="autofocus" ' :
			 					   'value="' . Filter::reinput( $userInfo['id'] ) . '" disabled="disabled" ' ) .
			 '/>';

		// display existing usernames
		echo '<datalist id="autosuggest_username">';
		if ( $userInfo == null ) {
			$resultSet = self::$database->query(
				"SELECT id " .
				"FROM user " .
				"ORDER BY id ASC"
			);
			while ( $userList = self::$database->getResultRow( $resultSet ) ) {
				echo '<option value="' . Filter::output( $userList['id'] ) . '"></option>';
			}
		}
		echo '</datalist>';

		// hidden input fields
		echo '<input type="hidden" name="user_id" id="user_id" value="' . ( $userInfo != null ? Filter::reinput( $userInfo['id'] ) : '' ) . '" />' .
			 '<input type="hidden" name="user_query_mode" id="user_query_mode" value="' . $mode . '" />';

		// display hint if new user
		if ( $userInfo == null ) {
			echo '<br /><span class="form_hint"><span class="inline_msg"></span>' .
				 'Username must be:<br />' .
				 '- 8 to 20 characters<br />' .
				 '- first character is a letter or underscore (_)<br />' .
				 '- second character onwards is a letter, number, or underscore (_)<br />' .
				 '- no space characters<br />' .
				 '- not the same as existing usernames</span>' .
				 '<input type="hidden" name="valid_username_flag" id="valid_username_flag" value="false" />';
		}

		echo '</div><div>' .
			 '<label for="name" class="required_label">Name:</label>' .
			 '<input type="text" name="name" id="name" class="form_input_text" maxlength="100" required="required" ' .
			 ( $userInfo != null ? 'autofocus="autofocus" value="' . capitalizeWords( Filter::reinput( $userInfo['name'] ) ) . '" ' : '' ) .
			 '/>' .
			 '</div></section>';


		if ( $userInfo == null ) {
				echo '<section><div>' .
					 '<label for="password" class="required_label">Initial Password:</label>' .
					 '<input type="password" name="initial_password" id="initial_password" class="form_input_text" maxlength="255" required="required" />' .
					 '</div>' .
					 '<div>' .
					 '<label for="retype_password" class="required_label">Retype Password:</label>' .
					 '<input type="password" name="retype_password" id="retype_password" class="form_input_text" maxlength="255" required="required" />' .
					 '<br /><span class="form_hint"><span class="inline_msg"></span>' .
					 'The initial password must be:<br />' .
					 '- not the same as his/her username<br />' .
					 '- not &quotpassword&quot<br />' .
					 '- not the same as user\'s old password<br />' .
					 '- at least 8 characters in length<br />' .
					 '- with at least 1 uppercase letter<br />' .
					 '- with at least 1 lowercase letter<br />' .
					 '- with at least 1 number<br />' .
					 '- with at least 1 special character</span>' .
					 '<input type="hidden" name="valid_password_flag" id="valid_password_flag" value="false" />';
					 '</div></section>';
		}
?>

		<section>
			<div>
				<label for="address">Address:</label>
				<textarea name="address" id="address" rows="2"><?php echo ( $userInfo != null ) ? capitalizeWords( Filter::reinput( $userInfo['address'] ) ) :
						"" ?></textarea>
			</div>
		</section>

		<section>
			<div>
				<label for="telephone">Telephone:</label>
				<input type="tel" name="telephone" id="telephone" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . Filter::reinput( $userInfo['telephone'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="mobile">Mobile:</label>
				<input type="tel" name="mobile" id="mobile" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . Filter::reinput( $userInfo['mobile'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="mobile">Fax:</label>
				<input type="tel" name="fax" id="fax" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . Filter::reinput( $userInfo['fax'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="email">E-mail:</label>
				<input type="email" name="email" id="email" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . Filter::reinput( $userInfo['email'] ) . "\"" : "" ?> />
			</div>
		</section>

		<section>
			<div id="branch_assignments">
				<label for="branch_assignments">Branch Assignments:</label>
				<span id="branch_list">
				<?php
				if ( $branchList != null ) {
					if ( $userInfo != null ) {
						$tempBranches = explode(',', $userInfo['branch_id']);
						foreach ($tempBranches as $tempBranchKey) {
							$userBranches[$tempBranchKey] = true;
						}
					} else {
						$userBranches = null;
					}
					$i = 1;
					foreach ( $branchList as $branchKey => $branchName ) {
						echo '<span><input type="checkbox" name="branch_assignments[]" id="branch_' . $branchKey . 
				 			 '" value="' . $branchKey . '"';
						if ( isset($userBranches[$branchKey]) || $userInfo['role'] == "administrator" ) {
							echo ' checked="checked"';
						}
						if ( $userInfo['role'] == "administrator" || $isViewingOwnID ) {
							echo ' disabled="disabled"';
						}
						echo ' />';
						echo '<label for="branch_' .  $branchKey . '" class="branch_label">' . $branchName . '</label></span>';
						if ( $i % 4 == 0 ) {
							echo '<br />';
						}
						$i++;
					}
				} else {
					echo '<option value="0">-- No available branches --</option>';
				}
				?>
				</span>
			</div>
			<div>
				<label></label>
				<span class="form_hint">
					<span class="inline_msg">User can view only the orders and customers from the assigned branches.</span>
				</span>
			</div>
		</section>
		<section>
			<div>
				<label for="department">Department:</label>
				<input type="text" name="department" id="department" class="form_input_text" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . capitalizeWords( Filter::reinput( $userInfo['department'] ) ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="position">Position:</label>
				<input type="text" name="position" id="position" class="form_input_text" maxlength="100"<?php echo ( $userInfo != null ) ? " value=\"" . capitalizeWords( Filter::reinput( $userInfo['position'] ) ) . "\"" : "" ?> />
			</div>
		</section>

		<section>
			<div>
				<label for="validity_start" class="required_label">Valid From:</label>
				<input type="text" name="validity_start" id="validity_start" size="30" maxlength="30" required="required"<?php
					if ( $isViewingOwnID || self::getUserIDStatic() != "administrator" ) {
						echo ' disabled="disabled"';
					} else {
						echo ' class="datepicker" onclick="$(this).select()"';
					}

					if ( $mode == 'edit' ) {
						echo ' value="' .
							 dateFormatOutput( Filter::reinput( $userInfo['validity_start'] ), "F j, Y, D", "Y-m-d" ) .
							 '"';
					}

				?> />
			</div>
			<div>
				<label for="validity_end" class="required_label">Valid Until:</label>
				<input type="text" name="validity_end" id="validity_end" size="30" maxlength="30" required="required"<?php
					if ( $isViewingOwnID || self::getUserIDStatic() != "administrator" ) {
						echo ' disabled="disabled"';
					} else {
						echo ' class="datepicker" onclick="$(this).select()"';
					}

					if ( $mode == 'edit' ) {
						echo ' value="' .
							 dateFormatOutput( Filter::reinput( $userInfo['validity_end'] ), "F j, Y, D", "Y-m-d" ) .
							 '"';
					}

				?> />
			</div>
		</section>
		</fieldset>
<?php
		// display permissions form
		self::showPermissionsForm( $userID, $isViewingOwnID, $mode );

		// display submit/reset/cancel buttons
		self::showButtons( ButtonSet::SUBMIT_RESET_CANCEL );

		echo '</form>';
	}



	// show permissions form
	private static function showPermissionsForm( $userID, $isViewingOwnID, $mode )
	{
		if ( $mode != 'view' && $mode != 'new' && $mode != 'edit' ) {
			return;
		}

		if ( $isViewingOwnID ) {
			$mode = 'view';
		}


		$MAX_COLUMN = 3;


		// construct permission table
		$permissionTable = array();
		$permissionTable[0] = array(
			'value' => 'orders_and_customers',
			'label' => 'Orders and Customers' );
		$permissionTable[1] = array(
			'value' => 'purchases_and_suppliers',
			'label' => 'Purchases and Suppliers' );
		$permissionTable[2] = array(
			'value' => 'inventory',
			'label' => 'Inventory' );
		$permissionTable[3] = array(
			'value' => 'agents',
			'label' => 'Agents' );
		$permissionTable[4] = array(
			'value' => 'daily_sales_report',
			'label' => 'Daily Sales Report' );
		$permissionTable[5] = array(
			'value' => 'periodic_sales_report',
			'label' => 'Periodic Sales Report' );
		$permissionTable[6] = array(
			'value' => 'projected_collections_report',
			'label' => 'Projected Collections Report' );
		$permissionTable[7] = array(
			'value' => 'inventory_report',
			'label' => 'Inventory Report' );
		$permissionTable[8] = array(
			'value' => 'revenue_and_expense_report',
			'label' => 'Revenue and Expense Report' );
		$permissionTable[9] = array(
			'value' => 'profit_calculator',
			'label' => 'Profit Calculator' );
		$permissionTable[10] = array(
			'value' => 'manage_users',
			'label' => 'Manage Users' );
		$permissionTable[11] = array(
			'value' => 'event_log',
			'label' => 'Event Log' );
		$permissionTable[12] = array(
			'value' => 'consistency_check',
			'label' => 'Consistency Check' );

		// check current permission if editing existing user
		if ( $userID != null && $mode != 'new' ) {
			$resultSet = self::$database->query(
				"SELECT permission FROM user_permission " .
				"WHERE user_id='" . Filter::input( $userID ) . "'"
			);
			$currentPermissions = array();
			while ( $savedPermission = self::$database->getResultRow( $resultSet ) ) {
				$currentPermissions[$savedPermission['permission']] = true;
			}
		}

		echo '<fieldset><legend>Permissions</legend><section>';
		echo '<table id="permissions_table"><tbody>';

		$i = 0;
		foreach ( $permissionTable as $permission ) {
			if ( $i == 0 ) {
				echo '<tr>';
			}
			echo '<td><input type="checkbox" name="permission[]" ' .
				 'id="' . $permission['value'] . '" value="' . $permission['value'] . '"';
			if ( $mode == 'view' ) {
				echo ' disabled="disabled"';
			}
			if ( isset( $currentPermissions[$permission['value']] ) || $userID == 'administrator' ) {
				echo ' checked="checked"';
			}
			echo ' /><label for="' . $permission['value'] . '">' . $permission['label'] . '</label>' . '</td>';
			$i++;
			if ( $i == $MAX_COLUMN ) {
				echo '</tr>';
				$i = 0;
			}
		}

		echo '</tbody></table>';
		echo '</section></fieldset>';
	}


	// check permissions
	public static function checkPermission( $permissionName, Database &$database = null )
	{
		if ( $database == null ) {
			$database = new Database();
		}

		$userID = self::getUserIDStatic();
		if ( $userID == 'administrator' ) {
			// grant automatic permission for administrator
			return true;
		}

		$sqlQuery = "SELECT * FROM user_permission " .
					"WHERE user_id='" . $userID . "' AND (";

		// if multiple permissions, check all
		$i = 0;
		if ( is_array( $permissionName ) ) {
			foreach( $permissionName as $permission ) {
				if ( $i > 0 ) {
					$sqlQuery = $sqlQuery . " OR ";
				}
				$sqlQuery = $sqlQuery . "permission='" . $permission . "'";
				$i++;
			}
		} else {
			$sqlQuery = $sqlQuery . "permission='" . $permissionName . "'";
		}
		$sqlQuery = $sqlQuery . ")";

		$resultSet = $database->query( $sqlQuery );
		if ( $database->getResultCount( $resultSet ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}



	// check if username exist
	public static function isUserIDExist()
	{
		if ( ! isset( $_POST['userID'] ) ) {
			return;
		}

		$database = new Database();

		$resultSet = $database->query(
			"SELECT * " .
			"FROM user " .
			"WHERE id='" . Filter::input( $_POST['userID'] ) . "'"
		);

		if ( $database->getResultCount( $resultSet ) >= 1 ) {
			echo '{ "user_exist" : true }';
		} else {
			echo '{ "user_exist" : false }';
		}
	}
	
	
	// save user details
	public static function save() {
		if ( self::$database == null ) {
			self::$database = new Database();
		}

		$mode = $_POST['user_query_mode'];
		if ( $mode != 'new' && $mode != 'edit' ) {
			return;
		}

		
		// name
		$name = "'" . Filter::input( $_POST['name'] ) . "'";
		
		// address
		if ( empty( $_POST['address'] ) ) {
			$address = "NULL";
		} else {
			$address = "'" . Filter::input( $_POST['address'] ) . "'";
		}
		
		// telephone
		if ( empty( $_POST['telephone'] ) ) {
			$telephone = "NULL";
		} else {
			$telephone = "'" . Filter::input( $_POST['telephone'] ) . "'";
		}
		
		// mobile
		if ( empty( $_POST['mobile'] ) ) {
			$mobile = "NULL";
		} else {
			$mobile = "'" . Filter::input( $_POST['mobile'] ) . "'";
		}

		// fax
		if ( empty( $_POST['fax'] ) ) {
			$fax = "NULL";
		} else {
			$fax = "'" . Filter::input( $_POST['fax'] ) . "'";
		}
		
		// email
		if ( empty( $_POST['email'] ) ) {
			$email = "NULL";
		} else {
			$email = "'" . Filter::input( $_POST['email'] ) . "'";
		}

		// branch
		if ( empty( $_POST['branch_assignments'] ) ) {
			$branch = "NULL";
		} else {
			$branch = "'";
			for ( $i = 0; $i < sizeof( $_POST['branch_assignments'] ); $i++ ) {
				if ( $i > 0 ) {
					$branch = $branch . ",";
				}
				$branch = $branch . $_POST['branch_assignments'][$i];
			}
			$branch = $branch . "'";
		}
		
		// department
		if ( empty( $_POST['department'] ) ) {
			$department = "NULL";
		} else {
			$department = "'" . Filter::input( $_POST['department'] ) . "'";
		}
		
		// position
		if ( empty( $_POST['position'] ) ) {
			$position = "NULL";
		} else {
			$position = "'" . Filter::input( $_POST['position'] ) . "'";
		}



		if ( $mode == 'edit' ) {
			$sqlQuery = "UPDATE user SET " .
						"name=" . $name . "," .
						"address=" . $address . "," .
						"telephone=" . $telephone . "," .
						"mobile=" . $mobile . "," .
						"fax=" . $fax . "," .
						"email=" . $email . "," .
						"branch_id=" . $branch . "," .
						"department=" . $department . "," .
						"position=" . $position;

			// validity start
			if ( isset( $_POST['validity_start'] ) ) {
				$sqlQuery = $sqlQuery . ",validity_start='" . dateFormatInput( Filter::input( $_POST['validity_start'] ), "Y-m-d", "F j, Y, l" ) . "'";
			}

			// validity end
			if ( isset( $_POST['validity_start'] ) ) {
				$sqlQuery = $sqlQuery . ",validity_end='" . dateFormatInput( Filter::input( $_POST['validity_end'] ), "Y-m-d", "F j, Y, l" ) . "'";
			}

			$sqlQuery = $sqlQuery . " WHERE id='" . Filter::input( $_POST['user_id'] ) . "'";

			if ( self::$database->query( $sqlQuery ) ) {
				if ( $_POST['user_id'] == self::getUserIDStatic() ) {	// viewing own user
					$_SESSION['userName'] = capitalizeWords( htmlentities( $_POST['name'] ) );
				}
				if ( isset( $_POST['permission'] ) ) {
					if ( self::savePermissions( $_POST['user_id'], $_POST['permission'], $mode ) ) {
						return true;
					} else {
						return false;
					}
				} else {
					if ( $_POST['user_id'] != self::getUserIDStatic() ) {	// not viewing own user
						self::$database->query(
							"DELETE FROM user_permission " .
								"WHERE user_id='" . Filter::input( $_POST['user_id'] ) . "'"
						);
					}
					return true;
				}
			} else {
				return false;
			}
		} else {
			$username = Filter::input( $_POST['username'] );

			$sqlQuery = "INSERT INTO user " .
						"(id,password,name,address,telephone,mobile,fax,email,branch_id,department,position,validity_start,validity_end,first_login) " .
						"VALUES (" .
						"'" . $username . "'," .
						"'" . self::encryptPassword( $_POST['initial_password'] ) . "'," .
						$name . "," .
						$address . "," .
						$telephone . "," .
						$mobile . "," .
						$fax . "," .
						$email . "," .
						$branch . "," .
						$department . "," .
						$position . "," .
						"'" . dateFormatInput( Filter::input( $_POST['validity_start'] ), "Y-m-d", "F j, Y, l" ) . "'," .
						"'" . dateFormatInput( Filter::input( $_POST['validity_end'] ), "Y-m-d", "F j, Y, l" ) . "'," .
						"1)";

			if ( self::$database->query( $sqlQuery ) ) {
				if ( self::savePermissions( $username, $_POST['permission'], $mode ) ) {
					return $username;
				} else {
					return null;
				}
			} else {
				return null;
			}
		}
	}



	// save permissions
	private static function savePermissions( $userID, $permissionSet, $mode ) {
		if ( self::$database == null ) {
			self::$database = new Database();
		}
		
		if ( $mode == 'edit' ) {
			self::$database->query(
				"DELETE FROM user_permission " .
				"WHERE user_id='" . Filter::input( $userID ) . "'"
			);
		}

		$permissionSetSize = sizeof( $permissionSet );
		if ( $permissionSetSize > 0 ) {
			$sqlQuery = "INSERT INTO user_permission (user_id, permission) VALUES ";

			$i = 0;
			foreach( $permissionSet as $permission ) {
				$sqlQuery = $sqlQuery . "('" . $userID . "','" . $permission . "')";
				$i++;
				if ( $i < $permissionSetSize ) {
					$sqlQuery = $sqlQuery . ",";
				}
			}

			if ( self::$database->query( $sqlQuery ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			// no permission selected
			return true;
		}
	}



	// change password
	public static function changePassword()
	{
		// check required parameters
		if ( !isset( $_POST['userID'] ) || !isset( $_POST['newPassword'] ) ) {
			return;
		}


		self::$database = new Database();


		// check if old password is correct
		if ( isset( $_POST['oldPassword'] ) ) {
			// get old password
			$resultSet = self::$database->query(
				"SELECT password " .
				"FROM user " .
				"WHERE id='" . Filter::input( $_POST['userID'] ) . "'"
			);
			$user = self::$database->getResultRow( $resultSet );

			// perform case-sensitive comparison for old password
			if ( strcmp( $user['password'], self::encryptPassword( $_POST['oldPassword'] ) ) != 0 ) {
				// old password is incorrect
				echo '{ "isSuccess" : false }';
				return;
			}
		}


		// proceed on changing password
		self::$database->query(
			"UPDATE user SET " .
			"password='" . self::encryptPassword( $_POST['newPassword'] ) . "', " .
			( isset( $_POST['oldPassword'] ) ? "first_login=FALSE, " : "first_login=TRUE, " ) .
			"last_password_change_date=NOW(), " .
			"last_password_change_terminal='" . gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) . "', " .
			"last_password_change_ip_address='" . $_SERVER['REMOTE_ADDR'] . "', " .
			"password_expiry_date=NOW()+INTERVAL 6 MONTH " .
			"WHERE id='" . Filter::input( $_POST['userID'] ) . "'"
		);


		// re-read entered data
		$resultSet = self::$database->query(
			"SELECT last_password_change_date, " .
			"last_password_change_terminal, " .
			"last_password_change_ip_address, " .
			"password_expiry_date " .
			"FROM user " .
			"WHERE id='" . Filter::input( $_POST['userID'] ) . "' " .
			"AND password='" . self::encryptPassword( $_POST['newPassword'] ) . "'"
		);
		$user = self::$database->getResultRow( $resultSet );

		$user['last_password_change_date'] = dateFormatOutput( $user['last_password_change_date'] );
		$user['password_expiry_date'] = dateFormatOutput( $user['password_expiry_date'],  "F j, Y, D", "Y-m-d" );
		$user['is_success'] = true;		// flag to denote successful password change

		echo json_encode( $user );
	}



	// encrypt password
	private static function encryptPassword( $password )
	{
		switch ( self::$encryption ) {
			case 'md5':
				return md5( $password );
			default:
				return $password;
		}
	}



	// check if user is locked
	public static function isLocked( $userID, Database &$database = null ) {
		if ( $database == null ) {
			$database = new Database();
		}

		$resultSet = $database->query(
						"SELECT locked " .
						"FROM user " .
						"WHERE id='" . Filter::input( $userID ) . "'"
					 );
		$user = $database->getResultRow( $resultSet );
		return $user['locked'];
	}


	// unlock user
	public static function unlockUser()
	{
		// check required parameters
		if ( !isset( $_POST['userID'] ) ) {
			return;
		}

		self::$database = new Database();

		$isSuccess = self::$database->query(
						"UPDATE user SET " .
						"locked=FALSE " .
						"WHERE id='" . Filter::input( $_POST['userID'] ) . "'"
					 );

		if ( $isSuccess ) {
			echo '{ "isSuccess" : ' . $isSuccess . ' }';
		}
	}


	// lock user
	public static function lockUser()
	{
		// check required parameters
		if ( !isset( $_POST['userID'] ) ) {
			return;
		}

		self::$database = new Database();

		$isSuccess = self::$database->query(
				"UPDATE user SET " .
				"locked=TRUE " .
				"WHERE id='" . Filter::input( $_POST['userID'] ) . "'"
		);

		if ( $isSuccess ) {
			echo '{ "isSuccess" : ' . $isSuccess . ' }';
		}
	}


	// tasks for user list
	public static function showListTasks()
	{
		echo '<div id="tasks"><ul>' .
			 '<li id="task_add_user"><a href="add_user.php" title="Add User"><img src="images/task_buttons/add.png" />Add User</a></li>' .
			 '</ul></div></div>';
	}


	// display list of users
	public static function showList()
	{
		$currentUser = self::getUserIDStatic();

		// get parameters
		if ( !isset( $_POST['sortColumn'] ) ) {
			$sortColumn = "id";
		} else {
			$sortColumn = $_POST['sortColumn'];
		}

		if ( !isset( $_POST['sortMethod'] ) ) {
			$sortMethod = "ASC";
		} else {
			$sortMethod = $_POST['sortMethod'];
		}

		if ( !isset( $_POST['page'] ) || !isset( $_POST['itemsPerPage'] ) ) {
			$page = 1;
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$page = $_POST['page'];
			$itemsPerPage = $_POST['itemsPerPage'];
		}

		$offset = ( $page * $itemsPerPage ) - $itemsPerPage;


		// count results prior to main query
		self::$database = new Database;
		$resultSet = self::$database->query(
			"SELECT COUNT(*) AS count " .
			"FROM user"
		);
		$resultCount = self::$database->getResultRow( $resultSet );
		$resultCount = $resultCount['count'];


		// construct main query
		$resultSet = self::$database->query(
			"SELECT * " .
			"FROM user " .
			"ORDER BY " . $sortColumn . " " . $sortMethod . " " .
			"LIMIT " . $offset . "," . $itemsPerPage
		);

		if ( self::$database->getResultCount( $resultSet ) == 0 ) {
			echo "<div>No users found.</div>";
			return;
		}


		// construct table headers
		$columns = array(
			'id' => 'Username',
			'name' => 'Name',
			'role' => 'Role',
			'validity_start' => 'Valid From',
			'validity_end' => 'Valid Until',
			'last_successful_login_date' => 'Last Successful Login',
			'last_failed_login_date' => 'Last Failed Login',
			'password_expiry_date' => 'Password Expiry Date',
			'locked' => 'Locked'
		);

		self::showListHeader( $columns, 'user_list_section', 'User::showList', null, $sortColumn, $sortMethod );


		// display content
		while ( $user = self::$database->getResultRow( $resultSet ) ) {
			echo '<tr>';

			// username
			echo '<td>';
			if ( $currentUser == $user['id'] ) {
				echo '<a href="account_settings.php" title="View user details">';
			} else {
				echo '<a href="view_user_details.php?id=' . $user['id'] . '" title="View user details">';
			}
			echo Filter::output( $user['id'] ) . '</a></td>';

			// name
			echo '<td><span class="long_text_clip">' .
				 capitalizeWords( Filter::output( $user['name'] ) ) .
				 '</span></td>';

			// role
			echo '<td>' .
				 Filter::output( $user['role'] ) .
				 '</td>';

			// valid until
			$currentDate = date( "Y-m-d" );

			if ( $user['validity_start'] != null ) {
				if ( $user['validity_start'] <= $currentDate ) {
					echo '<td>' .
						dateFormatOutput( $user['validity_start'], "M j, Y", "Y-m-d" ) .
						'</td>';
				} else {
					echo '<td><span class="bad">' .
						dateFormatOutput( $user['validity_start'], "M j, Y", "Y-m-d" ) .
						'</span></td>';
				}
			} else {
				echo '<td></td>';
			}

			if ( $user['validity_end'] != null ) {
				if ( $user['validity_end'] >= $currentDate ) {
					echo '<td>' .
						 dateFormatOutput( $user['validity_end'], "M j, Y", "Y-m-d" ) .
						 '</td>';
				} else {
					echo '<td><span class="bad">' .
						dateFormatOutput( $user['validity_end'], "M j, Y", "Y-m-d" ) .
						 '</span></td>';
				}
			} else {
				echo '<td></td>';
			}

			// last successful login
			if ( $user['last_successful_login_date'] != null ) {
				echo '<td>' .
					 dateFormatOutput( $user['last_successful_login_date'], "M j, Y g:i A" ) .
					 '</td>';
			} else {
				echo '<td></td>';
			}

			// last failed login
			if ( $user['last_failed_login_date'] != null ) {
				echo '<td>' .
					 dateFormatOutput( $user['last_failed_login_date'], "M j, Y g:i A" ) .
					 '</td>';
			} else {
				echo '<td></td>';
			}

			// password expiry date
			if ( $user['password_expiry_date'] != null ) {
				if ( $user['password_expiry_date'] >= $currentDate ) {
					echo '<td>' .
						 dateFormatOutput( $user['password_expiry_date'], "M j, Y", "Y-m-d" ) .
						 '</td>';
				} else {
					echo '<td><span class="bad">' .
						 dateFormatOutput( $user['password_expiry_date'], "M j, Y", "Y-m-d" ) .
						 '</span></td>';
				}
			} else {
				echo '<td></td>';
			}

			// locked
			if ( $user['locked'] ) {
				echo '<td><span class="bad">Locked</span></td>';
			} else {
				echo '<td></td>';
			}

			echo '</tr>';
		}

		echo '</tbody></table>';

		echo '<div class="pagination_bottom">';
		self::showPagination( $page, $itemsPerPage, $resultCount, 'user_list_section', 'User::showList', null, $sortColumn, $sortMethod );
		echo '</div>';
	}


	// get branch details
	private static function getBranches() {
		$resultSet = self::$database->query( "SELECT id, name FROM branch" );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			while ( $branchInfo = self::$database->getResultRow( $resultSet ) ) {
				$branch[$branchInfo['id']] = $branchInfo['name']; 
			}
		} else {
			$branch = null;
		}
		return $branch;
	}


	// check if user has permission to view customer based on branch ID
	public static function isBranchVisible( $branchIDStr, Database &$database = null ) {
		$userID = self::getUserIDStatic();
		if ( $userID == 'administrator' ) {
			// grant automatic permission for administrator
			return true;
		}
		
		$userBranchIDs = self::getUserBranchIDs( $database );
		if ( $userBranchIDs[0] != null ) {
			foreach ( $userBranchIDs as $tempBranchKey ) {
				$userBranches[$tempBranchKey] = true;
			}
			$customerBranches = explode(',', $branchIDStr);
			foreach ( $customerBranches as $customerBranchKey ) {
				if ( $userBranches[$customerBranchKey] == true ) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}


	// get branch ID array of user
	public static function getUserBranchIDs( Database &$database = null ) {
		if ( $database == null ) {
			$database = new Database();
		}
		
		$resultSet = $database->query( "SELECT branch_id FROM user WHERE id='".self::getUserIDStatic()."'" );
		if ( $database->getResultCount( $resultSet ) > 0 ) {
			$userInfo = $database->getResultRow( $resultSet );
			$userBranchIDs = explode(',', $userInfo['branch_id']);
			return $userBranchIDs;
		} else {
			return null;
		}
	}


	// format SQL WHERE clause for limiting visible customers by branch ID
	public static function getQueryForBranch( Database &$database = null ) {
		if ($database == null) {
			$database = new Database();
		}
		
		$userID = self::getUserIDStatic();
		if ( $userID == 'administrator' ) {
			// grant automatic permission for administrator
			return "TRUE";
		}
		
		$userBranchIDs = self::getUserBranchIDs($database);
		$sqlQuery = "(";
		for ($i = 0; $i < sizeof($userBranchIDs); $i++) {
			if ($i > 0) {
				$sqlQuery = $sqlQuery . " OR ";
			}
			$sqlQuery = $sqlQuery . "FIND_IN_SET('" . $userBranchIDs[$i] . "', customer.branch_id)";
		}
		$sqlQuery = $sqlQuery . ")";
		return $sqlQuery;
	} 


	// logout user
	public function logout()
	{
		//debug_print_backtrace();
		
		$userID = self::getUserID();
		$userName = self::getUserName();
		
		// log event
		EventLog::addEntry( self::$database, 'info', 'user', 'select', 'logout',
							'<a href="view_user_details.php?id=' . $userID . '">' .
							capitalizeWords( Filter::output( $userName ) ) . ' (' . $userID .
							')</a> logged out from Terminal: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) .
							' (IP Address: ' . $_SERVER['REMOTE_ADDR'] . ')',
							$userID );
		
		
		// unset all session variables
		$_SESSION = array();

		// delete session cookie
		if ( ini_get("session.use_cookies") )
		{
			$params = session_get_cookie_params();
			setcookie( session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"] );
		}

		// Finally, destroy the session.
		session_destroy();
	}
}

?>
