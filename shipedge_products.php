<?php
ini_set('max_execution_time', '0'); // for infinite time of execution


require './config/config.php';
require './functions.php';

$last_data = fetchlastJob($conn); // fetching last record from database
if(count($last_data) > 0) // checking records exist
{
    // setting last record values
    $sku = $last_data[0]['sku_last']; 
    $post_data['from_date'] = $last_data[0]['job_run_at']; 
}
else 
{
    // setting default values
    $sku = '';
    // $post_data['page'] = 1;
}

$post_data['page'] = 1;
$post_data['account_id'] = _ACCOUNT_ID; 
$post_data['key'] = _API_KEY;
$post_data['warehouse'] = _WAREHOUSE;
$products = [];
$curl = curl_init();
// calling api to fetch products. running loops because of we can fetch by passing page value
do {
    $response = make_curl_request(_PRODUCT_API_URL, $post_data, $curl);
    $response = json_decode($response, 1);
    if (sizeof($response['products']) > 0) {
        $products = array_merge($products, $response['products']);
    }
    $post_data['page'] = $post_data['page'] + 1;
} while ($post_data['page'] <= $response['total_pages']);

curl_close($curl);
// var_dump($products);die();
usort($products, 'compare_updated_date');
$new_products = $products;
echo "Total new Products: ".count($new_products)." \n";

// if($sku !== '') // checking last inserted sku 
// {
//     $position = search_sku($products, $sku);
//     $new_products = array_slice($products, $position + 1);
// }
// else {
//     $new_products = $products;
// }
// die(json_encode($new_products));

if (sizeof($new_products) > 0) {
    $job_record_id = AddJobRecord($conn, $post_data['page']);
    $access_token = AuthenticatePlytixAPI();
    
    $curl = curl_init();
    foreach($new_products as $key=>$product)
    {
        $data = [];
        $recordNo = $key + 1; 
        $data['sku'] = $product['sku'];
        $data['attributes']['from_shipedge'] = true;
        $data = json_encode($data, 1);
        echo "Adding product ".$product['sku']." ... \n";
        if($recordNo !== 0 && $recordNo % 10 == 0)
        {
            echo "sleeping..... \n";
            sleep(10);
        }
        CreatePlytixProducts($curl, $data, $access_token, $conn, $job_record_id);
    }

    curl_close($curl);

    updateJobRecord($conn, $data, $response['total_pages'], 'PASS', $job_record_id);

    
} else {
    echo 'no new products found';
}