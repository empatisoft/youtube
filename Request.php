<?php
/**
 * Author: Onur KAYA
 * E-mail: empatisoft@gmail.com
 * Date: 2021-11-25 10:00
 */
namespace Empatisoft\Api\Youtube;

use GuzzleHttp\Client;

class Request {

    /**
     * @param string $url
     * @param array $headers
     * @param bool $json
     * @return mixed|string
     */
    public function getRequest(string $url, array $headers = [], bool $json = true) {
        try {
            $client = new Client();
            $parameters = [];
            if(!empty($headers))
                $parameters['headers'] = $headers;

            $response = $client->request('GET', $url, $parameters)->getBody()->getContents();

            if(!empty($response) && $json == true) {
                $decode = json_decode($response, true);
                if(json_last_error() === JSON_ERROR_NONE)
                    $response = $decode;
            }
            $result = $response;
        } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
            $result = ['success' => false, 'code' => $exception->getCode(), 'message' => $exception->getMessage()];
        }

        return $result;
    }

    /**
     * @param string $url
     * @param array $data
     * @param string $type (form_params veya json)
     * @param array $headers
     * @return array
     */
    public function postRequest(string $url, array $data, string $type = 'form_params', array $headers = []):array {

        try {
            $result = [];
            $client = new Client();
            $parameters = [];
            if(!empty($headers))
                $parameters['headers'] = $headers;

            $parameters[$type] = $data;

            $response = $client->request('POST', $url, $parameters)->getBody()->getContents();

            if(!empty($response)) {
                $response = json_decode($response, true);
                if(json_last_error() === JSON_ERROR_NONE)
                    $result = $response;

            }

        } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
            $result = ['success' => false, 'code' => $exception->getCode(), 'message' => $exception->getMessage()];
        }
        return $result;
    }
}