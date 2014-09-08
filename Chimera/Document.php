<?php


namespace chimera;


class Document
{
	protected $_model;
	protected $_id = false;
	protected $_data = array();

	public function __construct($model) {
		$this->_model = $model;
		$this->_data = $this->_model->schema()->defaults();
	}

	public function __set($key, $val) {
		if ($key == 'id') $this->_id = $val;
		else if ($this->_model->schema()->has_virtual($key))
			$this->_model->schema()->virtual($key)->execute_set($this, $val);
		else {
			$this->_model->schema()->validate($key, $val);
			$this->_data[$key] = $val;
		}
	}

	public function __get($key) {
		if ($key == 'id') return $this->_id;
		if (!isset($this->_data[$key])) {
			if ($this->_model->schema()->has_virtual($key)) return $this->_model->schema()->virtual($key)->execute_get($this);
			return false;
		}
		return $this->_data[$key];
	}

	public function save() {
		$this->_model->schema()->validate($this);

		$fields = $this->_model->schema()->definition();
		$field_names = array_keys($fields);
		$data = $this->data();
		$values = array(':id' => 'NULL', ':_data' => '');
		foreach ($field_names as $key => $name) {
			$values[':' . $name] = $this->$name;
			unset($data[$name]);
		}
		$values[':_data'] = json_encode($data);

		if ($this->id) {
			$values[':id'] = $this->id;
			unset($values['id']);
	#TODO "updates need to be moved into storage classes";
			$update = 'UPDATE ' . $this->_model->source() . ' SET _data=:_data';
			foreach($fields as $name => $info) {
				switch ($info['type']) {
					case Schema::Date:
					case Schema::DateTime:
					case Schema::TimeStamp:
						if (strpos($this->$name, ')')) {
							$update .= ", $name=" . $this->$name;
							unset($values[':'.$name]);
						}
						else $update .= ", $name=:$name";
						break;

					default:
						$update .= ", $name=:$name";
						break;
				}
			}
			$update .= ' WHERE id=:id';
			$query = $this->_model->owner()->prepare($update);

			$ok = $query->execute($values);
			if (!$ok) {
				$e = $query->errorInfo();
				throw new \Exception('Error updating document: ' . $e[2]);
				return false;
			} else {
				return true;
			}

		} else {
#TODO "inserts need to be moved into storage classes";
			unset($values['id']);
			unset($values[':id']);
			$sql_field_names = array('_data');
			$insert = 'INSERT INTO ' . $this->_model->source() . ' (%s) VALUES (:_data';
			foreach ($fields as $name => $info) {
				$sql_field_names[] = $name;
				switch ($info['type']) {
					case Schema::Date:
					case Schema::DateTime:
					case Schema::TimeStamp:
						if (strpos($this->$name, ')')) {
							$insert .= ',' . $this->$name;
							unset($values[':'.$name]);
						}
						else {
							$insert .= ',:'.$name;
						}
						break;

					default:
						$insert .= ',:'.$name;
						break;
				}
			}
 			$insert .= ')';
			$insert = sprintf($insert, join(',', $sql_field_names));

			$query = $this->_model->owner()->prepare($insert);
			$ok = $query->execute($values);

			if (!$ok) {
				$e = $query->errorInfo();
				throw new \Exception('Error inserting document: ' . $e[2]);
				return false;
			} else {
				$this->id = $this->_model->owner()->lastInsertId();
				return true;
			}
		}
		return false;
	}

	public function delete() {
		if ($this->id) {
			#TODO "delete need to be moved into storage classes";
			$query = $this->_model->owner()->prepare('DELETE FROM ' . $this->_model->source() . ' WHERE id=?');
			$ok = $query->execute(array($this->id));
			if (!$ok) {
				$e = $query->errorInfo();
				throw new \Exception('Error inserting document: ' . $e[2]);
				return false;
			}
		}
		return true;
	}

	public function model() { return $this->_model; }
	public function data($new=false, $replace=false) {
		if ($new === false)			return $this->_data;
		if ($replace === true)	$this->_data = $new;
		foreach ($new as $key => $value) {
			if ($key == '_data') continue;
		 	$this->$key = $value;
		}
	}

	// representations
	public function __toString() 	{ return $this->to_string(); }
	public function to_string() {
		ob_start();
		var_export($this->to_array());
		return ob_get_clean();
	}

	public function to_array() {
		$out = $this->_data;
		$out['id'] = $this->_id;
		return $out;
	}

	public function to_json() {
		return json_encode($this->to_array());
	}
}

/*
class DocumentSet implements Iterator, Countable, ArrayAccess
{
	protected $_model;
	protected $_statement;
	protected $_key;
	protected $_documents;


	public function __construct($model, $statement) {
		$this->_model = $model;
		$this->_statement = $statement;
		$this->_documents = array();
	}


// ===========================================================
// - ITERATOR INTERFACE
// ===========================================================
	function rewind() {
		$this->_key = 0;
		$this->valid = false;
	}

	function valid() {
		if (!$this->row) $this->row = $this->result->fetch_assoc();
		if (!empty($this->row)) {
			$this->nextRow = $this->result->fetch_assoc();
			$this->valid = true;
		} else {
			$this->valid = false;
		}

		# check for limits
		if ($this->max) {
			if ($this->current >= $this->max) {$this->valid = false;}
			$this->num_objects = $this->current;
		}
		return $this->valid;
	}

	function  current() {
		# reset the model
		$themodel = clone $this->get_model();
		do {
			$themodel->process_row($this->row);
			if ($this->nextRow && ($this->nextRow['id'] == $this->row['id'])) {
				$this->next();
				$this->valid();
			} else {
				break;
			}
		} while(true);
		$this->current++;
		return $themodel;
	}

	function key() {
		return $this->_key;
	}

	function next() {
		$this->_key++;
		$this->row = $this->nextRow;
	}


// ===========================================================
// - COLLECTION ACCESS
// ===========================================================
	function first() {
		if (!$this->first) {
			$this->rewind();
			if (count($this->result) === false) return false;
			if ($this->valid()) {
				$this->first = $this->current();
			} else {
				return false;
			}
		}
		return $this->first;
	}

	function item($num) {
		$c = 0;
		foreach($this as $k => $v) {
			if ($c == $num) return $v;
			$c++;
		}
		return false;
	}

	function last() {
		if (!$this->last) {
			foreach($this as $k => $v) {}
			$this->last = $v;
		}
		return $this->last;
	}

	function is_empty() {
		return !$this->first();
	}

// ===========================================================
// - ARRAYACCESS INTERFACE
// ===========================================================
	public function offsetExists($offset) {
		if ($this->item($offset) !== false) return true;
		return false;
	}

	public function offsetGet($offset) {
		if ($offset == 0) return $this->first();
		return $this->item($offset);
	}

	public function offsetSet($offset, $value) {
		throw new ReadOnlyAccess('DBRecordCollection items are read only.');
	}

	public function offsetUnset($offset) {
		throw new ReadOnlyAccess('DBRecordCollection items are read only.');
	}


}
*/


