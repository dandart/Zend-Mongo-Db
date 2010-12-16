<?
/**
 *	This is used in the Testing and Validation phase of the mvn test
 */	

define('MONGO_PATH', 		realpath(dirname(__FILE__)).'/src/main/php/Mongo');
define('MONGO_TEST_PATH',	realpath(dirname(__FILE__)).'/src/test/php/Mongo/');
$paths = array(	get_include_path(),
				MONGO_PATH,
				realpath(dirname(__FILE__)).'/src/main/php',
				realpath(dirname(__FILE__)).'/target/phpinc',
				'.');
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'environments.php';
defined('APPLICATION_ENV')		or define('APPLICATION_ENV', 	ENVIRONMENT_UNIT_TEST);
defined('APPLICATION_PATH') 	or define('APPLICATION_PATH', 	realpath(dirname(__FILE__)).'/src/main/php/Mongo');
defined('MOCK_DB_PATH')			or define('MOCK_DB_PATH', 		realpath(dirname(__FILE__)).'/src/test/testDB/');

require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('PHPUnit_');
$autoloader->registerNamespace('Zend_');
$autoloader->registerNamespace('Mongo_');

PHPUnit_Util_Filter::addDirectoryToWhitelist(MONGO_PATH);

