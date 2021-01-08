<?php

if ($argc != 3) {
    printf("You must call like:\n\t getMailchimpResponse apikey batchid\n");
    return;
}

$apiKey = $argv[1];
$batchId = $argv[2];

$curl = curl_init();

curl_setopt_array(
    $curl,
    array(
        CURLOPT_URL => "https://api.squalomail.com/#API_ENDPOINT_PATH#/batches/$batchId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_USERPWD => "noname:$apiKey",
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    )
);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    printf("cURL Error #:" . $err);
} else {
    $jsonResponse = json_decode($response, true);

    if ($jsonResponse['status'] == 'finished') {
        $fileUrl = $jsonResponse['response_body_url'];
        // check if the file is not expired
        parse_str($fileUrl, $fileParams);

        try {
            $fd = fopen("$batchId.response.tar.gz", 'w');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fileUrl);
            curl_setopt($ch, CURLOPT_FILE, $fd);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this will follow redirects
            $r = curl_exec($ch);
            curl_close($ch);
            fclose($fd);
            printf("$batchId.response.tar.gz\n");
        } catch (Exception $e) {
            printf($e->getMessage());
        }
    }
}
