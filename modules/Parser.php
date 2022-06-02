<?php
class TParser {

    public $name;
    public $json;//as object
    // public $user;

    // public function getData();

    public function save(){
        $fp = fopen(__DIR__.'/'.$this->file_name(), 'w');

        if (!$fp) {
            throw new Exception('parsed file save failed.');
        }  

        fwrite($fp, json_encode($this->json));
        fclose($fp);
    }

    public function read(){
        try {
            $file = @file_get_contents(__DIR__.'/'.$this->file_name());
            if(!$file) return null;
            $this->json = json_decode( $file );
        } catch (\Throwable $th) {
            $this->json = null;
        }
        return $this->json;
    }

    public function dump(){
        highlight_string("<?php\n" . var_export($this->json, true) . ";\n?>");
    }

    public function file_name(){
        return $this->name.'.'.$this->user.'.ignore.json';
    }

    public function sync(){

        $this->getJson();
        $this->formatter();
        $this->save();

        return true;
    }

    public function echofile(string $filename, $data, bool $objtojson ){
        $filename = str_replace(__DIR__, '', $filename);
        $fp = fopen(__DIR__.'/'.$filename, 'w');

        if (!$fp) {
            throw new Exception('cannot open file. '.$filename);
        }  

        if($objtojson){
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT ));
        } else {
            fwrite($fp, $data);
        }
        fclose($fp);
    }

} 