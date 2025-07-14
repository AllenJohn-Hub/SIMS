<?php
// Create libraries directory if it doesn't exist
if (!file_exists('libraries')) {
    mkdir('libraries', 0777, true);
}

// TCPDF download URL
$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.zip';
$zip_file = 'libraries/tcpdf.zip';

// Download TCPDF
echo "Downloading TCPDF...\n";
file_put_contents($zip_file, file_get_contents($tcpdf_url));

// Extract the zip file
echo "Extracting TCPDF...\n";
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo('libraries/');
    $zip->close();
    
    // Move files from the extracted directory to the correct location
    rename('libraries/TCPDF-6.6.5', 'libraries/tcpdf');
    
    // Remove the zip file
    unlink($zip_file);
    
    echo "TCPDF has been successfully installed!\n";
} else {
    echo "Failed to extract TCPDF\n";
}
?> 