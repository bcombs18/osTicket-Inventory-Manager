<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INVENTORY_INCLUDE_DIR . 'model/Asset.php';

class AssetApiController extends ApiController {
    function getRequestStructure($format, $data = null) {
        $supported = array(
            "hostname",
            "manufacturer",
            "model",
            "serial",
            "location",
            "assignee"
        );

        if(($form = \model\AssetForm::getInstance()))
            foreach($form->getFields() as $field)
                $supported[] = $field->get('name');

        return $supported;
    }

    function validate(&$data, $format, $strict=true) {
        global $ost;

        if(!parent::validate($data, $format, $strict) && $strict)
            $this->exerr(400, __('Unexpected or invalid data received'));

        return true;
    }

    function create($format) {
        if(!($key=$this->requireApiKey()) || !$key->canCreateAssets())
            return $this->exerr(401, __('API key not authorized'));

        $asset = null;
        $asset = $this->createAsset($this->getRequest($format));

        if(!$asset) {
            return $this->exerr(500, __('Unable to create new asset: unknown error'));
        }

        $this->response(201, $asset->getHostname());
    }

    function createAsset($data) {
        $errors = array();

        $asset = \model\Asset::fromVars($data, true, false);

        if(count($errors)) {
            if(isset($errors['errno']) && $errors['errno'] == 403)
                return $this->exerr(403, __('Asset denied'));
            else
                return $this->exerr(400, __("Unable to create new asset: validation errors").":\n"
                .Format::array_implode(": ", "\n", $errors));
        } elseif(!$asset) {
            return $this->exerr(500, __("Unable to create new asset: unknown error"));
        }

        return $asset;
    }
}