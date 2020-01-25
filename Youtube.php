<?php
use GuzzleHttp\Client;

class Youtube {

	private $apiKey = YOUTUBE_API_KEY;
	private $referer = API_REFERER;
	private $url = 'https://www.googleapis.com/youtube/v3/';
	private $client;
	private $videos = array();

	public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param $code
     * @param $type
     * @return null
     *
     * Youtube API türü
     *
     * 0 => Tek video bilgisini çeker
     * 1 => Oynatma listesine ait videoları çeker
     * 2 => Kanala ait videoları çeker
     */
    public function youtubeType($code, $type) {

        $url = filter_var($code, FILTER_VALIDATE_URL) ? true : false;

        if($url == true) {

            $host = parse_url($code, PHP_URL_HOST);
            $path = parse_url($code, PHP_URL_PATH);
            $path = trim($path, '/');

            $parse = parse_url($code, PHP_URL_QUERY);
            $parse = str_replace('&amp;', '&', $parse);
            parse_str($parse, $params);

            if($type == 0) {
                $param = isset($params['v']) ? $params['v'] : null;
                $result = $host == 'youtu.be' ? $path : $param;
            } else if($type == 1) {
                $result = isset($params['list']) ? $params['list'] : null;
            } else if($type == 2) {
                preg_match("/(?:https|http)\:\/\/(?:[\w]+\.)?youtube\.com\/(?:c\/|channel\/|user\/)?([a-zA-Z0-9\-_+]{1,})/", $code, $output_array);
                $result = isset($output_array[1]) ? $output_array[1] : null;
            } else {
                $result = null;
            }
        } else {
            $result = $code;
        }

        return $result;

    }

    /**
     * @param $channel_id
     * @return array
     *
     * Kanala ait tüm videoları çeker.
     */
    public function getChannel($channel_id)
    {
        $this->getChannelVideos($channel_id);
        return $this->videos;
    }

    /**
     * @param $list_id
     * @return array
     *
     * Oynatma listesine ait tüm videoları çeker.
     */
    public function getPlayList($list_id)
    {
        $this->getPlayListVideos($list_id);
        return $this->videos;
    }

    /**
     * @param $id
     * @return array
     *
     * Video bilgisini çeker.
     */
    public function getVideo($id) {

        $params = array(
            'part' => 'snippet',
            'id' => $id,
            'key' => $this->apiKey
        );

        $headers = array(
            'Accept' => 'application/json',
            'Referer' => $this->referer
        );

        $response = $this->client->request('GET', $this->url.'videos', array('query' => $params,
            'headers' => $headers));

        $content = json_decode($response->getBody()->getContents());

        if(!empty($content)) {

            $playlists = isset($content->items) ? $content->items : NULL;

            if($playlists != NULL) {

                foreach ($playlists as $playlist) {
                    array_push($this->videos,
                        array(
                            'id' => isset($playlist->id) ? $playlist->id : NULL,
                            'publishedAt' => isset($playlist->snippet->publishedAt) ? $playlist->snippet->publishedAt : NULL,
                            'title' => isset($playlist->snippet->title) ? $playlist->snippet->title : NULL,
                            'description' => isset($playlist->snippet->description) ? strip_tags($playlist->snippet->description) : NULL,
                            'image_default' => isset($playlist->snippet->thumbnails->default->url) ? $playlist->snippet->thumbnails->default->url : NULL,
                            'image_high' => isset($playlist->snippet->thumbnails->high->url) ? $playlist->snippet->thumbnails->high->url : NULL,
                            'image_standard' => isset($playlist->snippet->thumbnails->standard->url) ? $playlist->snippet->thumbnails->standard->url : NULL,
                            'image_maxres' => isset($playlist->snippet->thumbnails->maxres->url) ? $playlist->snippet->thumbnails->maxres->url : NULL
                        ));
                }
            }
        }

        return $this->videos;
    }

