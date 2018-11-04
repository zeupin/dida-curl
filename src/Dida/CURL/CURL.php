<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace Dida\CURL;

class CURL
{
    const VERSION = '20180901';

    const ERR_INVALID_METHOD = -1;

    public $method = "GET";
    public $valid_methods = ["GET", "POST"];

    public $header = [];


    public function addHeader($line)
    {
        $this->header[] = $line;
    }


    public function clearHeaders()
    {
        $this->header = [];
    }


    public function request(array $http, array $curloptions = [])
    {
        $url = $http["url"];

        $method = (isset($http["method"])) ? $http["method"] : $this->method;
        $method = strtoupper($method);
        if (!in_array($method, $this->valid_methods)) {
            return [self::ERR_INVALID_METHOD, "无效的请求方式", null];
        }

        $query = (isset($http["query"])) ? $http["query"] : '';
        if (is_array($query)) {
            $query = http_build_query($query);
        }

        $data = (isset($http["data"])) ? $http["data"] : null;
        if (is_array($data)) {
            $data = http_build_query($data);
        }

        $curl = curl_init();

        if ($query) {
            if (mb_strpos($url, "?") === false) {
                $url = $url . "?" . $query;
            } elseif (mb_substr($url, -1, 1) === "&") {
                $url = $url . $query;
            } else {
                $url = $url . "&" . $query;
            }
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        if (mb_substr($url, 0, 8) === "https://") {
            curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
        }

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
        }

        $defaults = [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_AUTOREFERER    => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        curl_setopt_array($curl, $defaults);

        $header = $this->header;
        if (array_key_exists(CURLOPT_HTTPHEADER, $curloptions)) {
            $header = array_merge($header, $curloptions[CURLOPT_HTTPHEADER]);
            $header = array_unique($header);
            unset($curloptions[CURLOPT_HTTPHEADER]);
        }
        if ($header) {
            curl_setopt_array($curl, [
                CURLOPT_HEADER     => 1,
                CURLOPT_HTTPHEADER => $header
            ]);
        }

        curl_setopt_array($curl, $curloptions);

        $data = curl_exec($curl);

        $err_no = curl_errno($curl);
        if ($err_no) {
            return [$err_no, curl_error($curl), null];
        }

        curl_close($curl);

        return [0, null, $data];
    }


    public function postjson($url, $json)
    {
        $input = [
            "url"    => $url,
            "method" => "POST",
            "data"   => $json,
        ];

        $curloptions = [
            CURLOPT_HEADER     => 1,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json;charset=UTF-8"
            ],
        ];

        return $this->request($input, $curloptions);
    }


    public function parseHttpResponse($resp)
    {
        $matches = null;

        $lines = explode("\r\n", $resp);

        $line1 = $lines[0];
        $pattern = "/(HTTP\/)(\d\.\d)\s(\d\d\d)/";
        $r = preg_match($pattern, $line1, $matches);
        if ($r) {
            $statusCode = $matches[3];
        } else {
            return false;
        }
        unset($lines[0]);

        $headers = [];
        foreach ($lines as $n => $line) {
            if ($line === '') {
                unset($lines[$n]);
                break;
            }

            $headers[] = $line;
            unset($lines[$n]);
        }

        $body = implode("\r\n", $lines);

        return [
            "code"    => $statusCode,
            "headers" => $headers,
            "body"    => $body,
        ];
    }
}
