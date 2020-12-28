<?php
define("_SERVER_NAME", "localhost");
define("_USER_NAME", "root");
define("_DB_PASSWORD", "");
define("_DB_NAME", "shipedge_api");

// Shipedge Account Details CONSTANTS
define('_ACCOUNT_ID', '6');
define('_API_KEY', '617a24bf4367ad898907b008ac29bc6c');
define('_WAREHOUSE', 'shalooh');
define('_PRODUCT_API_URL', 'http://alphaintegration.shipedge.com/API/Rest/v2/Inventory/get_products');

define('_CA_CERT', 'C:\cacert\cacert.pem');
// plytix Account Details CONSTANTS
define('_PLYTIX_API_KEY', 'HLW0UBWWP1NN0NE5NPI3');
define('_PLYTIX_API_PASSWORD', '!$w/PCPA/TZ199Qx0wvQ0=DTLIyMLqX@.7b%%vI0');

$date = date('Y-m-d H:i:s');
try {

    $conn = new PDO("mysql:host="._SERVER_NAME.";dbname="._DB_NAME, _USER_NAME, _DB_PASSWORD);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

    echo "Connection failed: " . $e->getMessage();
    die();

}