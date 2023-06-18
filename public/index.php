<?php
namespace Bdd88\AssetsDirector;

use Exception;

try {

/**
 * Set common directory paths for ease of use.
 */
$baseDir = dirname(__DIR__, 1);
$pageViewDir = $baseDir . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Page' . DIRECTORY_SEPARATOR;
$layoutViewDir = $baseDir . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Presentation' . DIRECTORY_SEPARATOR;
$configDir = $baseDir . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR;
$logsDir = $baseDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;

/**
 * Setup the auto loader and service container.
 */
require '../NexusFrame/Dependency/AutoLoader.php';
$autoLoader = new \NexusFrame\Dependency\AutoLoader();
$autoLoader->register('Bdd88\AssetsDirector', $baseDir);
$serviceContainer = new \NexusFrame\Dependency\ServiceContainer();

/**
 * Load and validate configuration files.
 */
$mainConfig = $serviceContainer->create(\Bdd88\AssetsDirector\Model\Config\MainConfig::class, [$configDir . 'main.ini']);
$mainConfig->validate();
//$routesConfig = $serviceContainer->create(\Bdd88\AssetsDirector\Model\Config\RoutesConfig::class, [$configDir . 'routes.ini']);
//$routesConfig->validate();

/**
 * Setup the database connection using the credentials from the main config.
 */
$serviceContainer->create(\NexusFrame\Database\MySql\MySql::class, array(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME')));

/**
 * Configure logs
 */
$logger = $serviceContainer->create(\NexusFrame\Utility\Logger::class);
$logger->setup('login',  $logsDir . 'logins.log');
$logger->setup('curl', $logsDir . 'curl.log');
$logger->setup('TdaApi', $logsDir . 'TdaApi.log');

/**
 * Create pages by routing names to classes and views.
 */
$webpage = $serviceContainer->create(\NexusFrame\Webpage\Controller\Main::class);
$webpage->createPage('home', TRUE)
    ->setClass(\Bdd88\AssetsDirector\Model\Page\HomePage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/home.phtml")
    ->setLayoutViewPath("$layoutViewDir/mainLayout.phtml")
    ->setLoginRequired(TRUE)
;
$webpage->createPage('login')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\LoginPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/login.phtml")
    ->setLayoutViewPath("$layoutViewDir/noNavLayout.phtml")
;
$webpage->createPage('logout')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\LogoutPage::class)
    ->setMethod('exec')
;
$webpage->createPage('transactions')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\TransactionsPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/transactions.phtml")
    ->setLayoutViewPath("$layoutViewDir/mainLayout.phtml")
    ->setLoginRequired(TRUE)
;
$webpage->createPage('trades')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\TradesPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/trades.phtml")
    ->setLayoutViewPath("$layoutViewDir/mainLayout.phtml")
    ->setLoginRequired(TRUE)
;
$webpage->createPage('summary')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\SummaryPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/summary.phtml")
    ->setLayoutViewPath("$layoutViewDir/mainLayout.phtml")
    ->setLoginRequired(TRUE)
;
$webpage->createPage('account')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\AccountPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/account.phtml")
    ->setLayoutViewPath("$layoutViewDir/mainLayout.phtml")
    ->setLoginRequired(TRUE)
;


/**
 * Create error pages for custom http status code handling.
 */
$webpage->createErrorPage('default', TRUE)
    ->setClass(\Bdd88\AssetsDirector\Model\Page\ErrorPage::class)
    ->setMethod('exec')
    ->setPageViewPath("$pageViewDir/errorcode.phtml")
;
$webpage->createErrorPage('401')
    ->setClass(\Bdd88\AssetsDirector\Model\Page\LoginPage::class)
    ->setMethod('exec')
    ->setParameters(['message' => 'Page requires login.'])
    ->setPageViewPath("$pageViewDir/login.phtml")
    ->setLayoutViewPath("$layoutViewDir/noNavLayout.phtml")
;

/**
 * Retrieve the client request and run the application.
 */
$clientRequest = $serviceContainer->create(\NexusFrame\Webpage\Model\ClientRequest::class);
$clientRequest->get->page ??= NULL;
echo $webpage->exec($clientRequest->get->page);

} catch (Exception $e) {
    echo '<pre>';
    throw $e;
}