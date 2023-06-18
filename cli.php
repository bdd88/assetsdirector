<?php

// Retrieve command line options and flags.
$args = array_splice($_SERVER['argv'], 1);
$args = explode('=', implode('=', $args));
$options = array();
while (sizeof($args) > 0) {
    $arg = array_shift($args);
    if ($arg[0] === '-' && $arg[1] === '-') {
        $key = substr($arg, 2);
        $value = TRUE;
    } elseif ($arg[0] === '-' && sizeof($args) > 0) {
        $key = substr($arg, 1);
        $value = array_shift($args);
    } else {
        throw new Exception('Options must be prefixed with a dash, and toggles/flags must be prefixed with a double dash.');
    }
    $options[$key] = $value;
}

// Start the app CLI.
require __DIR__ . '/NexusFrame/Dependency/AutoLoader.php';
$autoLoader = new \NexusFrame\Dependency\AutoLoader();
$autoLoader->register('NexusFrame', __DIR__ . DIRECTORY_SEPARATOR . 'NexusFrame');
$autoLoader->register('Bdd88\AssetsDirector', __DIR__);
$serviceContainer = new \NexusFrame\Dependency\ServiceContainer();
$logsDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
$logger = $serviceContainer->create(\NexusFrame\Utility\Logger::class);
$logger->setup('curl', $logsDir . 'curl.log');
$logger->setup('TdaApi', $logsDir . 'TdaApi.log');
$serviceContainer->create(\NexusFrame\Database\MySql\MySql::class, array(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME')));

$cli = $serviceContainer->create(Bdd88\AssetsDirector\Controller\Cli::class);
foreach ($options as $key => $value) {
    $cli->$key($value);
}