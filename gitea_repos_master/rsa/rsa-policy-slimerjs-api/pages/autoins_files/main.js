$( document ).ready(function() {


    $('.form-select').selectric();

    $('input, textarea').placeholder();

    $('.table').footable();

    jQuery.datetimepicker.setLocale('ru');
    jQuery('.datepicker').each(function() {
        jQuery(this).datetimepicker({
            timepicker: false,
            format:'d.m.Y',
            scrollMonth : false,
            scrollInput : false
        });
    });


  $('.b-mainpage-topslider').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: true
  });

  /* Girds */
  $('.b-content-grid-items').isotope({
    itemSelector: '.b-content-grid-item',
    masonry: {
      columnWidth: 320
    },
    transitionDuration: '0.2s'
  });


  /* popup open */
  $('tr[data-popup], a[data-popup]').on('click', function(e) {
      e.preventDefault();
      var target = $(this).data('popup');
      $('.popup_background').fadeIn();
      $(target).fadeIn();
  });

  /* popup close */
  $('.popup-block .close-popup').on('click', function(e) {
    e.preventDefault();
    $('.popup_background').fadeOut();
    $(this).parents('.popup-block').fadeOut();
  });


  /* Clone item */

  $(".checkbox#unlim").change(function(e) {
      if(this.checked) {
        $(this).parents('.form-block').find('.form-item').hide();
      } else {
        $(this).parents('.form-block').find('.form-item').show();
      }
  });

  $('body').on('click', '.clone-link', function(e) {
    e.preventDefault();
    var cloneItem = $(this).parents(1).find('.clone-item').last();
    $('.form-select').selectric('destroy');
    cloneItem.clone().insertAfter(cloneItem);
    $('.form-select').selectric('init'); 
  });

  $('body').on('click', '.delete-link', function(e) {
    e.preventDefault();
    $(this).parents('.clone-item').remove();
  });

  /* toggle blocks  */

  $('body').on('click', '.toggle-link', function(e) {
    e.preventDefault();
    $(this).next('.toggle-block').toggle(200);
  });


});



$(window).scroll(function(){

});


$(window).on('resize', function(){

});
