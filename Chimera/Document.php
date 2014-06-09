<?
declare(encoding='UTF-8');

namespace chimera
{


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
		if ($this->_model->schema()->has_virtual($key))
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
		$values = array('id' => 'NULL', '_data' => '');
		foreach ($field_names as $key => $name) {
			$values[$name] = $this->$name;
			unset($data[$name]);
		}
		unset($data['id']);
		$values['_data'] = json_encode($data);

		if ($this->id) {
			unset($values['id']);
			echo "updates need to be moved into storage classes";
			$update = 'UPDATE ' . $this->_model->name() . ' SET _data=?';
			foreach($fields as $name => $info) {
				switch ($info['type']) {
					case Schema::Date:
					case Schema::DateTime:
					case Schema::TimeStamp:
						if (strpos($this->$name, ')')) {
							$update .= ", $name=" . $this->$name;
							unset($values[$name]);
						}
						else $update .= ", $name=?";
						break;

					default:
						$update .= ", $name=?";
						break;
				}
			}
			$update .= ' WHERE id=?';
			$query = Chimera::adapter()->prepare($update);

			// don't change id, but stick it on the end for the where
			$values[] = $this->id;

			$ok = $query->execute(array_values($values));
			if (!$ok) {
				$e = $query->errorInfo();
				throw new \Exception('Error updating document: ' . $e[2]);
				return false;
			}

		} else {
			echo "inserts need to be moved into storage classes";
			$insert = 'INSERT INTO ' . $this->_model->name() . ' VALUES (?,?';
			foreach ($fields as $name => $info) {
				switch ($info['type']) {
					case Schema::Date:
					case Schema::DateTime:
					case Schema::TimeStamp:
						if (strpos($this->$name, ')')) {
							$insert .= ',' . $this->$name;
							unset($values[$name]);
						}
						else $insert .= ',?';
						break;

					default:
						$insert .= ',?';
						break;
				}
			}
 			$insert .= ')';

			$query = Chimera::adapter()->prepare($insert);

			$ok = $query->execute(array_values($values));
			if (!$ok) {
				$e = $query->errorInfo();
				throw new \Exception('Error inserting document: ' . $e[2]);
				return false;
			}
		}
	}


	public function model() { return $this->_model; }
	public function data($new=false, $replace=false) {
		if ($new === false)			return $this->_data;
		if ($replace === true)	$this->_data = $new;
		foreach ($new as $key => $value) {
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


}



?>