    /**
     * @param $list_id
     * @param null $page_token
     *
     * Oynatma listesine ait tüm videoları çeker. Başlatabilmek için getPlaylist() metodu çalıştırılır.
     */
    private function getPlayListVideos($list_id, $page_token = null) {

        $params = array(
            'part' => 'snippet,contentDetails',
            'playlistId' => $list_id,
            'maxResults' => 50,
            'key' => $this->apiKey
        );

        if($page_token != null) {
            $params = array(
                'part' => 'snippet,contentDetails',
                'playlistId' => $list_id,
                'maxResults' => 50,
                'key' => $this->apiKey,
                'pageToken' => $page_token
            );
        }

        $headers = array(
            'Accept' => 'application/json',
            'Referer' => $this->referer
        );

        $response = $this->client->request('GET', $this->url.'playlistItems', array('query' => $params,
            'headers' => $headers));

        $content = json_decode($response->getBody()->getContents());

        if(!empty($content)) {

            $playlists = isset($content->items) ? $content->items : NULL;

            if($playlists != NULL) {

                $next_page = isset($content->nextPageToken) ? $content->nextPageToken : false;

                foreach ($playlists as $playlist) {
                    array_push($this->videos,
                    array(
                        'id' => isset($playlist->contentDetails->videoId) ? $playlist->contentDetails->videoId : NULL,
                        'publishedAt' => isset($playlist->snippet->publishedAt) ? $playlist->snippet->publishedAt : NULL,
                        'title' => isset($playlist->snippet->title) ? $playlist->snippet->title : NULL,
                        'description' => isset($playlist->snippet->description) ? strip_tags($playlist->snippet->description) : NULL,
                        'image_default' => isset($playlist->snippet->thumbnails->default->url) ? $playlist->snippet->thumbnails->default->url : NULL,
                        'image_high' => isset($playlist->snippet->thumbnails->high->url) ? $playlist->snippet->thumbnails->high->url : NULL,
                        'image_standard' => isset($playlist->snippet->thumbnails->standard->url) ? $playlist->snippet->thumbnails->standard->url : NULL,
                        'image_maxres' => isset($playlist->snippet->thumbnails->maxres->url) ? $playlist->snippet->thumbnails->maxres->url : NULL
                    ));
                }

                if($next_page != false) {
                    $this->getPlayListVideos($list_id, $next_page);
                }
            }
        }
    }

    /**
     * @param $channel_id
     * @param null $page_token
     *
     * Kanala ait tüm videoları çeker. Kullanabilmek için getChannel() metodu başlatılmalıdır.
     */
    private function getChannelVideos($channel_id, $page_token = null) {

        $params = array(
            'part' => 'snippet',
            'channelId' => $channel_id,
            'maxResults' => 50,
            'order' => 'date',
            'type' => 'video',
            'key' => $this->apiKey
        );

        if($page_token != null) {
            $params = array(
                'part' => 'snippet',
                'channelId' => $channel_id,
                'maxResults' => 50,
                'order' => 'date',
                'type' => 'video',
                'key' => $this->apiKey,
                'pageToken' => $page_token
            );
        }

        $headers = array(
            'Accept' => 'application/json',
            'Referer' => $this->referer
        );

        $response = $this->client->request('GET', $this->url.'search', array('query' => $params,
            'headers' => $headers));

        $content = json_decode($response->getBody()->getContents());

        if(!empty($content)) {

            $videos = isset($content->items) ? $content->items : NULL;

            if($videos != NULL) {

                $next_page = isset($content->nextPageToken) ? $content->nextPageToken : false;

                foreach ($videos as $video) {
                    array_push($this->videos,
                        array(
                            'id' => isset($video->id->videoId) ? $video->id->videoId : NULL,
                            'publishedAt' => isset($video->snippet->publishedAt) ? $video->snippet->publishedAt : NULL,
                            'title' => isset($video->snippet->title) ? $video->snippet->title : NULL,
                            'description' => isset($video->snippet->description) ? strip_tags($video->snippet->description) : NULL,
                            'image_default' => isset($video->snippet->thumbnails->default->url) ? $video->snippet->thumbnails->default->url : NULL,
                            'image_high' => isset($video->snippet->thumbnails->high->url) ? $video->snippet->thumbnails->high->url : NULL,
                            'image_standard' => isset($video->snippet->thumbnails->standard->url) ? $video->snippet->thumbnails->standard->url : NULL,
                            'image_maxres' => isset($video->snippet->thumbnails->maxres->url) ? $video->snippet->thumbnails->maxres->url : NULL
                        ));
                }

                if($next_page != false) {
                    $this->getChannelVideos($channel_id, $next_page);
                }
            }
        }

    }
}
