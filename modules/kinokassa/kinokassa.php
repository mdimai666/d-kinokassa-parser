<?php

require_once __DIR__."/../Parser.php";

class Kinokassa extends TParser{
    public $name = '';
    public $url = "";
    public $raw;

    public function __construct($slug, $url)
    {
        $this->name = $slug;
        $this->url = $url;
    }
    
    public function getJson(){
        $url = $this->url;


        $context = stream_context_create(
            array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                )
            )
        );

        mkdir($this->getDir());


        $html = file_get_contents($url, false, $context);
        // $html = file_get_contents(__DIR__.'/2index.html', false, $context);

        // $string = insta__curl_get_contents($url);

        // $media_json = get_string_between($html, 'window._sharedData = ', ';</script>');
        // $raw = json_decode($media_json);

        // $html = substr($html, 1, -1);

        $raw = $html;

        $this->raw = $raw;
        return $raw;
    }

    public function sampledata(){
        return json_decode( file_get_contents(__DIR__.'/instagram.json') );
    }

    public static function DirByName($name){
        return __DIR__.'/../../saved/'.$name.'/';
    }

    public function getDir(){
        return Kinokassa::DirByName($this->name);
    }

    function readEntities($raw){
        preg_match_all('/<link(.+?)href="(.*?)"\/>/', $raw, $matches_s);

        $styles = [];

        foreach ($matches_s[2] as $s) {
            $styles[] = str_replace('../common/', 'common/', $s);
        }

        preg_match_all('/<script src="(.*?)">/', $raw, $matches_js);


        preg_match('/<script(.*?)>\s*window.kinositeSettings(.*)\s*<\/script>/', $raw, $js_kinositeSettings);
        preg_match('/<script(.*?)>\s*window.kinokassaApiUrl(.*)\s*<\/script>/', $raw, $js_kinokassaApiUrl);

        $js1 = $matches_js[1];
        //common

        foreach ($js1 as $i => $s) {//fix relative urls
            $js1[$i] = preg_replace('/(.*?)(\/common\/)(.*)/','/common/$3', $s);
        }

        return [
            // 'styles' => $matches_s[2],
            'styles' => $styles,
            'js' => $js1,
            'kinositeSettings' => $js_kinositeSettings[0],
            'kinokassaApiUrl' => $js_kinokassaApiUrl[0],
        ];
    }

    function downloadReqFiles($e){
        $dw = $this->getDir();

        $domain = trim($this->url, '/').'/';

        // return;

        foreach ($e['styles'] as $s) {
            $url = $domain.$s;
            $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
            $this->downloadFile($url, $fname);
        }

        foreach ($e['js'] as $s) {
            $url = $domain.$s;
            $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
            $this->downloadFile($url, $fname);
        }

        $chunkjsList = [];

        //download chunks
        foreach ($e['js'] as $s) {
            if(strpos($s, 'kinosite-main.') !== false){//kinosite-main.min.2832beef0be6477ff575.js
                $url = $domain.$s;
                $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
                // $content = file_get_contents(__DIR__.'/'.$fname);
                // $fname = __DIR__.'/..'.$fname;
                $content = file_get_contents($fname);

                // $this->echofile($fname.'1','123', false);

                $chunkjsList = $this->extractChunks($content);
                // $this->echofile($dw.'chunkjsList.json',$chunkjsList, !true);
                
                $this->echofile($dw.'chunkjsList.json',$chunkjsList, true);

                foreach($chunkjsList as $chunkName){
                    $chunk_url = $domain."/common/chunks/".$chunkName;
                    $chunk_fname_read = __DIR__.'/..'.$dw.$chunkName;
                    $chunk_fname = $dw.$chunkName;
                    $this->downloadFile($chunk_url, $chunk_fname);
                }

            }
        }

        $ss = $e;
        $ss['chunkjsList'] = $chunkjsList;
        $this->echofile($dw.'ss.json', $ss, true);

    }

    function downloadFile($url, $fname){
        $context = stream_context_create(
            array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                )
            )
        );
        $content = file_get_contents($url, false, $context);
        $this->echofile($fname,$content, false);
        return $content;
    }

    function extractChunks($content){
        preg_match('/common\/chunks\/(.+?)\+{(.+?)}/', $content, $jchunks);
        // preg_match('/common\/(.+?)}/', $content, $jchunks);
        // $j = "{".$jchunks[2]."}";
        $j = $jchunks[2];
        // $j = $jchunks;
        $j = preg_replace('/(\d+):/','"$1":', $j);
        // $j = "[$j]";
        $j = "{".$j."}";
        // return ($j);
        // return json_decode(($j), true);
        $aa = json_decode(($j), true);
        $a=[];
        foreach($aa as $k=>$s){
            $a[] = "$k.min.$s.js";
        }
        return $a;
    }

    function getDWRelUrl(){
        $mm = plugins_url();
        $pp = 'wp-content/plugins';
        $pref = plugins_url("/kinokassa/",dirname(__FILE__));
        $pref = $pp.str_replace($mm,'', $pref);
        $pref = str_replace('/modules/kinokassa/','', $pref).'/saved/'.$this->name.'/';
        return $pref;
    }

    function generateHtml($e){
        $html = "";
        $pref = $this->getDWRelUrl();
        $url = $this->url;

        $tm = (new DateTime(current_time( 'mysql')))->getTimestamp();
        $hash = "?v=".wp_date('j-m-Y-H:i:s', $tm, new DateTimeZone('UTC'));
        

        foreach ($e['styles'] as $s) {
            $f = $pref.basename(parse_url($s, PHP_URL_PATH));
            $html .= "<link rel=\"stylesheet\" href=\"$f$hash\"/>".PHP_EOL;
        }

        $html .= "<div id=\"root\" data-domain=\"$url\" kinokassa=1></div>".PHP_EOL;


        $html .= $e['kinositeSettings'].PHP_EOL;
        $html .= $e['kinokassaApiUrl'].PHP_EOL;

        $html .= "
            <!-- KinoWidget -->
            <script>
                (function(d,t,u,e,s){
                e=d.createElement(t);s=d.getElementsByTagName(t)[0];
                e.async=true;e.src=u+'?v='+Date.now();s.parentNode.insertBefore(e,s);
                })(document,'script','//kinowidget.kinoplan.ru/js/kinowidget.min.js');
            </script>
            <!-- /KinoWidget -->
        ";

        foreach ($e['js'] as $s) {
            $f = $pref.basename(parse_url($s, PHP_URL_PATH));
            if(strpos($s, 'kinosite-main.') !== false){
                $f = $pref.'__'.basename(parse_url($s, PHP_URL_PATH));
            }
            $html .= "<script src=\"$f$hash\"></script>".PHP_EOL;
        }

        $html .= "<script src=\"$pref../../front/app_hooks.js?$hash\"></script>".PHP_EOL;


        $dw = $this->getDir();
        $this->echofile($dw.'index.html',$html, false);

    }

    function renameChunksPath($e){

        $dw = $this->getDir();
        $pe = "\"common/chunks/\"";
        $relDwUrl = "\"".$this->getDWRelUrl()."\"";//"/wp-content/plugins/d-kinokassa-parser/saves/<name>/"

        $domain = trim($this->url, '/').'/';

        foreach ($e['js'] as $s) {
            if(strpos($s, 'kinosite-main.') !== false){//kinosite-main.min.2832beef0be6477ff575.js
                $url = $s;
                $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
                $__fname = $dw.'__'.basename(parse_url($url, PHP_URL_PATH));
                // $content = file_get_contents(__DIR__.'/'.$fname);
                // $fname2 = __DIR__.'/..'.$fname;
                // $content = file_get_contents($fname2);
                $content = file_get_contents($fname);

                $content = str_replace($pe, $relDwUrl, $content);

                // $content = str_replace('/img/', $domain.'img/', $content);//renameLinksInUrls

                $this->echofile($__fname,$content, false);

                

            }
        }
    }

    function renameLinksInUrls($e){

        $dw = $this->getDir();
        $relDwUrl = "\"".$this->getDWRelUrl()."\"";//"/wp-content/plugins/d-kinokassa-parser/saves/<name>/"

        $domain = trim($this->url, '/').'/';

        foreach ($e['js'] as $s) {

            $isSpecial = strpos($s, 'kinosite-main.') !== false;

            $url = $s;
            $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
            $__fname = $dw.'__'.basename(parse_url($url, PHP_URL_PATH));
            if(!$isSpecial)
            $content = file_get_contents($fname);
            else 
            $content = file_get_contents($__fname);

            $content = str_replace('/img/', $domain.'img/', $content);

            if(!$isSpecial)
            $this->echofile($fname,$content, false);
            else
            $this->echofile($__fname,$content, false);

        }

        foreach ($e['chunkjsList'] as $s) {

            $isSpecial = false;

            $url = $s;
            $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
            $__fname = $dw.'__'.basename(parse_url($url, PHP_URL_PATH));
            if(!$isSpecial)
            $content = file_get_contents($fname);
            else 
            $content = file_get_contents($__fname);

            $content = str_replace('/img/', $domain.'img/', $content);
            // $content = str_replace('/release/', $domain.'release/', $content);

            if(!$isSpecial)
            $this->echofile($fname,$content, false);
            else
            $this->echofile($__fname,$content, false);
                

        }
    }

    public function formatter(){

        Kinokassa::CleanDir($this->name);
        mkdir($this->getDir());


        $raw = $this->raw;

        // return;
        // $this->echofile('kinokassa/2index.html', $raw, false);
        // file_put_contents(__DIR__.'/2index.html', $raw);
        
        //test
        // $ss = json_decode(file_get_contents($this->getDir().'ss.json'), true);

        $ss = $this->readEntities($raw);
        $this->echofile($this->getDir().'ss.json', $ss, true);
        
        $this->downloadReqFiles($ss);
        $this->renameChunksPath($ss);
        $this->renameLinksInUrls($ss);
        $this->generateHtml($ss);
        return;
        

        // throw new Error('not implement');

        // if(empty($this->raw))
        //     // $raw = $this->sampledata();
        //     $raw = $this->getJson();
        // else 
        //     $raw = $this->raw;


        $data = [];

        $this->json = $data;
        return $data;
    }

    public function getData(){
        return $this->json;
    }

    public function save(){

    }

    public static function CleanDir($name){
        $dw = Kinokassa::DirByName($name);
        if(is_dir($dw)){
            TParser::d_recurse_rmdir($dw);
        }
    }
}

// $Instagram = new Instagram('mdimai000');

// // $Instagram->read();
// $data = $Instagram->formatter();
// $Instagram->save();

// highlight_string("<?php\n" . var_export($data, true) . ";\n? >");


function insta__curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $html = curl_exec($ch);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function insta__file_get_html($url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
{
    $dom = new simple_html_dom;
    $args = func_get_args();
    $dom->load(call_user_func_array('curl_get_contents', $args), true);
    return $dom;
    //$dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

}

