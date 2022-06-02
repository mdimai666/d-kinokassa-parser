<?php


add_shortcode( 'dkinokassa', 'dsh_kinokassa' );

function dsh_kinokassa( $atts, $content, $tag ) {

    // какая-то общая логика, например обработка переменных $atts
    $atts = shortcode_atts( [
        'slug' => '0',        
    ], $atts );

    if($atts['slug'] == '0') return "<h3>[Требуется slug]</h3>";

    $feed = new DSocialFeed();
    $option = $feed->get_option();

    $slug = $atts['slug'];

    $exist = array_key_exists($slug, $option['feeds']);

    if($exist){
        $w = Kinokassa::DirByName($slug);
        $index = $w.'index.html';

        if(!file_exists($index)) return "<h3>[файл $slug не найден]</h3>"; 

        $html = file_get_contents($index);

        return $html;

    }

    // $html = file_get_contents(__DIR__.'/test_index.html');
    // $html = file_get_contents(__DIR__.'/modules/kinokassa/dw/index.html');
    // $content = $html;

    // $content = 'Конетнт шорткода '.$atts['id'];

    return $content;
}