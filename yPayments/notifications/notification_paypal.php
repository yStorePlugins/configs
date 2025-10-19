<?php

    const HOST = "localhost";
    const USER = "id21188874_ystoreuser";
    const PASSWORD = "yStorePass1.";
    const DB = "id21188874_ystoredb";
    const EMAIL = "<PAYPAL_EMAIL>";

    $content = trim(file_get_contents("php://input"));
    $payBody = json_decode($content);

    $reference = $_POST['custom'];
    $email = $_POST['payer_email'];
    $receiver_email = $_POST['receiver_email'];
    $code = $_POST['txn_id'];
    $gross = $_POST['mc_gross'];
    $amount = $_POST['mc_gross'] - $_POST['mc_fee'];
    $status = $_POST['payment_status'];
    $name = $_POST['first_name'] . " " . $_POST['last_name'];
    $paid = ($status == 'Completed') ? 1 : 0;

    if ($receiver_email != EMAIL) {
        return;
    }

    $conn = new mysqli(HOST, USER, PASSWORD, DB);

    if ($conn->connect_error) {
        http_response_code(500);
        $conn->close();
        return;
    }

    if ($gross < 0){
        $gross = $gross * -1;
    }

    $key = $reference;
    $json = json_encode(array("key"=>$key,"status"=>$status,"gateway"=>"paypal","gross"=>$gross));

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
  
?>