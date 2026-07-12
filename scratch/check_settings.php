<?php
require_once('../../../wp-load.php');
$settings = get_option('evo_flex_settings');
header('Content-Type: application/json');
echo json_encode($settings, JSON_PRETTY_PRINT);
