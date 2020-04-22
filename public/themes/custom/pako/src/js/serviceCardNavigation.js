"use strict";

(function ($, Drupal) {
  Drupal.behaviors.serviceCardNavigation = {
    attach: function attach(context) {
      var mainNavLinks = $('.service-card__navigation-item', context);
      var navBar = $('.service-card__navigation-wrapper', context);
      var num = 100;

      $('.service-card__navigation-item').click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var link = $(this).attr('href').split('=')[0].replace('#','');
        var section = $('a[name="' + link + '"]');
        $('html, body').animate({
          scrollTop: $(section).offset().top - 125
        }, 50);
      });

      $(window).on('scroll', event => {
        let fromTop = window.scrollY + 70;

        if (fromTop > num) {
          navBar.addClass('fixed');
        } else {
          navBar.removeClass('fixed');
        }

        $.each(mainNavLinks, function(i, val) {
          var link = $(val).attr('href').split('=')[0].replace('#','');
          var section = $('a[name="' + link + '"]');

          if (
            section.offset().top <= fromTop &&
            section.offset().top + section.outerHeight() > fromTop
          ) {
            $(val).addClass('is-active');
          } else {
            $(val).removeClass('is-active');
          }
        });
      });
    }
  };
})(jQuery, Drupal);
