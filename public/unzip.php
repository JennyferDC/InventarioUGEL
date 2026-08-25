<?php
// Script de descompresion temporal para produccion (ubicado en public/)
$zipFile = __DIR__ . '/../release.zip';
$extractTo = __DIR__ . '/../';

if (!file_exists($zipFile)) {
    die("Error: El archivo $zipFile no existe en el servidor.");
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();
    echo "SUCCESS: Descompresion completa.";
} else {
    echo "ERROR: No se pudo abrir el archivo zip.";
}