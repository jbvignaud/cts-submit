#!/bin/php
<?php
	
	$cert = file_get_contents($argv[1]);
	$inter = file_get_contents($argv[2]);

	$chain=array();
	$chain[]=x509_pem2der64($cert);
	$chain[]=x509_pem2der64($inter);
	$payload["chain"] = $chain;
	$payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 1);

	$servers=array();
	$servers[]="log.certly.io";
	$servers[]="ct.googleapis.com/aviator";
	$servers[]="ct.googleapis.com/pilot";
	$servers[]="ct.googleapis.com/rocketeer";
	/* those do not handle let's encrypt root yet */
	/*
	$servers[]="ct1.digicert-ct.com/log";
	$servers[]="ct.izenpe.com";
	$servers[]="ct.ws.symantec.com";
	*/
	$servers[]="ctlog.api.venafi.com";

	$scts=array();

	foreach ($servers as $server)
	{
		$content = post_data_to_log($curl_handle, "https://" . $server, "/ct/v1/add-chain", $payload_json);
		$obj = json_decode($content);
		$scts[] = pack_sct ($obj);
	}

	$tls_extention = pack_tls_extention ($scts);

	$tls_extention_pem = "-----BEGIN SERVERINFO FOR EXTENSION 18-----\n" . wordwrap(base64_encode($tls_extention), 64, "\n", true) . "\n-----END SERVERINFO FOR EXTENSION 18-----\n";
	echo $tls_extention_pem;
	
	


function x509_pem2der64($pem_data)
{
	$begin = "BEGIN CERTIFICATE-----";
	$end   = "-----END";
	$pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
	$pem_data = substr($pem_data, 0, strpos($pem_data, $end));
	$der = base64_decode($pem_data);
	$enc = base64_encode($der);
	return $enc;
}

function post_data_to_log($curl_handle, $host, $path, $data)
{
        curl_setopt($curl_handle, CURLOPT_URL, $host.$path);
        curl_setopt($curl_handle, CURLOPT_HTTPGET, true);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, NULL);
        $content = curl_exec($curl_handle);
        return ($content);
}

function pack_sct ($sct)
{
	$sct_version = pack("C", $sct->sct_version);
	$id = base64_decode($sct->id);
	$timestamp = pack("J", $sct->timestamp);
	$extensions = base64_decode($sct->extensions);
	$extensions_len = pack("n", strlen($extensions));
	$signature = base64_decode($sct->signature);

	$sct=$sct_version.$id.$timestamp.$extensions_len.$extensions.$signature;

	return ($sct);
}

function pack_tls_extention ($sct_array)
{
	$sct_list="";
	foreach ($sct_array as $sct)
	{
		$sct_list=$sct_list.pack("n", strlen($sct)).$sct;
	}
	$sct_list_len = pack("n", strlen($sct_list));

	$tls_extention_content = $sct_list_len . $sct_list;
	$tls_extention_content_len = strlen($tls_extention_content);
	$tls_extention = pack("n", 18) . pack("n", $tls_extention_content_len) . $tls_extention_content;

	return $tls_extention;
}
?>
