<?php
/**
 * Author: Onur KAYA
 * E-mail: empatisoft@gmail.com
 * Date: 2021-11-25 10:00
 */

namespace Empatisoft\Api\Youtube;

class Response {

    /**
     * @param array|object $data
     * @param int $status
     * @param bool $echo
     * @return false|string
     */
    public function json($data, int $status = 200, bool $echo = true) {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if($echo == true) {
            http_response_code($status);
            header('Content-Type: application/json; charset: utf-8');
            echo $json;
            exit();
        } else
            return $json;

    }

}