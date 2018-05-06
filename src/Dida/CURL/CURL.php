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
    const VERSION = '20180427';

    const ERR_INVALID_METHOD = -1;

    public function request(array $input, array $curloptions = [])
    {
        $url = $input["url"];

        $method = (isset($input["method"])) ? $input["method"] : "GET";
        $method = strtoupper($method);
        if (!in_array($method, ["GET", "POST"])) {
            return [self::ERR_INVALID_METHOD, "无效的请求方式", null];
        }

        $query = (isset($input["query"])) ? $input["query"] : '';
        if (is_array($query)) {
            $query = http_build_query($query);
        }

        $data = (isset($input["data"])) ? $input["data"] : null;
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

        curl_setopt_array($curl, $curloptions);

        $data = curl_exec($curl);

        $err_no = curl_errno($curl);
        if ($err_no) {
            return [$err_no, curl_error($curl), null];
        }

        curl_close($curl);

        return [0, null, $data];
    }
}
