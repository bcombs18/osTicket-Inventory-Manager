<?php

// Use sessions — it's important for SSO authentication, which uses
// /api/auth/ext
define('DISABLE_SESSION', false);

require 'api.inc.php';

# Include the main api urls
require_once INCLUDE_DIR."class.dispatcher.php";

$dispatcher = patterns('',
    url_post("^/assets\.(?P<format>xml|json)$", array('api.assets.php:AssetApiController', 'create'))
);

Signal::send('api', $dispatcher);

# Call the respective function
print $dispatcher->resolve(Osticket::get_path_info());
?>