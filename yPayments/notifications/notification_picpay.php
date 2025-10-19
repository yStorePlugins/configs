<?php

    const HOST = "localhost";
    const USER = "id21188874_ystoreuser";
    const PASSWORD = "yStorePass1.";
    const DB = "id21188874_ystoredb";
    const TOKEN = "<ACCESS_TOKEN>";

    $content = trim(file_get_contents("php://input"));
    $payBody = json_decode($content);

    $reference = "";
    $authorizationId = "";
    $status = "";

    if(isset($payBody->authorizationId)){

        $referenceId = $payBody->referenceId;

        $ch = curl_init('https://appws.picpay.com/ecommerce/public/payments/'.$referenceId.'/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-picpay-token: '.TOKEN));

        $res = curl_exec($ch);
        curl_close($ch);

        $notification = json_decode($res);

        $reference = $payBody->referenceId;
        $authorizationId = $payBody->authorizationId;
        $status = $notification->status;

    }

    if ($status === "paid" || $status === "completed" || $status === "refunded" || $status === "chargeback") {

         $conn = new mysqli(HOST, USER, PASSWORD, DB);

         if ($conn->connect_error) {
             http_response_code(500);
             $conn->close();
             return;
         }

         $key = $reference;
         $json = json_encode(array("key"=>$key,"status"=>$status,"gateway"=>"picpay","gross"=>0));

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