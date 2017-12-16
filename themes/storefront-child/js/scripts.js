var DEBUG = true;

if (DEBUG) {
    console.log('DEBUG IS ENABLED.');
}

function debug() {
    if (DEBUG) {
        for (var i = 0; i < arguments.length; i++) {
            console.log(arguments[i]);
        }
    }
}
// ---------------------------------------------------------------------------------

/**
 * Product select box handler.
 *
 * Only run on the shop page and single product page.
 */

(function($) {
    var shopPage    = window.location.href.includes('shop');
    var productPage = window.location.href.includes('product');

    // Run initialization for each select box
    $('.wcgp-select').each(function() {
        var form   = $(this).parent();
        var button = $(this).siblings('button')[0];

        // on shop page, form will have an attribute called 'default'
        // that stores the initial value of the form's 'action' attribute
        if (shopPage) {
            form.attr('default', form.attr('action'));
        }
        // on product page, button will have an attribute called 'default'
        // that stores the initial value of the button's 'value' attribute
        else if (productPage) {
            $(button).attr('default', $(button).attr('value'));
        }
    });

    debug('Forms initialized.');

    // change the action of the add-to-cart form every time
    // the value of the select box is changed.
    $(document).on('change', '.wcgp-select', function(e) {

        // info from select box
        var options = this.options;
        var index   = e.target.selectedIndex;
        var action  = options[index].value;

        // form elements
        var form       = $(this).parent();
        var cartButton = $(this).siblings('button');

        // product id
        var productId = action.split(/add-to-cart\=/)[1];

        // change the action of the form
        if (shopPage) {
            form.attr('action', action ? action : form.attr('default'));
        }
        // change the value of the button
        else if (productPage) {
            $(cartButton).attr('value', productId ? productId : $(cartButton).attr('default'));
        }

        debug('Select box changed.. ', action);
    });
})(jQuery);

// ---------------------------------------------------------------------------------

/**
 * Add a click listener to the button on the sidebar so that it can toggle
 * the filter box on mobile.
 */

(function($) {

    // Mobile product filter
    $('#lcgc-toggle-filter').on('click', function() {
        toggleElement('#secondary', filter)
    });
    debug('Added mobile filter listener.');

    // Mobile navigation menu
    $('#lcgc-toggle-mobile-nav').on('click', function() {
        toggleElement('.storefront-primary-navigation', nav)
    });
    debug('Added mobile nav listener.');

    // we use objects so that we can
    // pass to the function by reference,
    // instead of passing by value with
    // a regular boolean.
    var filter = { visible: false };
    var nav    = { visible: false };

    function toggleElement(selector, flag) {
        flag.visible = !flag.visible;

        if (flag.visible) {
            debug('Element is now visible');

            $('.site-branding').addClass('grayed-out');
            $('#primary')      .addClass('grayed-out');

            $(selector).addClass('visible');

            // hide the element when the window is resized
            // with it open.
            $(window).resize(function() { toggleElement(selector, flag); });
        }
        else {
            debug('Element is now hidden.');

            $('.site-branding').removeClass('grayed-out');
            $('#primary')      .removeClass('grayed-out');

            $(selector).removeClass('visible');

            // remove the resize listener when
            // the element is closed.
            $(window).off('resize');
        }
    }
})(jQuery);

// ---------------------------------------------------------------------------------

/**
 * -- DEPRECATED --
 * Set the sidebar to fixed position once scrolled to certain position.
 *
  (function($) {
      // don't run on the product page or the cart page
      if (window.location.href.includes('shop')) {
          var minWidth        = 767;
          var elementPosition = $('#secondary').offset();

          $(window).scroll(function() {
              if($(window).scrollTop() > elementPosition.top - 20 && $(window).width() > minWidth) {
                  $('#secondary').addClass('fixed-position');
              }
              else {
                  $('#secondary').removeClass('fixed-position');
              }
          });
      }
  })(jQuery);
*/

// ---------------------------------------------------------------------------------

/**
 * Handle the product filter sidebar.
 */

(function($) {
  // TODO: implement loader class
  var loaderVisibilityClass   = '';
  var minimumTransitionDelay  = 400;
  var productsVisibilityClass = 'invisible';

  var selectBox       = $('#lcgc-attribute-filter');
  var checkboxes      = $('#secondary .attributes input[type="checkbox"]');
  var requestLocation = window.location.href.split('shop')[0] + 
                        'product-filter';

  // TODO: implement loader reference
  var loader       = $('');
  var products     = $('ul.products');
  var originalHTML = null;

  // Checkbox event handlers
  $(checkboxes).on('change', function(e) {
    var checked        = this.checked;
    var categoryName   = $(this).parent().parent().siblings('h4').attr('name');
    console.log(categoryName);
    var attributeName  = this.name;
    var attributeValue = $( $(this).siblings()[0] ).text();
    var attrString    = '';

    if (checked) {
      // uncheck all the other boxes
      $(this).closest('.subcategory').find('input').prop("checked", false);
      // check this one
      $(this).prop("checked", true);
    }
    else {
      // uncheck this one
      $(this).prop("checked", false);
    }

    $.each($(checkboxes), function() {
      var currentAttributeName  = this.name;
      var currentAttributeValue = $( $(this).siblings()[0] ).text();

      if (this.checked) {
        attrString += currentAttributeName + ':' + currentAttributeValue + ';';
      }
    });

    var newURL = requestLocation + '?category=' + categoryName + '&attribute=' + attrString;

    console.log(newURL);
    replaceProducts(newURL);
  });

  // Select box Event Handler
  $(selectBox).on('change', function(e) {
    var options      = this.options;
    var index        = e.target.selectedIndex;
    var categoryName = options[index].value;
    var fullReqURL   = requestLocation + '?category=' + categoryName;

    if (categoryName === 'choose' && originalHTML !== null) {
      showSidebarElements();
      replaceProducts();
    }
    else if (categoryName !== 'choose') {
      if (originalHTML === null)
        originalHTML = $(products).html();

      showSidebarElements(categoryName);
      replaceProducts(fullReqURL);
    }
  })

  function toggleClass(element, className) {
    return $(element).hasClass(className) ?
      $(element).removeClass(className)   :
      $(element).addClass(className);
  }
  function toggleProductVisibility(productsVisibilityClass) { $(products).toggleClass(productsVisibilityClass); }
  function toggleLoaderVisibility(loaderVisibilityClass)    { $(loader).toggleClass(loaderVisibilityClass);     }
  function setProductsHTML(html) {
    $(products).html(html);
  }

  function showSidebarElements(categoryName) {
    $('#secondary .attributes > div').fadeOut();

    if (categoryName) {
      var selector = '#secondary .' + categoryName + '-attribute-filter';
      setTimeout(function() { $(selector).fadeIn(); }, 500);
    }
  }

  function replaceProducts(requestURL) {
    toggleLoaderVisibility(loaderVisibilityClass);
    toggleProductVisibility(productsVisibilityClass);

    setTimeout(function() {
      if (requestURL) {
        $.get(requestURL, function(response) {
          var obj     = $.parseHTML(response);
          var newHTML = $(obj).find('ul.products').html();

          setProductsHTML(newHTML);
          toggleProductVisibility(productsVisibilityClass);
          toggleLoaderVisibility(loaderVisibilityClass);
        });
      }
      else {
        setProductsHTML(originalHTML);
        toggleProductVisibility(productsVisibilityClass);
        toggleLoaderVisibility(loaderVisibilityClass);
      }
    }, minimumTransitionDelay);
  }
})(jQuery);
