<?php
//Страница настройки социальных сетей




// if(__isAdmin())
add_action( 'admin_menu', 'd_social_feed_init' );

//https://stackoverflow.com/questions/21096539/creating-wordpress-admin-page-template-in-your-theme
function d_social_feed_init() {

    $role = get_role('administrator');
    $role->add_cap("d_social_feed_page_cap", true);

	// add_menu_page( 'My Top Level Menu Example', __('Уведомления'), 'manage_options', 'myplugin/myplugin-admin-page.php', 'notify_admin_page', 'dashicons-format-chat', 2 );
    add_menu_page( __('Лента социальных сетей'), __('Лента соц. сетей'), 
        'd_social_feed_page_cap', 'd_social_feed', 'd_social_feed_render', 'dashicons-share', 10 );

    
    // add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position)
    // add_submenu_page( 'myplugin/myplugin-admin-page.php', 'My Sub Level Menu Example', 'Sub Level Menu', 'manage_options', 'myplugin/myplugin-admin-sub-page.php', 'myplguin_admin_sub_page' ); 
    // add_options_page( 'My Plugin Options', 'My Plugin', 'manage_options', 'my-unique-identifier', 'my_plugin_options' );

    // add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function );

}

function d_social_feed_render() {
    if ( !current_user_can( 'd_social_feed_page_cap' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $user = wp_get_current_user();

    $feed = new DSocialFeed();

    
    $option = $feed->get_option();

    $placeholders = [
        'instagram' => 'username',
        'vk' => 'username',
        'youtube' => '1WCfgQJLf6ShrpWq1oeelQoQ',
        'smi' => 'не требуется',
        'kinokassa' => 'не требуется',
    ];

    $placeholders_token = [
        // 'instagram' => 'api key',
        'vk' => 'service token',
        // 'youtube' => 'API YouTube Data v3',
    ];
    
    ?>
    <div class="wrap p12">

        <h1><?php _e('Настройки социальных сетей') ?></h1>

        <div id="d_social_feed_page">

            <div id="messages">
                <?php
                    // d_social_feed_notice__success('1');
                    // d_social_feed_notice__error('1');
                ?>
            </div>

            <form id="social_list" action="?" method="POST">
                <div id="publishing-action">
                    <span class="spinner"></span>
                    <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="Сохранить">
                </div>

                <h2 class="title">Аккаунты</h2>
                <table class="form-table">

                    <thead>
                        <tr>
                            <th>Социальная сеть</th>
                            <th>Идентификатор</th>
                            <th>Ключи</th>
                            <th>Функции</th>
                            <th>Последняя синхронизация</th>
                        </tr>
                    </thead>

                    <!-- <tr>
                        <td>Tick</td>
                        <td>< ?php echo $option['tick'] ? ></td>
                    </tr> -->

                    <?php foreach($feed->get_list1() as $name): ?>

                        <?php
                            $item = $option['feeds'][$name];                    
                        ?>
                        <tr data-social="<?php echo $name ?>">
                            <td>
                                <?php echo $name ?>            
                            </td>
                            <td>
                                <input type="text" 
                                    name="<?php echo $name; ?>_url" size="30" 
                                    value="<?php echo $item['url']; ?>" 
                                    id="<?php echo $name; ?>_url" 
                                    placeholder="<?php echo $placeholders[$name] ?>"
                                    spellcheck="true" autocomplete="off">
                            </td>
                            <td>
                                <?php if($placeholders_token[$name]): ?>
                                    <input type="text" 
                                        name="<?php echo $name; ?>_token" size="30" 
                                        value="<?php echo $item['token']; ?>" 
                                        id="<?php echo $name; ?>_token" 
                                        placeholder="<?php echo $placeholders_token[$name] ?>"
                                        spellcheck="true" autocomplete="off">

                                <?php endif; ?>

                            </td>
                            <td>
                                <div class="update-feed-action">
                                    <span class="spinner"></span>
                                    <input type="button" value="Синхронизировать" class="button">
                                </div>
                            </td>
                            <td class="last_sync">
                                <?php echo ($item['last_sync'])?(gmdate('j M Y, H:i', $item['last_sync'])):''; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

            </form>
            


        <?php
             //__dump( $feed->get_option() );
        ?>

        </div> <!-- //#d_social_feed_page -->

    </div> <!-- //wrap -->

    <?php
}


/////////////////////////
function d_social_feed_notice__success($text = '') {
    ?>
    <div id="message-success" class="notice notice-success is-dismissible">
        <p>
            <?php echo $text ?>
        </p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Скрыть это уведомление.</span>
        </button>
    </div>
    <?php
}

function d_social_feed_notice__error($text = '') {
    ?>
    <div id="message-error" class="notice notice-error is-dismissible">
        <p>
            <?php echo $text ?>
        </p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Скрыть это уведомление.</span>
        </button>
    </div>
    <?php

}
/////////////////////////


//////////////////////////////////
//AJAX сохранение
if( wp_doing_ajax() ){
 
    add_action('wp_ajax_d_social_feed_save_data', 'd_social_feed__save_data');
    add_action('wp_ajax_d_social_feed_sync_feed', 'd_social_feed__sync_feed');

    // add_action('wp_ajax_d_social_feed_sync_AllFeeds', 'd_social_feed__sync_AllFeeds');
    // add_action('wp_ajax_nopriv_d_social_feed_sync_AllFeeds', 'd_social_feed__sync_AllFeeds');

}

//Для регулярного обновления социальных листов через крон вызывать http://localhost/wp-json/socialfeeds/v1/syncall

add_action( 'rest_api_init', function () {
    
    register_rest_route( 'socialfeeds/v1', '/syncall/', array(
      'methods' => ['GET'],
      'callback' => 'd_social_feed__sync_AllFeeds',
    ));
});

function d_social_feed__save_data() {
	
    check_admin_referer('myajax-nonce', 'nonce_code');

    try{
        $feed = new DSocialFeed();

        $socials_list = $feed->get_list1();

        $proerties = ['url','token'];

        $option = $feed->get_option(true);

        foreach($socials_list as $social){
            foreach($proerties as $prop){

                $name = $social.'_'.$prop;
                if(isset($_POST[$name])){
                    if(!isset($option['feeds'][$social]))
                        $option['feeds'][$social] = [];
                    $option['feeds'][$social][$prop] = $_POST[$name];
                }

            }
        }

        $option['tick'] = $option['tick'] + 1;

        $option['last_updated'] = (new DateTime(current_time( 'mysql')))->getTimestamp();

        $result = $feed->update_option($option);

        if(!$result)
            throw new Exception("Error Processing save option", 1);
        
        wp_send_json(array(
            'Result' => 'OK',
            'Message' => 'Сохранено',
        ));

    } catch (\Throwable $th) {
    // } catch (Exception $th) {
        wp_send_json(array(
            'Result' => 'error',
            'Message' => $th->getMessage(),
        ));
    }

}


//вызов синхронизации
function d_social_feed__sync_feed() {
    check_admin_referer('myajax-nonce', 'nonce_code');

    $social_name = $_POST['social_name'];

    try{
        $feed = new DSocialFeed();

        $socials_list = $feed->get_list1();

        $option = $feed->get_option(true);

        $net = $option['feeds'][$social_name];

        if(empty($social_name))
            throw new Exception("Нужно указать имя", 1);

        if(empty($net))
            throw new Exception("Социальная сеть не найдена", 1);

        //func call there
        $result = $feed->update_feed($social_name);       

        if(!$result)
            throw new Exception("Ошибка при синхронизации материалов", 1);

        $option['feeds'][$social_name]['last_sync'] = (new DateTime(current_time( 'mysql')))->getTimestamp();
        $result = $feed->update_option($option);

        if(!$result)
            throw new Exception("Непредвиденная ошибка", 1);
        
        wp_send_json(array(
            'Result' => 'OK',
            'Message' => 'Синхронизировано: '. $social_name,
        ));

    } catch (\Throwable $th) {
    // } catch (Exception $th) {
        wp_send_json(array(
            'Result' => 'error',
            'Message' => $th->getMessage(),
        ));
    }

}

function d_social_feed__sync_AllFeeds(){

    $errors = [];
    $messages = [];

    try {
        $feed = new DSocialFeed();

        $socials_list = $feed->get_list1();

        $option = $feed->get_option(true);

        foreach ($socials_list as $social_name) {
            
            try {
                

                $net = $option['feeds'][$social_name];

                if(empty($social_name))
                    throw new Exception("Нужно указать имя", 1);

                if(empty($net))
                    throw new Exception("Социальная сеть не найдена", 1);

                //func call there
                $result = $feed->update_feed($social_name);       

                if(!$result)
                    throw new Exception("Ошибка при синхронизации материалов", 1);

                $option['feeds'][$social_name]['last_sync'] = (new DateTime(current_time( 'mysql')))->getTimestamp();
                $result = $feed->update_option($option);

                if(!$result)
                    throw new Exception("Непредвиденная ошибка", 1);
                
                $messages[] = 'Синхронизировано: '. $social_name;

                //code...
            } catch (\Throwable $th) {
                $errors[] = $th->getMessage();
            }
        }

    } catch (\Throwable $th) {
        
        wp_send_json(array(
            'Result' => 'error',
            'Message' => $th->getMessage(),
        ));
        
    }

    wp_send_json(array(
        'Result' => 'OK',
        'Message' => $messages,
        'Errors' => $errors
    ));
}