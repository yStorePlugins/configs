<?php

    const HOST = "localhost";
    const USER = "id21188874_ystoreuser";
    const PASSWORD = "yStorePass1.";
    const DB = "id21188874_ystoredb";
    const ACCESS_TOKEN = "<ACCESS_TOKEN>";

    if ( ($_SERVER["REQUEST_METHOD"] != "POST") || (!(isset($_GET['data_id'])) && !(isset($_GET['type']))) || ($_GET['type'] != "payment")) {
         http_response_code(500);
         return;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $_GET['data_id'],
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . ACCESS_TOKEN)
    ));

    $payment = json_decode(curl_exec($curl), true);
    if ($payment["status"] === "approved" || $payment["status"] === "in_mediation" || $payment["status"] === "refunded" || $payment["status"] === "charged_back") {

         $conn = new mysqli(HOST, USER, PASSWORD, DB);

         if ($conn->connect_error) {
             http_response_code(500);
             $conn->close();
             return;
         }

         $status = $payment["status"];
         $key = $payment['external_reference'];

         $json = json_encode(array("key"=>$key,"status"=>$status,"gateway"=>"mercadopago","gross"=>0));

         $result = $conn->query("SELECT * FROM `ypayments.orders.status` WHERE `key` = '" . $key . "'");

         if($result->num_rows == 0) {
              $sqlQuery = "INSERT INTO `ypayments.orders.status` (`key`, `json`) VALUES ('" . $key . "', '" . $json . "');";
         } else {
              $sqlQuery = "UPDATE `ypayments.orders.status` SET `json` = '" . $json . "' WHERE `key` = '" . $key . "'";
         }

         if ($conn->query($sqlQuery)) {
             $conn->close();
             http_response_code(201);
             return;
         }

         http_response_code(500);
         $conn->close();
         die();
    }
  
?>