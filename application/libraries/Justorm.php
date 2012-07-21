<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

/**
 * Justorm Main Class
 *
 * @package		Justorm Codeigniter
 * @subpackage	Justorm Codeigniter
 * @category	Codeigniter Library
 * @author		Shmavon Gazanchyan <munhell@gmail.com>
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeblog.ru
 */
class Justorm
{
	/**
	 * Costructor
	 *
	 * @access		public
	 * @return		void
	 */
	public function __construct()
	{
		$this->load->database();

		require_once(APPPATH.'libraries/Justorm/Model.php');
		require_once(APPPATH.'libraries/Justorm/Migration.php');
		
		$yaml_classes = array(
			'Yaml/Exception/ExceptionInterface',
			'Yaml/Exception/DumpException',
			'Yaml/Exception/ParseException',
			'Yaml/Dumper',
			'Yaml/Escaper',
			'Yaml/Inline',
			'Yaml/Parser',
			'Yaml/Unescaper',
			'Yaml/Yaml'
		);
		foreach($yaml_classes as $file)
		{
			require_once(APPPATH. 'third_party/' . $file. '.php');
		}
	}
	
	/**
	 * Execute a migration
	 *
	 * @access		public
	 * @param		integer
	 * @return		void
	 */
	public function migrate($target)
	{
		$start = JO_Migration::current();
		$version = $target;
		if($start < $target)
		{
			$direction = 'up';
			$step = 1;
			++$start;
			++$target;
		} 
		elseif($start > $target)
		{
			$direction = 'down';
			$step = -1;
		}
		else
		{
			return;
		}
		for($i = $start; $i != $target; $i = $i + $step)
		{
			$files = glob(sprintf(APPPATH.'migrations/%04d_*.yml', $i));
			if(count($files) == 0)
			{
				return;
			}
			foreach ($files as $file)
			{
				$yaml = $this->_yaml($file);
				$migration = new JO_Migration($yaml, $direction);
				$migration->apply();
			}
		}
		JO_Migration::current($version);
	}
	
	/**
	 * Yaml interpreter
	 *
	 * @access		private
	 * @param		string
	 * @return		array
	 */
	private function _yaml($file)
	{
		return Symfony\Component\Yaml\Yaml::parse($file);
	}
	
	/**
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @access		public
	 * @param		string
	 * @return		mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
	}
}
