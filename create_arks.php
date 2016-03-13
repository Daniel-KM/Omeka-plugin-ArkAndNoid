<?php
/**
 * This script will add arks to all existing collections and items.
 *
 * Set the options manually below.
 *
 * @copyright Daniel Berthereau, 2016
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/* ################# */
/*      Config       */
/* ################# */

// Edit these values manually, then set the value of $isReady to true.
$http_host = 'example.org';
$https = 'off';
$server_port = '80';
// The script base is the relative path of the Omeka installation on the host.
// Let empty if the root of Omeka is the host.
$omeka_dir = 'my_omeka_dir';

$isReady = false;

// Set false to process only public records. Anyway, the ark will be set the
// next time the record is saved, even private.
$process_private = true;
$process_items = true;
$process_collections = true;

/* ################# */
/*      Process      */
/* ################# */

// Quick ugly way.
$manually = false;
if (empty($_SERVER['HTTP_HOST'])) {
    if (!$isReady) {
        echo 'Edit the config manually in this file and set the "$isReady" to true.' . PHP_EOL;
        exit;
    }
    $manually = true;
    $_SERVER['HTTP_HOST'] = $http_host;
    $_SERVER['HTTPS'] = $https;
    $_SERVER['SERVER_PORT'] = $server_port;
    $_SERVER['SCRIPT_NAME'] = ($omeka_dir ? '/' . $omeka_dir : '') . '/index.php';
}

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$application = new Omeka_Application(APPLICATION_ENV);
$application->getBootstrap()->setOptions(array(
    'resources' => array(
        'theme' => array(
            'basePath' => THEME_DIR,
            'webBasePath' => WEB_THEME,
))));
$application->initialize();

// Authenticate as the super user to allow modify private records.
$user = get_record_by_id('User', 1);
$bootstrap = Zend_Registry::get('bootstrap');
$bootstrap->auth->getStorage()->write($user->id);
$bootstrap->currentUser = $user;
$bootstrap->getContainer()->currentuser = $user;
$aclHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Acl');
$aclHelper->setCurrentUser($user);

if (empty($process_items) && empty($process_collections)) {
    echo __('No record to process.') . PHP_EOL;
    exit;
}

if ($manually) {
    echo 'Manually edited host: ' . $_SERVER['HTTP_HOST'] . PHP_EOL;
}

echo __('Process started.') . PHP_EOL;

if ($process_items) {
    create_arks('Item', $process_private);
}
if ($process_collections) {
    create_arks('Collection', $process_private);
}

echo __('Process termined.') . PHP_EOL;

echo __("Don't forget to check the errors.log.") . PHP_EOL;
exit;

function create_arks($recordType, $private)
{
    $db = get_db();
    $view = get_view();

    // Get the list of records.
    $table = $db->getTable($recordType)->getTableName();
    $sql = "SELECT id FROM $table";
    $sql .= $private ? '' : ' WHERE public = 1';
    $sql .= ' ORDER BY id ASC;';

    $result = $db->fetchCol($sql);

    $total = count($result);
    echo "Process $total records of type $recordType."  . PHP_EOL;
    $new = 0;
    foreach ($result as $key => $recordId) {
        $key++;
        echo "  $recordType #$recordId ($key / $total)";
        $record = get_record_by_id($recordType, $recordId);
        $ark = $view->Ark($record);
        if ($ark) {
            echo ": $ark" . PHP_EOL;
        }
        else {
            $record->save();
            // Need to reload it to get the ark.
            $record = get_record_by_id($recordType, $recordId);
            $ark = $view->Ark($record);
            if (strlen($ark) === 0) {
                echo ": ERROR: Unable to create an ark. Check logs, parameters and rights." . PHP_EOL;
                exit(1);
            }
            echo ": $ark (new)" . PHP_EOL;
            $new++;
        }
        release_object($record);
        unset($record);
    }
    echo "Process terminated for $total records of type $recordType : $new new arks."  . PHP_EOL;
}
?>
