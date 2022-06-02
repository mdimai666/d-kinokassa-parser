<?php

require_once __DIR__."/../Parser.php";

class Kinokassa extends TParser{
    public $name = 'kinokassa';
    public $user = "";
    public $raw;

    public function __construct($user)
    {
        $this->user = $user;
    }
    
    public function getJson(){
        // $url = "https://www.instagram.com/$this->user/?__a=1";
        // $url = "https://www.kinokassakinokassa.com/$this->user";
        $url = "https://cinema-center.ru";


        $context = stream_context_create(
            array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                )
            )
        );

        // $html = file_get_contents($url, false, $context);
        $html = file_get_contents(__DIR__.'/2index.html', false, $context);

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
        $dw = '/kinokassa/dw/';

        $domain = "https://cinema-center.ru/";

        // return;

        foreach ($e['styles'] as $s) {
            $url = $domain.$s;
            $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
            $this->downloadFile($url, $fname);
        }

        // foreach ($e['js'] as $s) {
        //     $url = $domain.$s;
        //     $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
        //     $this->downloadFile($url, $fname);
        // }

        $chunkjsList = [];

        //download chunks
        foreach ($e['js'] as $s) {
            if(strpos($s, 'kinosite-main.') !== false){//kinosite-main.min.2832beef0be6477ff575.js
                $url = $domain.$s;
                $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
                // $content = file_get_contents(__DIR__.'/'.$fname);
                $fname = __DIR__.'/..'.$fname;
                $content = file_get_contents($fname);

                // $this->echofile($fname.'1','123', false);

                $chunkjsList = $this->extractChunks($content);
                // $this->echofile($dw.'chunkjsList.json',$chunkjsList, !true);
                
                $this->echofile($dw.'chunkjsList.json',$chunkjsList, true);

                foreach($chunkjsList as $chunkName){
                    $chunk_url = $domain."/common/chunks/".$chunkName;
                    $chunk_fname_read = __DIR__.'/..'.$dw.$chunkName;
                    $chunk_fname = $dw.$chunkName;
                    // $this->downloadFile($chunk_url, $chunk_fname);
                }

            }
        }

        $ss = $e;
        $ss['chunkjsList'] = $chunkjsList;
        $this->echofile('kinokassa/ss.json', $ss, true);

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
        $pref = plugins_url("/kinokassa/dw/",dirname(__FILE__));
        $pref = $pp.str_replace($mm,'', $pref);
        return $pref;
    }

    function generateHtml($e){
        $html = "";
        $pref = $this->getDWRelUrl();
        

        foreach ($e['styles'] as $s) {
            $f = $pref.basename(parse_url($s, PHP_URL_PATH));
            $html .= "<link rel=\"stylesheet\" href=\"$f\"/>".PHP_EOL;
        }

        $html .= "<div id=\"root\"></div>".PHP_EOL;


        $html .= $e['kinositeSettings'].PHP_EOL;
        $html .= $e['kinokassaApiUrl'].PHP_EOL;

        foreach ($e['js'] as $s) {
            $f = $pref.basename(parse_url($s, PHP_URL_PATH));
            if(strpos($s, 'kinosite-main.') !== false){
                $f = $pref.'__'.basename(parse_url($s, PHP_URL_PATH));
            }
            $html .= "<script src=\"$f\"></script>".PHP_EOL;
        }

        $dw = '/kinokassa/dw/';
        $this->echofile($dw.'index.html',$html, false);

    }

    function renameChunksPath($e){

        $dw = '/kinokassa/dw/';
        $pe = "\"common/chunks/\"";
        $relDwUrl = "\"".$this->getDWRelUrl()."\"";//"/wp-content/plugins/d-kinokassa-parser/modules/kinokassa/dw/"


        foreach ($e['js'] as $s) {
            if(strpos($s, 'kinosite-main.') !== false){//kinosite-main.min.2832beef0be6477ff575.js
                $url = $domain.$s;
                $fname = $dw.basename(parse_url($url, PHP_URL_PATH));
                $__fname = $dw.'__'.basename(parse_url($url, PHP_URL_PATH));
                // $content = file_get_contents(__DIR__.'/'.$fname);
                $fname2 = __DIR__.'/..'.$fname;
                $content = file_get_contents($fname2);

                $content = str_replace($pe, $relDwUrl, $content);


                $content = str_replace('/img/', 'https://cinema-center.ru/img/', $content);

                $this->echofile($__fname,$content, false);

                

            }
        }
    }

    public function formatter(){

        $raw = $this->raw;

        return;

        $this->echofile('kinokassa/2index.html', $raw, false);

        // file_put_contents(__DIR__.'/2index.html', $raw);
        
        $ss = $this->readEntities($raw);
        $this->echofile('kinokassa/ss.json', $ss, true);
        
        $this->downloadReqFiles($ss);
        
        $this->renameChunksPath($ss);
        $this->generateHtml($ss);
        

        throw new Error('not implement');

        if(empty($this->raw))
            // $raw = $this->sampledata();
            $raw = $this->getJson();
        else 
            $raw = $this->raw;

        // $user = $raw->graphql->user; //for non HTML json- from special page ?_a=1
        
        $user = $raw->entry_data->ProfilePage[0]->graphql->user;

        // __dump($user);

        $posts = array();

        $image_save_path = __DIR__.'/temp/';

        $break = false;

        $this->echofile('wi.json', $raw, true);
        if($user == null) throw new Error('isnta $user is NULL');

        throw new Error('cc:'.count($user->edge_owner_to_timeline_media->edges));

        foreach($user->edge_owner_to_timeline_media->edges as $nodes){
            $e = $nodes->node;

            $desc = '';
            try {
                $d = $e->edge_media_to_caption->edges;
                if(count($d)>0)
                    $desc = $d[0]->node->text;
            } catch (\Throwable $th) {
            }

            $post = array(
                'id' => $e->id,
                'channelId' => $e->shortcode,
                'title' => $user->full_name,

                'link' => 'https://www.instagram.com/p/'.$e->shortcode,
                // 'published' => date("Y-m-d H:i:s",$e->taken_at_timestamp),
                'published' => $e->taken_at_timestamp,
                'updated' => '',

                'thumbnail' => $e->display_url,
                'description' => urldecode($desc),

                'views' => 0,
                'likes' => $e->edge_liked_by->count,

                'is_video' => $e->is_video,
                'comments' => $e->edge_media_to_comment->count,
            );

            //save image local
            if(!$break){
               
                $break = true;
                $post['thumbnail'] = 'xxx';
                // $img_filename = $image_save_path.$post['id'].'.jpg';
                // file_put_contents($img_filename, file_get_contents($post['thumbnail']));
                // $post['thumbnail'] = get_stylesheet_directory_uri().'/modules/instagram/temp/'.$post['id'].'.jpg';


            }


            $posts[] = $post;
        }

        $data = array(
            'title' => $user->full_name,
            'author' => $user->username,
            'url' => 'https://www.instagram.com/'.$user->username,
            'img' => $user->profile_pic_url,

            'followers' => $user->edge_followed_by->count,
            'follow' => $user->edge_follow->count,

            'posts' => $posts,
        );

        $this->json = $data;
        return $data;
    }

    public function getData(){
        return $this->json;
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

