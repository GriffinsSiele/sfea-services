/**
 * Компонент отображения процесса загрузки.
 * Отображается на весь экран, делая не кликабельными элементы на странице:
 * Использование:
 *  LoadingImage.show(); - Отобразить процесс загрузки
 *  LoadingImage.hide(); - Скрыть процесс загрузки
 * @type {{show, hide}}
 */
var LoadingImage = (function(){

    var ID_ELEMENT = 'loadingImage';

    var $component;
    var $body;
    var isShow = false;

    $(function init(){
        $body = $('body');
        var $container = $('<div/>', {id: ID_ELEMENT});
        var $picture = $('<div/>');
        $picture.appendTo($container);
        $container.appendTo($body);

        $component = $container;
        if(isShow){
            show();
        }
    });

    var show = function () {
        if(checkExistsElement()){
            $component.show();
            $body.css('overflow', 'hidden');
        }
        isShow = true;
    };

    var hide = function () {
        if(checkExistsElement()){
            $component.hide();
            $body.css('overflow', 'auto');
        }
        isShow = false;
    };

    function checkExistsElement() {
        return !!$component;
    }

    return {
        show: show,
        hide: hide
    }
})();