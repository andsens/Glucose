<?php
namespace Glucose;
use \BadMethodCallException;
class ImmutableFixedArray implements Iterator, ArrayAccess, Countable {
	
	private $cannotAlterMessage = 'The array cannot be altered after initialization.';
	
	private $fixedArray;
	
	public function __construct($array) {
		$this->fixedArray = SplFixedArray::fromArray($array);
	}
	
	public function count() {
		return count($this->fixedArray);
	}
	
	public function current() {
		return $this->fixedArray->current();
	}
	
	public function getSize() {
		return $this->fixedArray->getSize();
	}
	
	public function key() {
		return $this->fixedArray->key();
	}
	
	public function next() {
		$this->fixedArray->next();
	}
	
	public function offsetExists($index) {
		return $this->fixedArray->offsetExists();
	}
	
	public function offsetGet($offset) {
		return $this->fixedArray->offsetGet($offset);
	}
	
	public function offsetSet($offset, $value) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}

	public function offsetUnset($offset) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function rewind() {
		$this->fixedArray->rewind($offset);
	}
	
	public function setSize($size) {
		throw new BadMethodCallException($this->cannotAlterMessage);
	}
	
	public function toArray() {
		return $this->fixedArray->toArray();
	}
	
	public function valid() {
		return $this->fixedArray->valid();
	}
}