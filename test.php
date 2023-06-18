<?php
$creds = array(apache_getenv('DB_HOST'), apache_getenv('DB_USER'), apache_getenv('DB_PASS'), apache_getenv('DB_NAME'));
var_dump($creds);