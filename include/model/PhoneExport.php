<?php

namespace model;

class PhoneExport extends \Export {
    static function savePhones($sql, $filename, $how='csv') {

        $exclude = array('phone_model', 'phone_number', 'sim', 'imei');
        $form = \model\PhoneForm::getPhoneForm();
        $fields = $form->getExportableFields($exclude, 'cdata.');

        $cdata = array_combine(array_keys($fields),
            array_values(array_map(
                function ($f) { return $f->getLocal('label'); }, $fields)));

        $assets = $sql->models()
            ->select_related('cdata');

        ob_start();
        echo self::dumpQuery($assets,
            array(
                'phone_model'  =>          __('Hostname'),
                'phone_number' =>   __('Manufacturer'),
                'sim' =>          __('Model'),
                'imei' => __('Serial Number'),
                'phone_assignee' => __('Assignee Metadata')
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
