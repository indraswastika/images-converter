<?php
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headerRow = intval($_POST['headerRow'] ?? 0);
    $chunkSize = intval($_POST['chunkSize'] ?? 0);

    if (!isset($_FILES['csvFile']) || $headerRow <= 0 || $chunkSize <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid input."]);
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/';
    $zipDir = __DIR__ . '/zip/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!file_exists($zipDir)) mkdir($zipDir, 0777, true);

    $tmpName = $_FILES['csvFile']['tmp_name'];
    $originalName = pathinfo($_FILES['csvFile']['name'], PATHINFO_FILENAME);
    $csvData = array_map('str_getcsv', file($tmpName));

    $headerIndex = $headerRow - 1;
    if (!isset($csvData[$headerIndex])) {
        echo json_encode(["success" => false, "message" => "Header row not found."]);
        exit;
    }

    $header = $csvData[$headerIndex];
    $content = array_slice($csvData, $headerRow);
    $totalRows = count($content);
    $totalChunks = ceil($totalRows / $chunkSize);
    $fileList = [];

    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkData = array_slice($content, $i * $chunkSize, $chunkSize);
        $chunkFilename = $uploadDir . $originalName . ' ' . ($i + 1) . '.csv';

        $fp = fopen($chunkFilename, 'w');
        fputcsv($fp, $header);
        foreach ($chunkData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $fileList[] = $chunkFilename;
    }

    $zipFilename = $zipDir . $originalName . "_split_" . time() . ".zip";
    $zip = new ZipArchive();
    if ($zip->open($zipFilename, ZipArchive::CREATE) !== TRUE) {
        echo json_encode(["success" => false, "message" => "Failed to create ZIP."]);
        exit;
    }

    foreach ($fileList as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();

    foreach ($fileList as $file) {
        unlink($file);
    }

    echo json_encode(["success" => true, "download" => "zip/" . basename($zipFilename)]);
}
