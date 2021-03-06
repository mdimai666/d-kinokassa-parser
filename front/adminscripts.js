document.addEventListener("DOMContentLoaded", function () {
    d_init_social_ready_a2(jQuery)
    d_init_social_feedpage(jQuery)
});


function d_init_social_ready_a2($) {

    console.log("d_init_social_ready");

    $.fn.extend({
        serializeJSON: function() {
            var formdata = $(this).serializeArray();
            var data = {};
            $(formdata ).each(function(index, obj){
                data[obj.name] = obj.value;
            });
            return data;
        }
    });

    $(function () {
        window.wpajax = function (action, data, callback) {
            var post_data = Object.assign({
                action: action,
                nonce_code: amai.nonce,
            }, data);

            $.post(ajaxurl, post_data, function (data) {
                (callback && callback(data));
            }, 'json');

        }
    })

}


function d_init_social_feedpage($) {

    if(!$('body').hasClass('toplevel_page_d_social_feed')) return;

    var feed = {};

    function submitForm1(callback){
        let form =  $('form#social_list');
        $('#publishing-action .spinner').addClass('is-active');
        var postdata = $(form).serializeJSON();

        // console.warn('save', postdata);

        wpajax('d_social_feed_save_data', postdata, function (data) {
            console.log(data);
            $('#publishing-action .spinner').removeClass('is-active');

            if(data.Result == 'OK')
                feed.show_message(data.Message);
            else 
                feed.show_error(data.Message || 'произошла ошибка');

            if(callback) callback(data.Result == 'OK');
           
        });
    }

    $('form#social_list').submit(function (e) {
        e.preventDefault();

        submitForm1();
        
        return false;
    });


    feed.show_message = function(text, divclass) {

        divclass = divclass || 'notice-success';

        var html = $(`<div id="message-success" class="notice  is-dismissible ${divclass}">
            <p>
                ${text}
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Скрыть это уведомление.</span>
            </button>
        </div>`);

        html.find('.notice-dismiss').click(function(){ html.hide(); });

        $('#messages').html(html);
    }

    feed.show_error = function(text){
        feed.show_message(text, 'notice-error');
    }

    //кнопка синхронизации
    $('.update-feed-action .button').click(function(){
        $(this).parent().find('.spinner').addClass('is-active');
        $('#messages').html('');

        var social_name = $(this).parents('tr').attr('data-social');

        var postdata = {
            social_name: social_name,
        }

        var self = this;

        wpajax('d_social_feed_sync_feed', postdata, function (data) {
            console.log(data);
            $(self).parent().find('.spinner').removeClass('is-active')
            $(self).parents('tr').find('.last_sync').html('Обновлено');

            if(data.Result == 'OK')
                feed.show_message(data.Message);
            else 
                feed.show_error(data.Message || 'произошла ошибка');
           
        });
    });

    $('.feed-tools .delete').click(function(){
        $(this).parents('tr').remove();
    })

    $('.d-feed-add-button').click(async function(){
        
        let slug = await prompt('Напишите SLUG кинотеатра (будет использоваться для shortcode)');

        if(!slug) return;

        slug = slug.replace(/^https?:\/\//,'')
        slug = slug.replace(/[^A-Za-z\.\-_0-9]/g,'')

        // console.warn('slug', slug);

        let tr = $(`
            <tr>
                <td>
                    ${slug}
                    <span class="spinner is-active"></span>

                    <input type="hidden" 
                    name="feeds[${slug}][name]" size="30" 
                    value="${slug}" 
                    id="feeds[${slug}][url]" 
                    spellcheck="true" autocomplete="off">
                </td>
            </tr>
        `)

        $('table.feeds-table').append(tr);

        submitForm1(function(success){
            if(success){
                location.reload();
            }
        })


    })

    // function fade_message() {
    //     $('#saved').fadeOut(1000);
    //     clearTimeout(t);
    // }

    window.d_social_feed = feed;
}