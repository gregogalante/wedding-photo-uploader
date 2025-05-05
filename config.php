<?php

$TITLE = 'Matrimonio di Luca e Giorgia - Album Foto';

$NAMES_DIR = 'names/';
$UPLOAD_DIR = 'uploads/';
$THUMB_DIR = 'thumbs/';

$ACCEPTED_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic'];

$THUMB_EXT = 'jpg';
$THUMB_MAX_WIDTH = 1024;
$THUMB_MAX_HEIGHT = 1024;
$THUMB_QUALITY = 80;

// Create $NAMES_DIR, $UPLOAD_DIR, and $THUMB_DIR if they don't exist
if (!is_dir($NAMES_DIR)) {
    mkdir($NAMES_DIR, 0777, true);
}
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0777, true);
}
if (!is_dir($THUMB_DIR)) {
    mkdir($THUMB_DIR, 0777, true);
}
