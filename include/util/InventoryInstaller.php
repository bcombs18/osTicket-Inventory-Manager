<?php

namespace util;

require_once 'class.setup.php';
require_once INVENTORY_MODEL_DIR.'AssetForm.php';

class InventoryInstaller extends \SetupWizard {
    function install() {
        $schemaFile = INVENTORY_PLUGIN_ROOT . 'install/sql/install_inventory.sql';
        if(!$this->runJob($schemaFile)) {
            return false;
        } elseif (\model\AssetForm::ensureDynamicDataViews()) {
            return false;
        }
        return true;
    }

    function upgrade() {
        $schemaFile = INVENTORY_PLUGIN_ROOT . 'install/sql/upgrade_inventory.sql'; // DB dump.
        return $this->runJob ( $schemaFile, false );
    }

    private function runJob($schemaFile, $show_sql_errors = false) {
        if(! file_exists($schemaFile)) {
            echo '<br/>';
            var_dump($schemaFile);
            echo '<br/>';
            echo 'File Access Error - please make sure your douwnload is the latest (#1)';
            echo '<br/>';
            $this->error = 'File Access Error!';
            return false;
        } elseif(!$this->load_sql_file($schemaFile, TABLE_PREFIX, true, true)) {
            if($show_sql_errors) {
                echo '<br/>';
                echo 'Error parsing SQL schema! Get help from developers (#4)';
                echo '<br/>';
                return false;
            }
            return true;
        }
        return true;
    }

    function remove() {
        $schemaFile = INVENTORY_PLUGIN_ROOT . 'install/sql/remove_inventory.sql';
        return $this->runJob($schemaFile);
    }

    function purgeData() {
        $schemaFile = INVENTORY_PLUGIN_ROOT . 'install/sql/purge_inventory_data.sql';
        return $this->runJob($schemaFile);
    }

    function load_sql($schema, $prefix, $abort = true, $debug = false) {
        $schema = preg_replace('%^\s*(#|--).*$%m', '', $schema);
        $schema = str_replace('%TABLE_PREFIX%', $prefix, $schema);

        if(!($statements = array_filter(array_map('trim',
            preg_split("/\\$(?=(?:[^']*'[^']*')*[^']*$)/", $schema)))))
            return $this->abort('Error parsing SQL schema', $debug);

        db_query('SET SESSION SQL_MODE=""', false);
        foreach($statements as $k => $sql) {
            if(db_query($sql, false)) {
                continue;
            }
            if(db_error() != null) {
                $error = "[$sql] " . db_error();
                if($abort)
                    return $this->abort($error, $debug);
            }
        }
        return true;
    }
}