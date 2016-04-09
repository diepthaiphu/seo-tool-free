<?php

function alexaRank($site)
{
    $xml = simplexml_load_file('http://data.alexa.com/data?cli=10&dat=snbamz&url=' .
        $site);
    $a = $xml->SD[1]->POPULARITY;
    if ($a != null)
    {
        $alexa_rank = $xml->SD[1]->POPULARITY->attributes()->TEXT;
        if ($alexa_rank == null)
            $alexa_rank = 'No Rank';
    } else
    {
        $alexa_rank = 'No Rank';
    }

    return $alexa_rank;
}

function checkOnline($site)
{
    $curlInit = curl_init($site);
    curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($curlInit, CURLOPT_HEADER, true);
    curl_setopt($curlInit, CURLOPT_NOBODY, true);
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

    //get answer
    $response = curl_exec($curlInit);
    $GLOBALS['rtime'] = curl_getinfo($curlInit);
    curl_close($curlInit);
    if ($response)
        return true;
    return false;
}

function StrToNum($Str, $Check, $Magic)
{
    $Int32Unit = 4294967296; // 2^32

    $length = strlen($Str);
    for ($i = 0; $i < $length; $i++)
    {
        $Check *= $Magic;
        if ($Check >= $Int32Unit)
        {
            $Check = ($Check - $Int32Unit * (int)($Check / $Int32Unit));
            //if the check less than -2^31
            $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
        }
        $Check += ord($Str{$i});
    }
    return $Check;
}
function isValidSite($site)
{
    return !preg_match('/^[a-z0-9\-]+\.[a-z]{2,10}(\.[a-z]{2,4})?$/i', $site);
}
function HashURL($String)
{
    $Check1 = StrToNum($String, 0x1505, 0x21);
    $Check2 = StrToNum($String, 0, 0x1003F);

    $Check1 >>= 2;
    $Check1 = (($Check1 >> 4) & 0x3FFFFC0) | ($Check1 & 0x3F);
    $Check1 = (($Check1 >> 4) & 0x3FFC00) | ($Check1 & 0x3FF);
    $Check1 = (($Check1 >> 4) & 0x3C000) | ($Check1 & 0x3FFF);

    $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2) | ($Check2 & 0xF0F);
    $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 &
        0xF0F0000);

    return ($T1 | $T2);
}

function CheckHash($Hashnum)
{
    $CheckByte = 0;
    $Flag = 0;

    $HashStr = sprintf('%u', $Hashnum);
    $length = strlen($HashStr);

    for ($i = $length - 1; $i >= 0; $i--)
    {
        $Re = $HashStr{$i};
        if (1 === ($Flag % 2))
        {
            $Re += $Re;
            $Re = (int)($Re / 10) + ($Re % 10);
        }
        $CheckByte += $Re;
        $Flag++;
    }

    $CheckByte %= 10;
    if (0 !== $CheckByte)
    {
        $CheckByte = 10 - $CheckByte;
        if (1 === ($Flag % 2))
        {
            if (1 === ($CheckByte % 2))
            {
                $CheckByte += 9;
            }
            $CheckByte >>= 1;
        }
    }

    return '7' . $CheckByte . $HashStr;
}
function getch($url)
{
    return CheckHash(HashURL($url));
}
function google_page_rank($url)
{
    $ch = getch($url);
    $fp = fsockopen('toolbarqueries.google.com', 80, $errno, $errstr, 30);
    if ($fp)
    {
        $out = "GET /tbr?client=navclient-auto&ch=$ch&features=Rank&q=info:$url HTTP/1.1\r\n";
        $out .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:28.0) Gecko/20100101 Firefox/28.0\r\n";
        $out .= "Host: toolbarqueries.google.com\r\n";
        $out .= "Connection: Close\r\n\r\n";
        fwrite($fp, $out);
        while (!feof($fp))
        {
            $data = fgets($fp, 128);
            //echo $data;
            $pos = strpos($data, "Rank_");
            if ($pos === false)
            {
            } else
            {
                $pager = substr($data, $pos + 9);
                $pager = trim($pager);
                $pager = str_replace("\n", '', $pager);
                return $pager;
            }
        }
        fclose($fp);
    }
}
function clean_url($site)
{
    $site = strtolower($site);
    $site = str_replace(array(
        'http://',
        'https://',
        'www.',
        '/'), '', $site);
    return $site;
}

function host_info($site)
{
    $ch = curl_init('http://www.iplocationfinder.com/' . clean_url($site));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $data = curl_exec($ch);
    preg_match('~ISP.*<~', $data, $isp);
    preg_match('~Country.*<~', $data, $country);
    preg_match('~IP:.*<~', $data, $ip);

    $country = explode(':', strip_tags($country[0]));
    $country = trim(str_replace('Hide your IP address and Location here', '', $country[1]));
    if ($country == '')
        $country = 'Not Available';

    $isp = explode(':', strip_tags($isp[0]));
    $isp = trim($isp[1]);
    if ($isp == '')
        $isp = 'Not Available';

    $ip = $ip[0];
    $ip = trim(str_replace(array(
        'IP:',
        '<',
        '/label>',
        '/th>td>',
        '/td>'), '', $ip));
    if ($ip == '')
        $ip = 'Not Available';
    $data = $ip . "::" . $country . "::" . $isp . "::";
    return $data;
}

