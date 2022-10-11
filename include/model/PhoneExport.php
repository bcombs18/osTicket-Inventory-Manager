<?php

namespace model;

class PhoneExport extends \Export {
    static function savePhones($sql, $filename, $how='csv') {

        $exclude = array('phone_model', 'phone_number', 'imei');
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
                'phone_model'  =>          __('Model'),
                'phone_number' =>   __('Phone Number'),
                'imei' => __('IMEI'),
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
