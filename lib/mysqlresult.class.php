<?php
class Lib_MysqlResult implements Iterator {
    private $result;
    private $position;
    private $row_data;
    private $object = 'stdClass';
    private $num_rows = 0;

    public function __get($propertyName) {
    	if($propertyName == 'count') {
    		return $this->num_rows;
    	}
    }

    public function __construct($result,$object,$extra=null) {
    	if(is_resource($result->resoc)) {
			$this->num_rows = $result->count;
    	}
        $this->result = $result->resoc;
        $this->position = 0;
        if(!is_null($object)) { $this->object = $object; }
    }

    public function current() {
    	return $this->row_data;;
//        $this->row_data = mysql_fetch_object($this->result,$this->object);
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        $this->position++;
        $this->row_data = mysql_fetch_object($this->result,$this->object);
    }

    public function rewind() {
        $this->position = 0;
        if((bool)mysql_num_rows($this->result)) {
            mysql_data_seek($this->result, 0);
        }

        /* The initial call to valid requires that data
            pre-exists in $this->row_data
        */
        $this->row_data = mysql_fetch_object($this->result,$this->object);
    }

    public function valid() {
        return (bool)$this->row_data;
    }
}
?>