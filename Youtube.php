<?php
/**
 * Author: Onur KAYA
 * E-mail: empatisoft@gmail.com
 * Date: 02.01.2022 00:36
 */
namespace Empatisoft\Api\Youtube;

class Youtube {

    private string $url = 'https://youtube.googleapis.com/youtube/v3/';
    private string $api_key;
    private Request $request;
    private Response $response;
    private array $videos = [];
    private array $channels = [];

    public function __construct(string $api_key)
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->api_key = $api_key;
    }

    public function get(string $url)
    {
        $check = (bool)filter_var($url, FILTER_VALIDATE_URL);
        if($check == true) {
            $path = trim(parse_url($url, PHP_URL_PATH), '/');
            $query = parse_url($url, PHP_URL_QUERY);
            $query = str_replace('&amp;', '&', $query);
            parse_str($query, $params);
            if(isset($params['list'])) {
                $type = 'playlist';
                $code = $params['list'];
            } else if($path == 'watch') {
                $type = 'video';
                $code = $params['v'] ?? null;
            } else if(preg_match('/c\/(.*)/', $path) || preg_match('/user\/(.*)/', $path)) {
                $path = str_replace('c/', '', $path);
                $path = str_replace('user/', '', $path);
                $type = 'channel';
                $this->searchChannel($path, false);
                $code = $this->channels[0]['id'] ?? null;
            } else if(preg_match('/channel\/(.*)/', $path)) {
                $path = str_replace('channel/', '', $path);
                $type = 'channel';
                $code = $path;
            } else {
                $type = 'video';
                $code = $path;
            }
        } else {
            if(substr($url, 0, 2) === 'UC') {
                $type = 'channel';
                $code = $url;
            } else if(substr($url, 0, 2) === 'PL') {
                $type = 'playlist';
                $code = $url;
            } else {
                $type = 'video';
                $code = $url;
            }
        }

        if($type == 'channel')
            $this->getChannel($code);
        else if($type == 'playlist')
            $this->getPlaylist($code);
        else
            $this->getVideo($code);
    }

    /**
     * @param string $video_id
     * Belirtilen video bilgisini döndürür.
     */
    private function getVideo(string $video_id) {
        $response = $this->request->getRequest($this->url.'videos?part=id,snippet&id='.$video_id.'&maxResults=1&key='.$this->api_key);
        $result = [];
        $video = $response['items'][0] ?? null;
        if($video != null) {
            $result = [
                'id' => $video_id,
                'published_at' => $video['snippet']['publishedAt'] ?? null,
                'title' => $video['snippet']['title'] ?? null,
                'description' => $video['snippet']['description'] ?? null,
                'images' => $this->setImages($video)
            ];
        }
        $this->response->json($result);
    }

    /**
     * @param string $id
     * Bir oynatma listesine ait tüm videoları listeler.
     */
    private function getPlaylist(string $id) {
        $this->setPlaylist($this->url.'playlistItems?part=id,snippet&playlistId='.$id.'&maxResults=50&key='.$this->api_key);
        $this->response->json($this->videos);
    }

    /**
     * @param string $id
     * Bir kanala ait tüm videoları listeler.
     */
    private function getChannel(string $id) {
        $this->setChannel($this->url.'search?order=date&type=video&part=snippet&channelId='.$id.'&maxResults=50&key='.$this->api_key);
        $this->response->json($this->videos);
    }

    /**
     * @param string $query
     * Belirtilen kritere göre kanalları arar.
     */
    private function searchChannel(string $query, bool $json = true) {
        $this->setChannelList($this->url.'search?type=channel&part=snippet&q='.$query.'&maxResults=50&key='.$this->api_key);
        if($json == true)
            $this->response->json($this->channels);

    }

    /**
     * @param string $url
     * @param string $nextPageToken
     * Oynatma listesindeki tüm videoları çeker.
     */
    private function setPlaylist(string $url, string $nextPageToken = '') {
        if($nextPageToken != '') {
            if (strpos($url, 'pageToken') !== false)
                $url = preg_replace('/&pageToken=(.*)/', '$1', $url);

            $url = $url.'&pageToken='.$nextPageToken;
        }
        $response = $this->request->getRequest($url);
        if(!empty($response['items'])) {
            foreach ($response['items'] as $item) {
                $this->videos[] = [
                    'id' => $item['snippet']['resourceId']['videoId'] ?? null,
                    'published_at' => $item['snippet']['publishedAt'] ?? null,
                    'title' => $item['snippet']['title'] ?? null,
                    'description' => $item['snippet']['description'] ?? null,
                    'images' => $this->setImages($item)
                ];
            }
        }

        if(isset($response['nextPageToken']))
            $this->setPlaylist($url, $response['nextPageToken']);
    }

    /**
     * @param string $url
     * @param string $nextPageToken
     * Kanala ait tüm videoları çeker.
     */
    private function setChannel(string $url, string $nextPageToken = '') {
        if($nextPageToken != '') {
            if (strpos($url, 'pageToken') !== false)
                $url = preg_replace('/&pageToken=(.*)/', '$1', $url);

            $url = $url.'&pageToken='.$nextPageToken;
        }
        $response = $this->request->getRequest($url);
        if(!empty($response['items'])) {
            foreach ($response['items'] as $item) {
                $this->videos[] = [
                    'id' => $item['id']['videoId'] ?? null,
                    'published_at' => $item['snippet']['publishedAt'] ?? null,
                    'title' => $item['snippet']['title'] ?? null,
                    'description' => $item['snippet']['description'] ?? null,
                    'images' => $this->setImages($item)
                ];
            }
        }

        if(isset($response['nextPageToken']))
            $this->setChannel($url, $response['nextPageToken']);
    }

    /**
     * @param string $url
     * @param string $nextPageToken
     * Aranan kelimeye göre kanalları listeler.
     */
    private function setChannelList(string $url, string $nextPageToken = '') {
        if($nextPageToken != '') {
            if (strpos($url, 'pageToken') !== false)
                $url = preg_replace('/&pageToken=(.*)/', '$1', $url);

            $url = $url.'&pageToken='.$nextPageToken;
        }
        $response = $this->request->getRequest($url);
        if(!empty($response['items'])) {
            foreach ($response['items'] as $item) {
                $this->channels[] = [
                    'id' => $item['snippet']['channelId'] ?? null,
                    'published_at' => $item['snippet']['publishedAt'] ?? null,
                    'title' => $item['snippet']['title'] ?? null,
                    'channel_title' => $item['snippet']['channelTitle'] ?? null,
                    'description' => $item['snippet']['description'] ?? null,
                    'images' => [
                        'default' => $item['snippet']['thumbnails']['default']['url'] ?? null,
                        'medium' => $item['snippet']['thumbnails']['medium']['url'] ?? null,
                        'high' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                        'standard' => $item['snippet']['thumbnails']['standard']['url'] ?? null,
                        'maxres' => $item['snippet']['thumbnails']['maxres']['url'] ?? null
                    ]
                ];
            }
        }

        if(isset($response['nextPageToken']))
            $this->setChannelList($url, $response['nextPageToken']);
    }

    private function setImages($item): array
    {
        $images = [
            'default' => null,
            'medium' => null,
            'high' => null,
            'standard' => null,
            'maxres' => null
        ];
        $default = $item['snippet']['thumbnails']['default']['url'] ?? null;
        if($default != null) {
            $images['default'] = [
                'url' => $item['snippet']['thumbnails']['default']['url'] ?? null,
                'width' => $item['snippet']['thumbnails']['default']['width'] ?? null,
                'height' => $item['snippet']['thumbnails']['default']['height'] ?? null
            ];
        }
        $medium = $item['snippet']['thumbnails']['medium']['url'] ?? null;
        if($medium != null) {
            $images['medium'] = [
                'url' => $item['snippet']['thumbnails']['medium']['url'] ?? null,
                'width' => $item['snippet']['thumbnails']['medium']['width'] ?? null,
                'height' => $item['snippet']['thumbnails']['medium']['height'] ?? null
            ];
        }
        $high = $item['snippet']['thumbnails']['high']['url'] ?? null;
        if($high != null) {
            $images['high'] = [
                'url' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                'width' => $item['snippet']['thumbnails']['high']['width'] ?? null,
                'height' => $item['snippet']['thumbnails']['high']['height'] ?? null
            ];
        }
        $standard = $item['snippet']['thumbnails']['standard']['url'] ?? null;
        if($standard != null) {
            $images['standard'] = [
                'url' => $item['snippet']['thumbnails']['standard']['url'] ?? null,
                'width' => $item['snippet']['thumbnails']['standard']['width'] ?? null,
                'height' => $item['snippet']['thumbnails']['standard']['height'] ?? null
            ];
        }
        $maxres = $item['snippet']['thumbnails']['maxres']['url'] ?? null;
        if($maxres != null) {
            $images['maxres'] = [
                'url' => $item['snippet']['thumbnails']['maxres']['url'] ?? null,
                'width' => $item['snippet']['thumbnails']['maxres']['width'] ?? null,
                'height' => $item['snippet']['thumbnails']['maxres']['height'] ?? null
            ];
        }
        return $images;
    }
}