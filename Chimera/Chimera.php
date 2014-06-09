<?
declare(encoding='UTF-8');


namespace chimera
{

class Chimera
{
	private $_adapter = false;
	private $_storage = false;
	private $_models = array();

	const MySQL  	= "MySQL";
	const SQLite  = "SQLite";

	function __construct($dsn=false, $user=false, $pass=false, $options=array()) {
		if ($dsn !== false) $this->connect($dsn, $user, $pass, $options);
	}

	public function connect($dsn, $user=false, $pass=false, $options=array()) {
		if (strpos($dsn, 'mysql') == 0) {
			$this->_storage = static::MySQL;
			require(__DIR__.'/Storage/'.$this->_storage.'/'.$this->_storage.'.php');
			require(__DIR__.'/Storage/'.$this->_storage.'/Query.php');
		}
		$this->set_adapter(new \PDO($dsn, $user, $pass, $options));
	}

	public static function autoload($class_name) {
		static $base_dir;
		if (!$base_dir) {
			$this_class = str_replace(__NAMESPACE__.'\\', '', __CLASS__);
			$base_dir = __DIR__;
			if (substr($base_dir, -strlen($this_class)) === $this_class) {
				$base_dir = substr($base_dir, 0, -strlen($this_class));
			}
		}
		$class_name = ltrim($class_name, '\\');
		$file_name  = $base_dir;
		$namespace = '';
		if ($last_pos = strripos($class_name, '\\')) {
			$namespace = substr($class_name, 0, $last_pos);
			$class_name = substr($class_name, $last_pos + 1);
			$file_name  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$file_name .= $class_name . '.php';
		if (file_exists($file_name)) require $file_name;
	}


	public function model($name, $schema=false, $source=false) {
		if ($source === false) $source = $name;
		if (!isset($this->_models[$name])) $this->_models[$name] = new Model($source, $schema, $this);
		return $this->_models[$name];
	}

	public function adapter() {
		return $this->_adapter;
	}

	public function set_adapter($pdo) {
		$this->_adapter = $pdo;
	}

	public function query() {
		$query = "chimera\Storage\\" . $this->_storage . "\Query";
		return new $query($this->adapter());
	}

	// create table and migrations
	public function migrate($model_name=false) {
		// do them all
		if ($model_name == false) $to_migrate = array_keys($this->_models);
		else 											$to_migrate = array($model_name);

		foreach ($to_migrate as $model_name) {
			if (!isset($this->_models[$model_name])) return false;
			$engine = "chimera\Storage\\" . $this->_storage;
			$sql = $engine::create_table_query($model_name, $this->_models[$model_name]->schema()->definition());
			$this->_adapter->query($sql);
		}
	}
}

require(__DIR__.'/Schema.php');
require(__DIR__.'/Model.php');
require(__DIR__.'/Document.php');



}
?>