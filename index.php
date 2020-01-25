<?php
/**
 * Developer: ONUR KAYA
 * Contact: empatisoft@gmail.com
 */

define('DIR', DIRECTORY_SEPARATOR);
define('ROOT', $_SERVER['DOCUMENT_ROOT'].DIR);

define('YOUTUBE_API_KEY', 'API Anahtarı');
define('API_REFERER', 'Güvenlik doğrulaması için referans');

require_once ROOT.'vendor'.DIR.'autoload.php';
require_once ROOT.'Youtube.php';

$videos = null;

/**
 * 0: Tek video
 * 1: Oynatma Listesi
 * 2: Kanal
 */
$video_source_type = 0;

/**
 * Türe göre VideoID, PlaylistID veya ChannelID değerleri verilmelidir.
 * Tam adres de verebilirsiniz. Parse edecektir.
 */
$video_code = 'm02fJvOMZa8';


$youtube = new Youtube();
$code = $youtube->youtubeType($video_code, $video_source_type);

$videos = NULL;

if($video_source_type == 0)
    $videos = $youtube->getVideo($code);
else if($video_source_type == 1)
    $videos = $youtube->getPlayList($code);
else if($video_source_type == 2)
    $videos = $youtube->getChannel($code);

echo '<pre>';
print_r($videos);