<?php

namespace controller;

require_once INCLUDE_DIR . 'class.export.php';
require_once INCLUDE_DIR . 'class.ajax.php';

class Export extends \AjaxController {

    function check($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Agent login is required');
        elseif (!($exporter=\Exporter::load($id)) || !$exporter->isAvailable())
            \Http::response(404, 'No such export');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($exporter->isReady())
                \Http::response(201, $this->json_encode([
                    'status' => 'ready',
                    'href' => sprintf('/osTicket/scp/export.php?id=%s',
                        $exporter->getId()),
                    'filename' => $exporter->getFilename()]));
            else // Export is not ready... checkback in a few
                \Http::response(200, $this->json_encode([
                    'status' => 'notready']));
        }

        include INVENTORY_VIEWS_DIR . 'export.tmpl.php';
    }
}