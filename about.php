<?php
require_once("controls/autoload.php");

$PAGE_NAME = 'About ' . PROG_NAME;

function inlineStyle() {
	?>
	<style type="text/css">
		section {
			width : 1000px;
		}
	</style>
<?php
}

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->paint('inlineStyle');
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('about.png', true);

?>
<fieldset>
	<section>
		<div id="about_content">
			<section>
				<div>
					<?php echo PROG_NAME_LONG ?><br />
					Version <?php echo VERSION ?><br />
					Build <?php echo BUILD ?><br />
					Codename <?php echo CODENAME ?><br />
					<br />
				</div>
				<div>
					Licensed to <?php echo CLIENT ?>&trade; Philippines
				</div>
			</section>
			
			<section>
				<div>
					----------------------------------------------------------------------------<br />
					A. <a href="#release">Release Notes</a><br />
					B. <a href="#sysreq">System Requirements</a><br />
					C. <a href="#license">End-User License Agreement (EULA)</a><br />
					----------------------------------------------------------------------------
				</div>
			</section>
			
			<a id="release"></a>
			<fieldset>
				<legend>RELEASE NOTES</legend>
				<section>
					<h3>About This Release</h3>
					
					<div><?php echo PROG_NAME . ' ' . VERSION ?> is based on the Worldwide Web platform utilizing the latest Web technologies available as of 
							this writing such as HTML version 5, CSS version 3, PHP version 5.6.25, MySQL RDBMS version 5.7.14, and Apache 2.4.23. Please visit 
							our <a href="issues.php">known issues</a> and <a href="buglist.php">bug list</a> for more information and support notes about this 
							release. For any comments or suggestions, or to report errors, please send us your <a href="feedback.php">feedback</a>.<br /><br />
					</div>
					<h3>What's New in this Version</h3>
					<ul>
						<li>Permissions Based on Branch Assignments</li>
						<li>Several bug and stability fixes (see complete <a href="buglist.php">bug list</a>)</li>
					</ul>
					<h3>Changelist from Previous Versions</h3>
					<ul>
						<li>User Authentication</li>
						<li>Homepage</li>
						
						<li>Add/Edit/List/Cancel Orders</li>
						<li>Mark Orders as Delivered/Picked-up</li>
						<li>Enter Payment for Orders</li>
						<li>Mark Orders as Cleared</li>
						
						<li>Add/Edit/List Customers</li>
						<li>Add/Edit/List Agents</li>
						
						<li>Purchase Supplies</li>
						<li>Edit/List/Cancel Purchases</li>
						<li>Mark Purchases as Delivered/Picked-up</li>
						<li>Enter Payment for Purchases</li>
						<li>Mark Purchases as Cleared</li>
						
						<li>Add/Edit/List Suppliers</li>
						
						<li>List Inventory</li>
						<li>Add/Edit/Delete Brands</li>
						<li>Add/Edit/Delete Models</li>
						<li>Update Purchase and Selling Prices</li>
						<li>Duplicate Inventory Deletion</li>
						
						<li>Daily Sales Report</li>
						<li>Periodic Sales Report</li>
						<li>Inventory Report</li>
						<li>Revenue and Expense Report</li>
						<li>Profit Calculator</li>
						
						<li>Event Log</li>
						<li>Consistency Check</li>
						
						<li>Search</li>
						<li>Account Settings</li>
						
						<li>Selective Item Delivery</li>
						<li>Return Items</li>
						
						<li>Payment Details</li>
						<li>Rebates and Excess Payments</li>
						<li>Clear Payments</li>
						
						<li>Sales Invoice and Delivery Receipt</li>
						<li>SI/DR Price and Net Price</li>
						<li>Withholding Tax</li>
						
						<li>Auto-calculation</li>
						<li>Inventory Checking</li>
						<li>Auto-suggest for Inventory Model</li>
						<li>Credit Limit Checking</li>
						
						<li>Charts</li>
						<li>Pagination</li>
						
						<li>Export to Excel</li>
						<li>Projected Collections Report</li>
						
						<li>User Management</li>
						<li>User Permissions</li>
						
						<li>3-Decimal Notation System</li>
						<li>Remarks on Payment</li>
						<li>List Orders and Purchases for Specific Inventory</li>
					</ul>
				</section>
			</fieldset>
			
			<a id="sysreq"></a>
			<fieldset>
				<legend>SYSTEM REQUIREMENTS</legend>
				<section>
					<div>Any Web browser that supports HTML 5 and CSS 3 standards, preferably Firefox 40.0 or above.<br /><br />
						 JavaScript should be enabled in the Web browser of your choice. A notification will be displayed if <?php echo PROG_NAME ?> detected
						 that your Web browser's JavaScript is disabled.<br /><br />
						 Network connectivity (either LAN or Internet, provided that you can access the <?php echo PROG_NAME ?> Web server).<br /><br />
						 A username and a password (to be given by your administrator).
					</div>
				</section>
			</fieldset>
			
			<a id="license"></a>
			<fieldset>
				<legend>END-USER LICENSE AGREEMENT</legend>
				<section>
					<div>
						<p><b>IMPORTANT:</b> PLEASE READ THE TERMS AND CONDITIONS OF THIS LICENSE AGREEMENT CAREFULLY</p>
						
						<p><?php echo COMPANY ?>'s End-User License Agreement (EULA) is a legal agreement between you (either an individual or a single entity)
							and <?php echo COMPANY ?>. For the <?php echo COMPANY ?> software product(s) identified above which may include associated software 
							components, media, printed materials, and online or electronic documentation (&quot;SOFTWARE PRODUCT&quot;). By installing, 
							copying, or otherwise using the SOFTWARE PRODUCT, you agree to be bound by the terms of this EULA. This license agreement 
							represents the entire agreement concerning the program between you and <?php echo COMPANY ?>, (referred to as 
							&quot;licenser&quot;), and it supersedes any prior proposal, representation, or understanding between the parties. If you do not 
							agree to the terms of this EULA, do not install or use the SOFTWARE PRODUCT.</p>
						
						<p>The SOFTWARE PRODUCT is protected by copyright laws and international copyright treaties, as well as other intellectual property laws
						   and treaties. The SOFTWARE PRODUCT is licensed, not sold.</p>
						<ol class="ol_1">
							<li>GRANT OF LICENSE<br /><br />
								The SOFTWARE PRODUCT is licensed as follows:<br /><br />
								<ol class="ol_a">
									<li>Installation and Use.<br />
										<?php echo COMPANY ?> grants you the right to install and use copies of the SOFTWARE PRODUCT on your computer running a
										validly licensed copy of the operating system [e.g. Windows XP, Windows Vista, Windows 7, Mac OS X] and other
										complementary software [e.g. Mozilla Firefox 4] for which the SOFTWARE PRODUCT was designed.<br /><br /></li>
									<li>Backup Copies.<br />
										You may also make copies of the SOFTWARE PRODUCT as may be necessary for backup and archival purposes.
									</li>
								</ol>
								<br /></li>
							<li>DESCRIPTION OF OTHER RIGHTS AND LIMITATIONS<br /><br />
								<ol class="ol_a">
									<li>Maintenance of Copyright Notices.<br />
										You must not remove or alter any copyright notices on any and all copies of the SOFTWARE PRODUCT.<br /><br /></li>
									<li>Usage Rights.<br />
										The SOFTWARE PRODUCT is licensed only for use by <?php echo CLIENT ?>, including it's owners, administrators, and
										employees. Use of a third-party personnel such as a customer, supplier, guest, or non-employee is not
										allowed.<br /><br /></li>
									<li>Distribution.<br />
										You may not distribute registered copies of the SOFTWARE PRODUCT to third parties.<br /><br /></li>
									<li>Prohibition on Reverse Engineering, Decompilation, and Disassembly.<br />
										You may not reverse engineer, decompile, or disassemble the SOFTWARE PRODUCT, except and only to the extent that such
										activity is expressly permitted by applicable law notwithstanding this limitation.<br /><br />
										<?php echo COMPANY ?> reserves the right and royalty for SOFTWARE PRODUCT. The SOFTWARE PRODUCT and its source codes are
										allowed to be viewed only by owners and administrators of <?php echo CLIENT ?>. Viewing and modification of the SOFTWARE
										PRODUCT and its source codes, all or in part, by a non-administrator employee of <?php echo CLIENT ?> or by a
										third-party software consulting group or company is prohibited, unless a written notice is given
										to <?php echo COMPANY ?> and a written and signed approval from <?php echo COMPANY ?> is obtained.<br /><br /></li>
									<li>Rental.<br />
										You may not rent, lease, or lend the SOFTWARE PRODUCT.<br /><br /></li>
									<li>Support Services.<br />
										<?php echo COMPANY ?> may provide you with support services related to the SOFTWARE PRODUCT (&quot;Support Services&quot;).
										Any supplemental software code provided to you as part of the Support Services shall be considered part of the SOFTWARE
										PRODUCT and subject to the terms and conditions of this EULA.<br /><br /></li>
									<li>Compliance with Applicable Laws.<br />
										You must comply with all applicable laws regarding use of the SOFTWARE PRODUCT.
									</li>
								</ol>
								<br /></li>
							<li>TERMINATION<br /><br />
								Without prejudice to any other rights, <?php echo COMPANY ?> may terminate this EULA if you fail to comply with the terms and
								conditions of this EULA. In such event, you must destroy all copies of the SOFTWARE PRODUCT in your possession.<br /><br /></li>
							<li>COPYRIGHT<br /><br />
								All title, including but not limited to copyrights, in and to the SOFTWARE PRODUCT and any copies thereof are owned
								by <?php echo COMPANY ?> or its suppliers. All title and intellectual property rights in and to the content which may be
								accessed through use of the SOFTWARE PRODUCT is the property of the respective content owner and may be protected by applicable
								copyright or other intellectual property laws and treaties. This EULA grants you no rights to use such content. All rights not
								expressly granted are reserved by <?php echo COMPANY ?>.<br /><br /></li>
							<li>NO WARRANTIES<br /><br />
								<?php echo COMPANY ?> expressly disclaims any warranty for the SOFTWARE PRODUCT. The SOFTWARE PRODUCT is provided 'As Is'
								without any express or implied warranty of any kind, including but not limited to any warranties of merchantability,
								non-infringement, or fitness of a particular purpose. <?php echo COMPANY ?> does not warrant or assume responsibility for the
								accuracy or completeness of any information, text, graphics, links or other items contained within the SOFTWARE
								PRODUCT. <?php echo COMPANY ?> makes no warranties respecting any harm that may be caused by the transmission of a computer
								virus, worm, time bomb, logic bomb, or other such computer program. <?php echo COMPANY ?> further expressly disclaims any
								warranty or representation to Authorized Users or to any third party.<br /><br /></li>
							<li>LIMITATION OF LIABILITY<br /><br />
								In no event shall <?php echo COMPANY ?> be liable for any damages (including, without limitation, lost profits, business
								interruption, or lost information) rising out of 'Authorized Users' use of or inability to use the SOFTWARE PRODUCT, even
								if <?php echo COMPANY ?> has been advised of the possibility of such damages. In no event will <?php echo COMPANY ?> be liable
								for loss of data or for indirect, special, incidental, consequential (including lost profit), or other damages based in
								contract, tort or otherwise. <?php echo COMPANY ?> shall have no liability with respect to the content of the SOFTWARE PRODUCT
								or any part thereof, including but not limited to errors or omissions contained therein, libel, infringements of rights of
								publicity, privacy, trademark rights, business interruption, personal injury, loss of privacy, moral rights or the disclosure of
								confidential information.
							</li>
					</div>
				</section>
				
				<section>
					<div>&copy; Chakra Development Group<br />
						 &nbsp; &nbsp; All Rights Reserved<br />
						 &nbsp; &nbsp; June, 2016
					</div>
				</section>
				
				<section>
					<div>
						&nbsp; &nbsp; Chakra Development Group<br />
						&nbsp; &nbsp; Project Members:<br />
						<br />
						&nbsp; &nbsp; &nbsp; &nbsp; Lordinel Grajo<br />
						&nbsp; &nbsp; &nbsp; &nbsp; Systems Analyst / Programmer<br />
					</div>
				</section>
				
				<section>
					<div>
						Apache, the Apache logo, and Powered-by-Apache logo are trademarks of Apache Foundation, Inc.<br />
						<br />
						Apple, the Apple logo, Macintosh, Mac OS, iOS, Safari browser, and Webkit are either registered trademarks or trademarks of Apple
						Computer, Inc.<br />
						<br />
						Express Dymans logo is a trademark of Express Dymans Philippines<br />
						<br />
						GNU, GNU-is-Not-Unix, General Public License, and GPL are trademarks of GNU Software Foundation<br />
						<br />
						Google, the Google logo, Chrome browser, and Chromium Project are either registered trademarks or trademarks of Google Inc.<br />
						<br />
						Microsoft, Microsoft logo, Windows, Windows logo, Microsoft Office, Outlook Express, Windows Live, Windows Live Mail, Internet Explorer,
						Internet Explorer logo, Bing, and Bing logo are either registered trademarks or trademarks of Microsoft Corporation<br />
						<br />
						Mozilla, Mozilla logo, Firefox, Firefox logo, Thunderbird, Thunderbird logo, Minefield, Bugzilla, and Gecko are trademarks of either
						Mozilla Corporation or Mozilla Foundation<br />
						<br />
						MySQL, the MySQL logo, Powered-by-MySQL logo, and Sakila are trademarks of Oracle Corporation<br />
						<br />
						Opera, Opera Mini, and Opera logo are trademarks of Opera Software ASA<br />
						<br />
						PHP, the PHP logo, PHP: Hypertext Preprocessor, and Powered-by-PHP logo are trademarks of The PHP Group<br />
						<br />
						SourceForge logo is a trademark of SourceForge network<br />
						<br />
						W3C, Amaya, Markup Validator, CSS Validator, and SGML/HTML Tidy are either trademarks or service marks of World Wide Web
						Consortium<br />
						<br />
						All other trademarks and services marks are property of their respective owners</p>
					</div>
				</section>
			</fieldset>
		</div>
	</section>
</fieldset>
<?php
?>
