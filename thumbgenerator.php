<?php

require_once 'config.php';

// Save time start to choose when kill the script
$start_time = time();

// Loop through all files in the upload directory
foreach (glob($UPLOAD_DIR . '*') as $file) {
    $file = basename($file); // Get the file name only

    // Skip if the file is not a regular file
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $ACCEPTED_EXTS))  continue;

    // Load the paths
    $original_path = $UPLOAD_DIR . $file;
    $thumb_path = $THUMB_DIR . $file . '.' . $THUMB_EXT;

    // Skip if the thumbnail already exists
    if (file_exists($thumb_path)) continue;

    // Skip if exists a $thumb_path . '.error' file
    if (file_exists($thumb_path . '.error')) continue;
    
    echo "Processing $file...<br>";

    // Try to create the thumbnail
    try {
        // Create the command to resize the image
        $command = "convert $original_path -resize {$THUMB_MAX_WIDTH}x{$THUMB_MAX_HEIGHT} -quality {$THUMB_QUALITY} $thumb_path";
        
        // Execute the command
        exec($command, $output, $returnVar);
        
        // Check if the command was successful
        if ($returnVar !== 0) {
            throw new Exception("Error resizing image: " . implode("\n", $output));
        }

        // Check if the output file was created
        if (!file_exists($thumb_path)) {
            throw new Exception("Output file was not created.");
        }

        echo "✅ Thumbnail created<br>";
    } catch (Exception $e) {
        // Generate an error file with the error message
        $error_message = $e->getMessage();
        file_put_contents($thumb_path . '.error', $error_message);
        echo "🚨 Error: $error_message<br>";
    }

    echo "**********************<br>";

    // Check if the script has been running for more than 60 seconds
    if (time() - $start_time > 60) {
        // Kill the script
        die("Script has been running for too long. Exiting.");
    }
}

die("All thumbnails processed.");
