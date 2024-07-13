<?php

require("./lib/AngryCurl/classes/RollingCurl.class.php");
require("./lib/AngryCurl/classes/AngryCurl.class.php");
require("./lib/PHPQuery/phpQuery.php");

class FBParser
{
    private $proxyList;
    private $useragentList;
    private $searchUrlTemplate;
    private $phoneCode;
    private $AC;
    private $dataList;
    
    public function __construct($config)
    {
        $this->proxyList = $config['proxy_list_file'];
        $this->useragentList = $config['useragent_list_file'];
        $this->searchUrlTemplate = $config['fb_search_url_tpl'];
        $this->phoneCode = $config['phone_code'];
        
        // sending callback function name as param
        $this->AC = new AngryCurl([$this, 'responseCallback']);
        
        // initializing console-style output
        $this->AC->init_console();

        // Importing proxy and useragent lists, setting regexp, proxy type and target url for proxy check
        // You may also import proxy from an array as simple as $AC->load_proxy_list($proxy array);
        $this->AC->load_proxy_list(
            // path to proxy-list file
            $this->proxyList,
            // optional: number of threads
            200
        );
        
        // You may also import useragents from an array as simple as $AC->load_useragent_list($proxy array);
        $this->AC->load_useragent_list($this->useragentList);
    }
    
    public function process($searchData)
    {
        
        foreach($searchData as $item) {
            
            $url = str_replace('{search}', urlencode($item['email']), $this->searchUrlTemplate);
            
            // adding URL to queue
            $this->AC->get($url);
            $this->dataList[$url] = $item;
            
            
            // you may also use 
            // $AC->post($url, $post_data = null, $headers = null, $options = null);
            // $AC->get($url, $headers = null, $options = null);
            // $AC->request($url, $method = "GET", $post_data = null, $headers = null, $options = null);
            // as well
             
        }
        
        // setting amount of threads and starting connections
        $this->AC->execute(9);
        
        // if console_mode is off
        //AngryCurl::print_debug(); 

        unset($this->AC);
        
    }
    
    public function responseCallback($response, $info, $request)
    {
       
        if($response){
            $this->processResult($response, $this->dataList[$info['url']]);        
        
        } else {
            AngryCurl::add_debug_msg('Empty response, URL:'.$info['url']);
            $this->AC->get($info['url']);
        }
    }
    
    public static function processResult($html, $data)
    {
	$html = str_replace(['<!--', '-->'], '', $html);
        $html = str_replace('<code', '<div', $html);
        $html = str_replace('</code>', '</div>', $html);
        
	$pq = phpQuery::newDocumentHTML($html);
        
        $captcha = $pq->find('#captcha')->text();

        if($captcha) {
            echo $data['email'];
            echo ':captcha'."\n";
            return false;
        }
        
	$result = $pq->find('div#BrowseResultsContainer');
        
        $items = $result->find('div._4p2o');
        
        $countItems = count($items);
        
        switch($countItems) {
            case 0:
                if(!$data['email']) {
                    var_dump($data);
                } else {
                    $url = str_replace('{search}', urlencode($this->phoneCode.$data['cellphone']), $this->searchUrlTemplate);
                    $this->AC->get($url);
                    AngryCurl::add_debug_msg('Phone URL:'.$url);
                }
                echo $data['email'];
                echo ':nothing found'."\n";
                break;
            case 1:
                $pq = pq($items[0]);
                
                echo $data['email'];
                echo ':success'."\n";
                break;
            default:
                echo $data['email'];
                echo ':>1 el.'."\n";
                if(!$data['email']) {
                    var_dump($data);
                } else {
                    $url = str_replace('{search}', urlencode($this->phoneCode.$data['cellphone']), $this->searchUrlTemplate);
                    $this->AC->get($url);
                    AngryCurl::add_debug_msg('Phone URL:'.$url);
                }
                break;
        }
        
        return true;
    }
}


