<?php

function make_curl_request($url, $post_data, $curl)
{
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($post_data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);


    return $response;
} // function ends here

function search_sku($exif, $field)
{
    foreach ($exif as $key => $data) {
        if ($data['sku'] == $field)
            return $key;
    }
} // function ends here

function fetchlastJob($db)
{
    $sql = "SELECT * FROM job_record WHERE UPPER(status) = UPPER('pass') ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
} // function ends here

function AuthenticatePlytixAPI()
{
    try {
        $curl = curl_init();
        // Check if initialization had gone wrong*
        if ($curl === false) {
            throw new Exception('failed to initialize');
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://auth.plytix.com/auth/api/get-token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CAINFO => _CA_CERT,
            CURLOPT_CAPATH => _CA_CERT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "api_key" : "' . _PLYTIX_API_KEY . '",
                "api_password" : "' . _PLYTIX_API_PASSWORD . '"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);


        // Check the return value of curl_exec(), too
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);
        $response = json_decode($response, 1);
        return  $response['data'][0]['access_token'];
    } catch (Exception $e) {

        trigger_error(
            sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            E_USER_ERROR
        );
    }
} // function ends here

function CreatePlytixProducts($curl, $data, $access_token, $db, $job_record_id)
{

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://pim.plytix.com/api/v1/products',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_CAINFO => _CA_CERT,
        CURLOPT_CAPATH => _CA_CERT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $access_token",
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $http_code = $info['http_code'];

    if ($http_code != 201) {
        addError($data, $db, $job_record_id, $response);
    }

} // function ends here

function addError($data, $db, $job_record_id, $error)
{
    $data = json_decode($data, 1);
    $sql = 'INSERT INTO importerror(job_record_id, sku, error) VALUES (:job_record_id, :sku, :error)';

    $stmt = $db->prepare($sql);
    $stmt->bindValue('job_record_id', $job_record_id);
    $stmt->bindValue('sku', $data['sku']);
    $stmt->bindValue('error', $error);
    $stmt->execute();
} // function ends here

function AddJobRecord($db, $page)
{
    $sql = 'INSERT INTO job_record(sku_last, page, job_run_at, status) VALUES (NULL, :page, :job_run_at, NULL)';

    $stmt = $db->prepare($sql);
    $stmt->bindValue('page', $page);
    $stmt->bindValue('job_run_at', date('Y-m-d H:i:s'));
    $stmt->execute();

    $lastID = $db->lastInsertId();

    return $lastID;
} // function ends here

function updateJobRecord($db, $data, $page, $status, $job_record_id)
{
    $data = json_decode($data, 1);
    $sql = "UPDATE job_record
            SET 
            sku_last = :sku_last,
            page = :page,
            status = :status
            WHERE id = :id
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('sku_last', $data['sku']);
    $stmt->bindValue('page', $page);
    $stmt->bindValue('status', $status);
    $stmt->bindValue('id', $job_record_id);
    $stmt->execute();
} // function ends here

function AddPlytixAttribute($curl, $access_token)
{
    echo "--Adding Shipedge Attribute..... \n";
    $data['name'] = 'From_ShipEdge';
    $data['type_class'] = 'BooleanAttribute';
    $data = json_encode($data);
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://pim.plytix.com/api/v1/attributes/product',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_CAINFO => _CA_CERT,
        CURLOPT_CAPATH => _CA_CERT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $access_token",
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
} // function ends here


function compare_updated_date($a, $b)
{
    if(strtotime($a['updated_date']) == strtotime($b['updated_date'])){
        return 0;
    }

    return (strtotime($a['updated_date']) < strtotime($b['updated_date'])) ? -1 : 1;
}// function ends here