function googleBack($site)
{
    $url = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=link:" . $site .
        "&filter=0";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $json = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($json, true);
    if ($data['responseStatus'] == 200)
    {
        $data = $data['responseData']['cursor']['resultCount'];
        if ($data == '')
            $data = 0;
        return $data;
    } else
        return false;
}
function googleIndex($site)
{
    $url = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=site:" . $site .
        "&filter=0";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $json = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($json, true);
    if ($data['responseStatus'] == 200)
        return $data['responseData']['cursor']['resultCount'];
    else
        return false;
}
function bingBack($link)
{
    $link = "http://www.bing.com/search?q=link%3A" . trim($link) .
        "&go=&qs=n&sk=&sc=8-5&form=QBLH";
    $source = file_get_contents($link);
    $s = explode('<span class="sb_count">', $source);
    $s = explode('</span>', $s[1]);
    $s = explode('results', $s[0]);
    $s = Trim($s[0]);
    if ($s == '')
    {
        $s = 0;
    }
    return $s;
}
function dmozCheck($site)
{
    $mydata = file_get_contents("http://www.dmoz.org/search?q=$site");
    return strpos($mydata, "DMOZ Categories") ? "Listed" : "Not Listed";
}
function dnsblookup($ip)
{
    $dnsbl_lookup = array(
        "dnsbl-1.uceprotect.net",
        "dnsbl-2.uceprotect.net",
        "dnsbl-3.uceprotect.net",
        "dnsbl.dronebl.org",
        "dnsbl.sorbs.net",
        "zen.spamhaus.org"); // Add your preferred list of DNSBL's
    if ($ip)
    {
        $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
        foreach ($dnsbl_lookup as $host)
        {
            if (checkdnsrr($reverse_ip . "." . $host . ".", "A"))
            {
                $listed .= $reverse_ip . '.' . $host . ' blacklisted';
            }
        }
    }
    if ($listed)
    {
        return $listed;
    } else
    {
        return 'not blacklisted';
    }
}

function getHeaders($site)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $site);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    return curl_exec($ch);
}
function getHttp($headers)
{
    $headers = explode("\r\n", $headers);
    $http_code = explode(' ', $headers[0]);
    return (int)trim($http_code[1]);
}
function robocheck($site)
{
    if ($site{strlen($site) - 1} != '/')
        $site .= '/';
    $site .= 'robots.txt';
    $headers = explode("\r\n", getHeaders($site));

    if (!empty($headers[0]))
    {
        $httpcode = getHttp($headers[0]);
        if ($httpcode == 200 || $httpcode == 500 || $httpcode == 301 || $httpcode == 302 ||
            $httpcode == 403)
        {
            $site = "www.$site";
            $headers = explode("\r\n", getHeaders($site));

            if (!empty($headers[0]))
            {
                $httpcode = getHttp($headers[0]);
                if ($httpcode == 200 || $httpcode == 500 || $httpcode == 301 || $httpcode == 302 ||
                    $httpcode == 403)
                {
                    return 1;
                } else
                {
                    return 0;
                }
            } else
            {
                return 0;
            }
        } else
        {
            return 0;
        }
    } else
    {
        return 0;
    }
}
function sitemap_check($site)
{
    if ($site{strlen($site) - 1} != '/')
        $site .= '/';
    $site .= 'sitemap.xml';
    $headers = explode("\r\n", getHeaders($site));

    if (!empty($headers[0]))
    {
        $httpcode = getHttp($headers[0]);
        if ($httpcode == 200 || $httpcode == 500 || $httpcode == 301 || $httpcode == 302 ||
            $httpcode == 403)
        {
            $site = "www.$site";
            $headers = explode("\r\n", getHeaders($site));

            if (!empty($headers[0]))
            {
                $httpcode = getHttp($headers[0]);
                if ($httpcode == 200 || $httpcode == 500 || $httpcode == 301 || $httpcode == 302 ||
                    $httpcode == 403)
                {
                    return 1;
                } else
                {
                    return 0;
                }
            } else
            {
                return 0;
            }
        } else
        {
            return 0;
        }
    } else
    {
        return 0;
    }
}

define('API_KEY', "ABQIAAAANrgclgOSnI8GAOO2GKrfLxSjiiXprvFQi7Qdz4LWsrszinU-iQ");
define('PROTOCOL_VER', '3.0');
define('CLIENT', 'checkURLapp');
define('APP_VER', '1.0');

function get_data($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $data = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array('status' => $httpStatus, 'data' => $data);
}

function send_response($input)
{
    if (!empty($input))
    {
        $urlToCheck = urlencode($input);

        $url = 'https://sb-ssl.google.com/safebrowsing/api/lookup?client=' . CLIENT .
            '&apikey=' . API_KEY . '&appver=' . APP_VER . '&pver=' . PROTOCOL_VER . '&url=' .
            $urlToCheck;

        $response = get_data($url);

        if ($response['status'] == 204)
        {
            return json_encode(array(
                'status' => 204,
                'checkedUrl' => $urlToCheck,
                'message' => 'The website is not blacklisted and looks safe to use.'));
        } elseif ($response['status'] == 200)
        {
            return json_encode(array(
                'status' => 200,
                'checkedUrl' => $urlToCheck,
                'message' => 'The website is blacklisted as ' . $response['data'] . '.'));
        } else
        {
            return json_encode(array(
                'status' => 501,
                'checkedUrl' => $urlToCheck,
                'message' => 'Something went wrong on the server. Please try again.'));
        }
    } else
    {
        return json_encode(array(
            'status' => 401,
            'checkedUrl' => '',
            'message' => 'Please enter URL.'));
    }
    ;
}

function check_mal($site)
{
    $checkMalware = send_response($site);
    $checkMalware = json_decode($checkMalware, true);
    $malwareStatus = $checkMalware['status'];
    return $malwareStatus;
}

?>
