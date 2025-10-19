<?php

    const HOST = "localhost";
    const USER = "id21188874_ystoreuser";
    const PASSWORD = "yStorePass1.";
    const DB = "id21188874_ystoredb";
    const TOKEN = "<SECRET_TOKEN>";

    $content = trim(file_get_contents("php://input"));
    $payBody = json_decode($content, true);

    $status = "";

    $encoded = json_encode($payBody);

    if (isset($payBody['data'])) {
        $type = $payBody['type'];

        if ($type == "charge.refunded"){

            $paymentIntent = $payBody['data']['object']['payment_intent'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.stripe.com/v1/checkout/sessions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer ' . TOKEN
                ),
            ));

            $res = curl_exec($curl);
            curl_close($curl);

            $notification = json_decode($res);

            $toReturnId = "none";

            foreach($notification->data as $objectData) {
                $intent = $objectData->payment_intent;

                if ($intent != null && $intent == $paymentIntent){
                    $toReturnId = $objectData->id;
                    break;
                }

            }

            if ($toReturnId == "none"){
                http_response_code(201);
                return;
            }

            $key = $toReturnId;
            $json = json_encode(array("key"=>$key,"status"=>"refunded","gateway"=>"stripe","gross"=>0));

            $conn = new mysqli(HOST, USER, PASSWORD, DB);
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

            return;
        }

        $code = $payBody['data']['object']['id'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.stripe.com/v1/checkout/sessions/' . $code,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer ' . TOKEN
              ),
        ));

        $res = curl_exec($curl);
        curl_close($curl);

        $notification = json_decode($res);

        $status = $notification->payment_status;

        if ($status === "paid") {

            $key = $code;
            $json = json_encode(array("key"=>$key,"status"=>$status,"gateway"=>"stripe","gross"=>0));

            $conn = new mysqli(HOST, USER, PASSWORD, DB);
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