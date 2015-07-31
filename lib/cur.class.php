<?php
class Lib_Cur implements Iterator, Countable {
	private $cursor = null;
	private $className = null;

	public function __construct($cursor, $className=__CLASS__){
		$this->cursor = $cursor;
		$this->className = $className;

		if($this->cursor instanceof Traversable) {
			$this->cursor->reset();
		}
	}

	public function key(){
		return $this->cursor->key();
	}

	public function rewind(){
		$this->cursor->rewind();
	}

	public function current() {
		$o = new $this->className($this->cursor->current());
		return $o;
	}

	public function next() {
		return $this->cursor->next();
	}

	public function count(){
		return $this->cursor->count();
	}

	public function valid(){
		return $this->cursor->valid();
	}
}
?>