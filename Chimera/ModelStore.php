<?php

namespace chimera;

class ModelStore
{
	protected $_schema_base;

	private function chimera() {
		static $chimera;
		if (!$chimera) $chimera = new \chimera\Chimera();
		return $chimera;
	}

	public function __construct($schema_base, $dsn, $user, $pass) {
		$this->_schema_base = $schema_base;
		$this->chimera()->connect($dsn, $user, $pass);
	}

	public function __call($name, $args) {
		$schema_file = $this->_schema_base . DIRECTORY_SEPARATOR . $name . '.json';
		if (file_exists($schema_file)) {
			$json = json_decode(file_get_contents($schema_file));
			$schema = array();
			foreach($json as $k => $v) {
				$schema[$k] = array(
					'type' 		=> constant('\\chimera\\' . $v->type),
					'default' => $v->default
				);
			}
			return $this->chimera()->model($name, new \Chimera\Schema($schema));
		}
	}

	public function migrate() {
		$di = new \DirectoryIterator($this->_schema_base);
		foreach ($di as $file) {
			if ($file->isFile() && $file->getExtension() == 'json') {
				$name = $file->getBasename('.json');
				$model = $this->$name();
   		}
		}
		$this->chimera()->migrate();
	}
}

