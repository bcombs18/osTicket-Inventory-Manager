<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.app.php');
require_once(INCLUDE_DIR.'class.dispatcher.php');
require_once(INCLUDE_DIR.'class.osticket.php');
require_once(INCLUDE_DIR.'class.import.php');
require_once('config.php');

const INVENTORY_TABLE = TABLE_PREFIX . 'inventory_asset';

define ( 'OST_WEB_ROOT', osTicket::get_root_path ( __DIR__ ) );

const INVENTORY_WEB_ROOT = OST_WEB_ROOT . 'scp/dispatcher.php/inventory/';

const OST_ROOT = INCLUDE_DIR . '../';
const INVENTORY_PLUGIN_ROOT = __DIR__ . '/';
const INVENTORY_INCLUDE_DIR = INVENTORY_PLUGIN_ROOT . 'include/';
const INVENTORY_MODEL_DIR = INVENTORY_INCLUDE_DIR . 'model/';
const INVENTORY_CONTROLLER_DIR = INVENTORY_INCLUDE_DIR . 'controller/';

const INVENTORY_ASSETS_DIR = INVENTORY_PLUGIN_ROOT . 'assets/';
const INVENTORY_VENDOR_DIR = INVENTORY_PLUGIN_ROOT . 'vendor/';
const INVENTORY_VIEWS_DIR = INVENTORY_PLUGIN_ROOT . 'views/';

const INVENTORY_PLUGIN_VERSION = '1.1.5';

const SEARCH_BACKEND = 'assetmysql';

require_once INVENTORY_MODEL_DIR.'AssetSearch.php';

require_once INVENTORY_VENDOR_DIR.'autoload.php';
spl_autoload_register(array(
    'InventoryPlugin',
    'autoload'
));

class InventoryPlugin extends Plugin {

    var $config_class = 'InventoryConfig';

