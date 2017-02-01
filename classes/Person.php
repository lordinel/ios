<?php
// note: this class requires scripts/person.js


// class definition for person
abstract class Person extends Layout
{
	// attributes
	protected $name;
	protected $contactPerson;
	protected $address;
	protected $telephone;
	protected $mobile;
	protected $fax;
	protected $email;



	// abstract methods
	//abstract public static function showInputForm();
	//abstract public static function showInputFieldset();
	//abstract public function save();
	//abstract public function view();
	//abstract public static function showListTasks();
	//abstract public static function showList();
	//abstract public static function showAutoSuggest();
	//abstract public static function autoFill();



	// display input fieldset
	protected static function showBasicInputFields( array $personInfo = null, $nameLabel = "Name", $showContactPerson = false, $addressLabel = null, $addressIsRequired = true )
	{
		$class = self::getInstanceClassName();

?>
		<section>
			<div>
				<label for="<?php echo $class ?>_name" class="required_label"><?php echo $nameLabel ?>:</label>
				<input type="text" name="<?php echo $class ?>_name" id="<?php echo $class ?>_name" list="autosuggest_<?php echo $class ?>_name" class="form_input_text" maxlength="100" autofocus="autofocus" required="required"<?php echo ( $personInfo != null ) ? " value=\"" . capitalizeWords( Filter::reinput( $personInfo['name'] ) ) . "\"" : "" ?> />
				<datalist id="autosuggest_<?php echo $class ?>_name"></datalist>
				<input type="hidden" name="<?php echo $class ?>_query_mode" id="<?php echo $class ?>_query_mode" value="<?php echo ( $personInfo != null ) ? "edit" : "new" ?>" />
				<input type="hidden" name="<?php echo $class ?>_id" id="<?php echo $class ?>_id" value="<?php echo ( $personInfo != null ) ? $personInfo['id'] : "null" ?>" />
			</div>
<?php		if ( $showContactPerson == true )
			{
?>			<div>
				<label for="contact_person">Contact Person:</label>
				<input type="text" name="contact_person" id="contact_person" class="form_input_text" maxlength="100"<?php echo ( $personInfo != null ) ? " value=\"" . capitalizeWords( Filter::reinput( $personInfo['contact_person'] ) ) . "\"" : "" ?> />
			</div>
<?php		}
?>		</section>

<?php	if ( $addressLabel != null )
		{
?>		<section>
			<div>
				<label for="address"><?php echo $addressLabel ?>:</label>
				<textarea name="address" id="address" rows="2"<?php echo $addressIsRequired == true? 'required="required"' : '' ?>><?php echo ( $personInfo != null ) ? capitalizeWords( Filter::reinput( $personInfo['address'] ) ) : "" ?></textarea>
			</div>
		</section>
<?php	}
?>
		<section>
			<div>
				<label for="telephone">Telephone:</label>
				<input type="tel" name="telephone" id="telephone" maxlength="100"<?php echo ( $personInfo != null ) ? " value=\"" . Filter::reinput( $personInfo['telephone'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="mobile">Mobile:</label>
				<input type="tel" name="mobile" id="mobile" maxlength="100"<?php echo ( $personInfo != null ) ? " value=\"" . Filter::reinput( $personInfo['mobile'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="fax">Fax:</label>
				<input type="tel" name="fax" id="fax" maxlength="100"<?php echo ( $personInfo != null ) ? " value=\"" . Filter::reinput( $personInfo['fax'] ) . "\"" : "" ?> />
			</div>
			<div>
				<label for="email">E-mail:</label>
				<input type="email" name="email" id="email" maxlength="100"<?php echo ( $personInfo != null ) ? " value=\"" . Filter::reinput( $personInfo['email'] ) . "\"" : "" ?> />
			</div>
		</section>

<?php
	}



	// save person info
	public function prepareBasicInputData()
	{
		$class = self::getInstanceClassName();


		// filter entries and prepare for query

		$this->name = "'" . Filter::input( $_POST[$class.'_name'] ) . "'";


		if ( isset( $_POST['contact_person'] ) )   //if ( $class != "agent" )
		{
			if ( empty( $_POST['contact_person'] ) )
				$this->contactPerson = "NULL";
			else
				$this->contactPerson = "'" . Filter::input( $_POST['contact_person'] ) . "'";
		}


		if ( isset( $_POST['address'] ) ) {
			if ( empty( $_POST['address'] ) ) {
				$this->address = "NULL";
			} else {
				$this->address = "'" . Filter::input( $_POST['address'] ) . "'";
			}
		}


		if ( empty( $_POST['telephone'] ) )
			$this->telephone = "NULL";
		else
			$this->telephone = "'" . Filter::input( $_POST['telephone'] ) . "'";


		if ( empty( $_POST['mobile'] ) )
			$this->mobile = "NULL";
		else
			$this->mobile = "'" . Filter::input( $_POST['mobile'] ) . "'";


		if ( empty( $_POST['fax'] ) )
				$this->fax = "NULL";
			else
				$this->fax = "'" . Filter::input( $_POST['fax'] ) . "'";


		if ( empty( $_POST['email'] ) )
			$this->email = "NULL";
		else
			$this->email = "'" . Filter::input( $_POST['email'] ) . "'";

	}



	// view person info
	public function showBasicInfo( $showContactPerson = false, $addressLabel = null )
	{
		//$class = self::getInstanceClassName();
?>
		<section class="main_record_label">
			<div><?php echo $this->name ?></div>
		</section>

<?php
		if ( $showContactPerson == true && $this->contactPerson != null )
		{
?>		<section>
			<div>
				<span class="record_label">Contact Person:</span>
				<span class="record_data"><?php echo $this->contactPerson ?></span>
			</div>
		</section>
<?php	}


		if ( $addressLabel != null && $this->address != null )
		{
?>
		<section>
			<div>
				<span class="record_label"><?php echo $addressLabel ?>:</span>
				<span class="record_data"><?php echo $this->address ?></span>
			</div>
		</section>
<?php
		}


		if ( $this->telephone != null || $this->mobile != null ||
			 $this->fax != null		  || $this->email != null )
		{
?>		<section>
<?php
			if ( $this->telephone != null )
			{
?>			<div>
				<span class="record_label">Telephone:</span>
				<span class="record_data"><?php echo $this->telephone ?></span>
			</div>
<?php		}

			if ( $this->mobile != null )
			{
?>			<div>
				<span class="record_label">Mobile:</span>
				<span class="record_data"><?php echo $this->mobile ?></span>
			</div>
<?php		}

			if ( $this->fax != null )
			{
?>			<div>
				<span class="record_label">Fax:</span>
				<span class="record_data"><?php echo $this->fax ?></span>
			</div>
<?php		}

			if ( $this->email != null )
			{
?>			<div>
				<span class="record_label">Email:</span>
				<span class="record_data"><?php echo $this->email ?></span>
			</div>
<?php		}

?>		</section>
<?php
		}
	}
}

?>
