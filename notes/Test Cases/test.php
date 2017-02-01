
<?php
class A {
    static function foo($object) {
      return $object->id;
    }
}

class B {
	public $id;
	public $a;
	public function __construct() {
		 $this->a = new A();
		 $this->id = 1;
	}
}




$instance = new B;
echo $instance->a->foo($instance);


?>
