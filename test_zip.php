<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP version: " . phpversion() . "<br>";
if (class_exists('ZipArchive')) {
    echo "ZipArchive exists!<br>";
    $zipFile = 'release.zip';
    if (file_exists($zipFile)) {
        echo "release.zip exists! Size: " . filesize($zipFile) . " bytes<br>";
        
        $zip = new ZipArchive;
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            echo "Successfully opened release.zip!<br>";
            $zip->close();
        } else {
            echo "Failed to open release.zip. Code: $res<br>";
        }
    } else {
        echo "release.zip DOES NOT exist!<br>";
    }
} else {
    echo "ZipArchive DOES NOT exist!<br>";
}
