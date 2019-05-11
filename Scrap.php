<?php

    class Wordpress {

        private $debug = FALSE;
        private $urlDefaultOpts = [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_VERBOSE => FALSE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
            CURLOPT_AUTOREFERER => FALSE,
            CURLOPT_NOBODY => FALSE,
            CURLOPT_HTTPPROXYTUNNEL => FALSE,
            CURLOPT_PROXY => ''
        ];

        public function __construct() {

            if ( in_array ( 'curl', @get_loaded_extensions()) === FALSE || extension_loaded ( 'curl' ) === FALSE )
                trigger_error ( 'CURL Lib not loaded', E_USER_ERROR );

            return TRUE;
        }

        public function getCount(){

            $url = "https://fr.wordpress.org/plugins/";

            if ( ( $content = self::get($url) ) == FALSE ) {
                return FALSE;
            }

            if ( preg_match('/WordPress\s(\w*)\s([\d,]*)\sext/i', $content, $return) == FALSE ){
                if ( $this -> debug === TRUE ){
                    trigger_error("Can not parse html returned", E_USER_ERROR );
                }
                return FALSE;
            }

            $count = @str_replace(',','.',(string)$return[2]);

            return $count;
        }

        public function pluginDetail($url){

            if ( ( $content = self::get($url) ) == FALSE ) {
                return FALSE;
            }

            if ( @preg_match('@<\s*?script\stype=["\']application\/ld\+json["\'][^>]*>(.*?)</script\b[^>]*@s', $content, $return) == FALSE ){
                if ( $this -> debug === TRUE ){
                    trigger_error("No Json infos !", E_USER_ERROR );
                }
                return FALSE;
            }

            if ( ( $json = @json_decode($return[1], true) ) == FALSE ) {
                if ( $this -> debug === TRUE ){
                    trigger_error("Json malformed !", E_USER_ERROR );
                }
                return FALSE;
            }

            $pluginInfos = [
                "name" => @$json[1]["name"],
                "description" => @$json[1]["description"],
                "softwareVersion" => @$json[1]["softwareVersion"],
                "downloadUrl" => @$json[1]["downloadUrl"],
                "dateModified" => @$json[1]["dateModified"]
            ];

            return $pluginInfos;
        }

        public function search($keyword){
            
            $url = ('https://fr.wordpress.org/plugins/search/'.urlencode((string)$keyword).'/page/1/');
            $regex_next = '/a\sclass=["\']next\spage-numbers["\']\shref=["\'](.*?)["\']/i';
            $regex_link = '/<h2\sclass=["\']entry-title["\']><a\shref=["\'](.*?)["\']\srel=["\']bookmark["\']>/i';

            $linksArray = array();

            if ( ( $content = self::get($url) ) == FALSE ) {
                return FALSE;
            }

            if ( @preg_match_all($regex_link, $content, $links) == FALSE ){
                if ( $this -> debug === TRUE ){
                    trigger_error("No result !", E_USER_ERROR );
                }
                return FALSE;
            }

            if ( count((array)$links[1]) < 1 ){

                if ( $this -> debug === TRUE ){
                    trigger_error("No result 2 !", E_USER_ERROR );
                }

                return FALSE;
            }

            $linkArray = (array)$links[1];

            while ( ( @preg_match($regex_next, $content, $return) ) != FALSE ){

                if ( ( $content = self::get($return[1]) ) != FALSE ) {

                    @preg_match_all($regex_link, $content, $links);
                    $linkArray = array_merge($linkArray,(array)$links[1]);

                }
                else break;
            }

            $linkArray = array_unique($linkArray);

            return $linkArray;
        }

        private function get($url,array $opt = []) {

            $link = @curl_init($url);

            curl_setopt_array($link, ($this->urlDefaultOpts+$opt));

            if(($result = @curl_exec($link)) == FALSE ){
                if ( $this -> debug === TRUE ) {
                    $curl_errno = @curl_errno($link);
                    $curl_error = @curl_error($link);
                    trigger_error("cURL Error ($curl_errno): $curl_error", E_USER_ERROR );
                }
                @curl_close($link);
                return FALSE;
            }
            @curl_close($link);
            return $result;
        }
    }

// Try :
//$A = new Wordpress();
//var_dump($A -> getCount());
//var_dump($A -> search('wp'));
//var_dump($A -> pluginDetail("https://fr.wordpress.org/plugins/wpforms-lite/"));
    
?>
