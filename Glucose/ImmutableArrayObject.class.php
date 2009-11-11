<?php
namespace Glucose;
use \BadMethodCallException;
class ImmutableArrayObject extends \ArrayObject {
	
	private $cannotAlterMessage = 'The array cannot be altered after initialization';
	
	public function append($value) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function asort() {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function exchangeArray($input) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function ksort() {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function natcasesort() {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function natsort() {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function offsetSet($offset, $value) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function offsetUnset($offset) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function uasort($cmp_function) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function uksort($cmp_function) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
}
?>