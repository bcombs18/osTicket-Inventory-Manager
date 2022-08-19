<?php

namespace model;

class AssetExport extends \Export {
    static function saveAssets($sql, $filename, $how='csv') {

        $exclude = array('host_name', 'manufacturer', 'model', 'serial_number', 'location');
        $form = \model\AssetForm::getAssetForm();
        $fields = $form->getExportableFields($exclude, 'cdata.');

        $cdata = array_combine(array_keys($fields),
            array_values(array_map(
                function ($f) { return $f->getLocal('label'); }, $fields)));

        $assets = $sql->models()
            ->select_related('cdata');

        ob_start();
        echo self::dumpQuery($assets,
            array(
                'host_name'  =>          __('Hostname'),
                'manufacturer' =>   __('Manufacturer'),
                'model' =>          __('Model'),
                'serial_number' => __('Serial Number'),
                'location' => __('Location'),
                'assignee' => __('Assignee Metadata')
            ) + $cdata,
            $how,
            array('modify' => function(&$record, $keys, $obj) use ($fields) {
                foreach ($fields as $k=>$f) {
                    if ($f && ($i = array_search($k, $keys)) !== false) {
                        $record[$i] = $f->export($f->to_php($record[$i]));
                    }
                }
                return $record;
            })
        );
        $stuff = ob_get_contents();
        ob_end_clean();

        if ($stuff)
            \Http::download($filename, "text/$how", $stuff);

        return false;
    }
}
