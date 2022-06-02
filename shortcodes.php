<?php


add_shortcode( 'dkinokassa', 'dsh_kinokassa' );

function dsh_kinokassa( $atts, $content, $tag ) {

    // какая-то общая логика, например обработка переменных $atts
    $atts = shortcode_atts( [
        'id' => 'no foo',        
    ], $atts );

    // $html = file_get_contents(__DIR__.'/test_index.html');
    $html = file_get_contents(__DIR__.'/modules/kinokassa/dw/index.html');
    $content = $html;

    // $content = 'Конетнт шорткода '.$atts['id'];

    return $content;
}