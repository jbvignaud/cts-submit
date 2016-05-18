#!/usr/bin/php
<?php
	
	$sct1 = file_get_contents($argv[1]);
	$sct2 = file_get_contents($argv[2]);
	$sct1_tls_der = sct_get_der($sct1);
	$sct2_tls_der = sct_get_der($sct2);

	$sct_list = array();
	sct_get_tls ($sct1_tls_der,  $sct_list);
	sct_get_tls ($sct2_tls_der,  $sct_list);

	$tls_extention = pack_tls_extention ($sct_list);

        $tls_extention_pem = "-----BEGIN SERVERINFO FOR EXTENSION 18-----\n" . wordwrap(base64_encode($tls_extention), 64, "\n", true) . "\n-----END SERVERINFO FOR EXTENSION 18-----\n";
        echo $tls_extention_pem;

function sct_get_der($pem_data)
{
	$begin = "BEGIN SERVERINFO FOR EXTENSION 18-----";
	$end   = "-----END";
	$pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
	$pem_data = substr($pem_data, 0, strpos($pem_data, $end));
	$der = base64_decode($pem_data);
	return $der;
}

function sct_get_tls ($sct, &$scts)
{
	$tls_ext_version = unpack("n", substr($sct, 0,2));
	$tls_ext_len = unpack("n", substr($sct, 2,2));
	$sct_list_len = unpack("n", substr($sct, 4,2));
	$list_len = $sct_list_len[1];
	$i=0;
	$offset=6;
	while (strlen(substr($sct, $offset, 1)) > 0)
	{
		$sct_data=array();
		$sct_len = unpack("n", substr($sct, $offset,2));
		$offset+=2;
		$sct_data["len"] = $sct_len[1];
		$sct_data["data"] = substr($sct, $offset, $sct_len[1]);
		$scts[] = $sct_data;

		$sct_version =  unpack("C", substr($sct, $offset, 1));
		$offset++;
		$offset+=32;
		$sct_timestamp =  unpack("J", substr($sct, $offset, 8));
		$offset+=8;
		$sct_ext_len =  unpack("n", substr($sct, $offset, 2));
		$offset+=2;
		$sct_hash_alg =  unpack("C", substr($sct, $offset, 1));
		$offset+=1;
		$sct_sig_alg =  unpack("C", substr($sct, $offset, 1));
		$offset+=1;
		$sct_sig_len = unpack("n", substr($sct, $offset,2));
		$offset+=2;
		$offset+=$sct_sig_len[1];
	}
}

function pack_tls_extention ($sct_array)
{
        $sct_list="";
        foreach ($sct_array as $sct)
        {
                $sct_list=$sct_list.pack("n",  $sct["len"]).$sct["data"];
        }
        $sct_list_len = pack("n", strlen($sct_list));

        $tls_extention_content = $sct_list_len . $sct_list;
        $tls_extention_content_len = strlen($tls_extention_content);
        $tls_extention = pack("n", 18) . pack("n", $tls_extention_content_len) . $tls_extention_content;

        return $tls_extention;
}

?>