    public static function autoload($className) {
        $className = ltrim ( $className, '\\' );
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos ( $className, '\\' )) {
            $namespace = substr ( $className, 0, $lastNsPos );
            $className = substr ( $className, $lastNsPos + 1 );
            $fileName = str_replace ( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace ( '_', DIRECTORY_SEPARATOR, $className ) . '.php';
        $fileName = 'include/' . $fileName;

        if (file_exists ( INVENTORY_PLUGIN_ROOT . $fileName )) {
            require $fileName;
        }
    }

    public function bootstrap() {
        if($this->firstRun()) {
            if(!$this->configureFirstRun()) {
                return false;
            }
        } else if ($this->needsUpgrade()) {
            $this->configureUpgrade();
        }

        $config = $this->getConfig();

        if($config->get('inventory_backend_enable')) {
            $this->createStaffMenu();
        }

        Signal::connect( 'apps.scp', array(
            'InventoryPlugin',
            'callbackDispatch'
        ));
    }

    static public function callbackDispatch($object, $data) {
        $media_url = url ( '^/inventory.*assets/',
            patterns ( 'controller\MediaController',
                url_get ( '^(?P<url>.*)$', 'defaultAction' )
            )
        );

        $asset_url = url ( '^/inventory.*asset',
            patterns( 'controller\Asset',
                url_get('^/(?P<id>\d+)$', 'getAsset'),
                url_post('^/(?P<id>\d+)$', 'updateAsset'),
                url_get('^/(?P<id>\d+)/edit$', 'editAsset'),
                url_get('^/(?P<id>\d+)/delete$', 'delete'),
                url_post('^/(?P<id>\d+)/delete$', 'delete'),
                url_get('^/(?P<id>\d+)/preview$', 'preview'),
                url_get('^/(?P<id>\d+)/user$', 'viewUser'),
                url_get('^/(?P<id>\d+)/change-user$', 'changeUserForm'),
                url_post('^/users/lookup$', 'getUser'),
                url_get('^/users/lookup/form$', 'lookupUser'),
                url_post('^/users/lookup/form$', 'addUser'),
                url_get('^/users/select$', 'selectUser'),
                url_get('^/users/select/(?P<id>\d+)$', 'selectUser'),
                url_post('^/(?P<id>\d+)/note$', 'createNote'),
                url_get('^/(?P<id>\d+)/retire$', 'retire'),
                url_post('^/(?P<id>\d+)/retire$', 'retire'),
                url_get('^/(?P<id>\d+)/activate$', 'activate'),
                url_post('^/(?P<id>\d+)/activate$', 'activate'),
                url_get('^/lookup', 'lookup'),
                url_get('^/lookup/form$', 'lookup'),
                url_post('^/lookup/form$', 'addAsset'),
                url('^/search',
                    patterns('controller\Search',
                        url_get('^$', 'getAdvancedSearchDialog'),
                        url_post('^$', 'doSearch'),
                        url_get('^/(?P<id>\d+)$', 'editSearch'),
                        url_get('^/adhoc,(?P<key>[\w=/+]+)$', 'getAdvancedSearchDialog'),
                        url_get('^/create$', 'createSearch'),
                        url_post('^/(?P<id>\d+)/save$', 'saveSearch'),
                        url_post('^/save$', 'saveSearch'),
                        url_delete('^/(?P<id>\d+)$', 'deleteSearch'),
                        url_get('^/field/(?P<id>[\w_!:]+)$', 'addField'),
                        url('^/column/edit/(?P<id>\d+)$', 'editColumn'),
                        url('^/sort/edit/(?P<id>\d+)$', 'editSort'),
                        url_post('^(?P<id>\d+)/delete$', 'deleteQueues'),
                        url_post('^(?P<id>\d+)/disable$', 'disableQueues'),
                        url_post('^(?P<id>\d+)/enable$', 'undisableQueues')
                    )),
                url('^/queue', patterns('controller\Search',
                    url('^(?P<id>\d+/)?preview$', 'previewQueue'),
                    url_get('^(?P<id>\d+)$', 'getQueue'),
                    url_get('^addColumn$', 'addColumn'),
                    url_get('^condition/add$', 'addCondition'),
                    url_get('^condition/addProperty$', 'addConditionProperty'),
                    url_get('^counts$', 'collectQueueCounts'),
                    url('^/(?P<id>\d+)/delete$', 'deleteQueue')
                )),
                url('^/note/', patterns('controller\Note',
                    url_get('^(?P<id>\d+)$', 'getNote'),
                    url_post('^(?P<id>\d+)$', 'updateNote'),
                    url_delete('^(?P<id>\d+)$', 'deleteNote'),
                    url_post('^attach/(?P<ext_id>\w\d+)$', 'createNote')
                )),
                url('/add', 'addAsset'),
                url('/handle', 'handle')
            )
        );

        $import_url = url('^/inventory.*import',
            patterns('controller\Import',
                url('/bulk', 'importAssets'),
                url('/handle', 'handle')
            )
        );

        $queue_url = url('^/inventory.*queue/', patterns('controller\Search',
            url('^(?P<id>\d+/)?preview$', 'previewQueue'),
            url_get('^(?P<id>\d+)$', 'getQueue'),
            url_get('^addColumn$', 'addColumn'),
            url_get('^condition/add$', 'addCondition'),
            url_get('^condition/addProperty$', 'addConditionProperty'),
            url_get('^counts$', 'collectQueueCounts'),
            url('^(?P<id>\d+)/delete$', 'deleteQueue')
        ));

        $admin_url = url('^/inventory.*admin', patterns('controller\Admin',
            url('^/quick-add', patterns('controller\Admin',
                url('^/queue-column$', 'addQueueColumn')
            ))
        ));

        $settings_url = url('^/inventory.*settings', patterns('controller\Settings',
            url_get('^/form/field-config/(?P<id>\d+)$', 'getFieldConfiguration'),
            url_post('^/form/field-config/(?P<id>\d+)$', 'saveFieldConfiguration'),
            url_delete('^/form/answer/(?P<entry>\d+)/(?P<field>\d+)$', 'deleteAnswer'),
            url_get('^/form/(?P<id>\d+)/fields/view$', 'getAllFields'),
            url('^/forms$', 'formsPage'),
            url('^/queues$', 'queuesPage'),
            url('^/api$', 'apiPage')
        ));

        $object->append ( $media_url );
        $object->append ( $import_url );
        $object->append ( $asset_url );
        $object->append ( $queue_url );
        $object->append ( $admin_url );
        $object->append ( $settings_url );
    }

    function createStaffMenu() {
        $app = new Application();
        $app->registerStaffApp('Inventory Manager', INVENTORY_WEB_ROOT.'asset/handle');
    }

    function firstRun() {
        $sql = 'SHOW TABLES LIKE \'' . INVENTORY_TABLE . '\'';
        $res = db_query($sql);
        return (db_num_rows($res) == 0);
    }

    function configureFirstRun() {
        if(!$this->createDBTables()) {
            echo "First run configuration error. " . "Unable to create database tables!";
            return false;
        }

        if(!$this->executeFileCopy()) {
            echo "First run configuration error. " . "Unable to copy necessary files!";
            return false;
        }

        return true;
    }

    function needsUpgrade() {
        $sql = 'SELECT version FROM ' . PLUGIN_TABLE . ' WHERE name=\'Inventory Manager\'';

        if (! ($res = db_query($sql))) {
            return true;
        } else {
            $ht = db_fetch_array($res);
            if($ht['version'] != INVENTORY_PLUGIN_VERSION) {
                return true;
            }
        }
        return false;
    }

    function configureUpgrade() {
        $installer = new \util\InventoryInstaller();

        if(!$installer->upgrade()) {
            echo "Upgrade configuration error. " . "Unable to upgrade database tables!";
        }
    }

    function createDBTables() {
        $installer = new \util\InventoryInstaller();
        return  $installer->install();
    }

    function executeFileCopy() {
        if(!copy(INVENTORY_PLUGIN_ROOT."dispatcher.php", OST_ROOT."scp/dispatcher.php")) {
            return false;
        }
    }

    function pre_uninstall(&$errors) {
        $installer = new \util\InventoryInstaller();
        try {
            $installer->remove();
        } catch(Exception) {}
    }
}

\AssetMysqlSearchBackend::register();

// Recreate the dynamic view after new or removed fields to the inventory form
\Signal::connect('model.created',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField');
\Signal::connect('model.deleted',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField');
// If the `name` column is in the dirty list, we would be renaming a
// column. Delete the view instead.
\Signal::connect('model.updated',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField',
    function($o, $d) { return isset($d['dirty'])
        && (isset($d['dirty']['name']) || isset($d['dirty']['type'])); });