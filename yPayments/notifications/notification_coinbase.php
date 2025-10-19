<?php

    const HOST = "localhost";
    const USER = "id21188874_ystoreuser";
    const PASSWORD = "yStorePass1.";
    const DB = "id21188874_ystoredb";
    const VERSION = "<ACCESS_TOKEN>";
    const TOKEN = "<ACCESS_TOKEN>";

    $content = trim(file_get_contents("php://input"));
    $payBody = json_decode($content, true);

    $status = "";

    $conn = new mysqli(HOST, USER, PASSWORD, DB);
    $encoded = json_encode($payBody);

    if (isset($payBody['event'])) {
        $code = $payBody['event']['data']['code'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.commerce.coinbase.com/charges/' . $code,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-CC-Version: ' . VERSION,
                    'X-CC-Api-Key: ' . TOKEN
              ),
        ));

        $res = curl_exec($curl);
        curl_close($curl);

        $notification = json_decode($res);

        $jsonArray = $notification->data->timeline;
        $statusObject = $jsonArray[count($jsonArray) - 1];

        $status = $statusObject->status;

        if ($status === "expired" || $status === "completed" || $status === "cancelled" || $status === "refunded") {

            $key = $code;
            $json = json_encode(array("key"=>$key,"status"=>$status,"gateway"=>"coinbase","gross"=>0));

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

    }

?>