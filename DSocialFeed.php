<?php
    
class DSocialFeed {
    
    public $option;

    const option_name = 'd_social_option';
    
    public function __construct() {

    }

    public function get_option(bool $force = false){
        if(!$force && $this->option)
            return $this->option;

        return get_option(DSocialFeed::option_name, $this->default_option());
    }

    public function update_option($option){
        $this->option = $option;
        return update_option(DSocialFeed::option_name, $option);
    }

    public function default_option(){
        return [
            'tick' => 1,
            'last_updated' => (new DateTime(current_time( 'mysql')))->getTimestamp(),
            'feeds' => [],
        ];
    }

    public function update_feed($name){
        
        $parser = $this->get_parser($name);

        $result = $parser->sync();

        if(!$result)
            throw new Exception("Ошибка парсера при синхронизации");

        $parser->save();
            
        return true;
    }

    public function get_parser($name){
        $option = $this->get_option();

        $net = $option['feeds'][$name];

        if(!$net)
            throw new Exception("Сеть не найдена");
        
        if(!$net['url'])
            throw new Exception("Для сети не указана ссылка");

        // $parcer - will be Class TParser "/modules/Parser.php"
        $parser = false;

        $username = $net['url'];
        $token = $net['token'];

        if($name == 'kinokassa')
            $parser = new Kinokassa($username);
        // else if($name == 'vk') 
        //     $parser = new VK_parcer($username, $token);
        // else if($name == 'youtube')
        //     $parser = new Youtube($username);
        // else if($name == 'smi')
        //     $parser = new SMI_Parcer($username);

        if(!$parser)
            throw new Exception("Парсер для сети не найден");

        return $parser;
    }

    public function get_simple_feed($name){
        
        try{

            $parser = $this->get_parser($name);

            $data = $parser->read();

            return $data;
        } catch(Exception $ex){
            
        }

        return [];
    }
}

// $Instagram->getJson();
// $data = $Instagram->formatter();
// $Instagram->save();

// $data = $Instagram->read();
// $items = $data->posts;