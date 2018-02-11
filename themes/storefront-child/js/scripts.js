var DEBUG = false;

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

/**
 * Add to Cart via AJAX
 */

(function($) {
  $(document).ready(function() {
    function ajaxAddToCart(e) {
      e.preventDefault();

      var action           = $(this).parent().attr('action');
      var productContainer = $(this).parent().parent();

      var form = $(e.target).closest('form');

      var newQuantity     = $(form).find('.input-text').val();
      var newQuantityPack = $(form).find(':selected').text();

      if (newQuantityPack.includes('Qty/Pk')) {
        alert('Please select a quantity pack before adding an item to your cart.');
      }
      else {
        if (action.includes('&quantity')) {
          action = action.replace(/quantity\=\d{1,}/, 'quantity=' + newQuantity);
        }
        else {
          action += '&quantity=' + newQuantity;
        }

        $.get(action, function(res) {
          // TODO: change logic for product page vs product-archive
          // page.

          var html  = jQuery.parseHTML(res);
          var notif = $(html).find('.woocommerce-message');

          var notificationText = notif.text().includes('added to your cart') ?
            'The item has been added.<br><a href="' + window.location.href.replace('shop', 'cart') + '">Click here to view your cart</a>' :
            'Sorry, please try again.';

          // Replace the old cart with the new cart
          var newCart = $(html).find('#site-header-cart');
          $('#site-header-cart').replaceWith(newCart);

          // Replace the old footer bar with the new footer bar
          var newFooterBar = $(html).find('.storefront-handheld-footer-bar');
          $('.storefront-handheld-footer-bar').replaceWith(newFooterBar);

          // Display the notification text
          $(productContainer).append('<div class="notification" style="display: none;">' + notificationText + '</div>');
          $(productContainer).find('.notification').fadeIn('slow');

          // fade it in
          setTimeout(function () {
            $(productContainer).find('.add-to-cart.button').addClass('added');
          }, 10);

          // fade it out
          setTimeout(function () {
            $(productContainer).find('.notification').fadeOut();
            $(productContainer).find('.add-to-cart.button').removeClass('added');

            setTimeout(function () {
              $(productContainer).find('.notification').remove();
            }, 600);
          }, 8200);
        });
      }
    }
    window.ajaxAddToCart = ajaxAddToCart;
    addAjaxCartListeners();
  });
})(jQuery);

function addAjaxCartListeners() {
  jQuery('.add-to-cart.button').off('click', window.ajaxAddToCart);
  jQuery('.add-to-cart.button').on('click', window.ajaxAddToCart);
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
 * Handle the product filter sidebar.
 */

(function($) {

  function filterProducts(filterArguments) {
    var data = {
      'action'        : 'get_filter_exclusions',
      'category_slug' : $('#lcgc-attribute-filter').val(),
      'filter_args'   : filterArguments
    };

    showLoader();

    setTimeout(function() {

      $.post(ajaxurl, data, function(response) {
        var html     = $.parseHTML(response);
        var newProducts = $(html).find('ul.products').html();
        var oldProducts = $('ul.products');

        var exclusionString = $(html).find('#exclusion-string').html();
        var exclusionObject = JSON.parse(exclusionString);

        // TODO:
        // add in loader/smooth transition
        //
        // TODO: parse the exclusion string and disable those elements

        if ($(oldProducts)[0]) {
          $(oldProducts).html(newProducts);
        }
        else {
          $('#main').html('<ul class="products">' + newProducts + '</ul>');
        }

        var localBlacklist = {
          'Similar to' : true,
          'Qty/pk'     : true,
          'Temp.'      : true
        };

        $('div.disabled').removeClass('disabled');

        for (var attributeName in exclusionObject) {
          var attribute = exclusionObject[attributeName];


          if (!localBlacklist[attributeName]) {
            for (var valueName in attribute) {

              $(".subcategory[name='"+attributeName+"']")
                .find("div:contains('"+valueName+"')")
                .addClass('disabled');
            }
          }
        }

        addAjaxCartListeners();

        setTimeout(function() {
          hideLoader();
        }, 300);
      });

    }, 400);

  }

  function showLoader() {
    $('#loader').css('display', 'flex').hide().fadeIn();
  }

  function hideLoader() {
    $('#loader').fadeOut();
  }



  // TODO: implement loader class
  var loaderVisibilityClass   = '';
  var minimumTransitionDelay  = 400;
  var productsVisibilityClass = 'invisible';

  // NOTE:
  // I used a select box instead of radio buttons because you need
  // to be able to "deselect" the one that is currently checked.
  var selectBox       = $('#lcgc-attribute-filter');
  var checkboxes      = $('#secondary .attributes input[type="checkbox"]');
  var requestLocation = window.location.href.split('shop')[0] + 
    'product-filter';

  // TODO: implement loader reference
  var loader       = $('');
  var products     = $('ul.products');
  var originalHTML = null;

  // Checkbox event handlers
  // This fires every time a checkbox is changed. We should never
  // have to re-add these because all of the checkboxes are always
  // present on the page; changing the category that you're filtering
  // for only shows / hidesthem.
  $(checkboxes).on('change', function(e) {
    var checked        = this.checked;
    var categoryName   = $(this).parent().parent().siblings('h4').attr('name');
    var attributeName  = this.name;
    var attributeValue = $( $(this).siblings()[0] ).text();
    var attrString    = '';

    if (checked) {
      var siblingInputs   = $(this).closest('.subcategory').find('input');
      var subcategoryName = $(this).closest('.subcategory').attr('name');

      // uncheck all the boxes in this subcategory
      // and disable them (since we can only have one
      // at a time)
      $(this)
        .closest('.subcategory')
        .find('input')
        .prop("checked", false);

      // check and enable this one
      $(this)
        .prop("checked", true);
    }
    else {
      // uncheck this one
      $(this)
        .prop("checked", false);

      // enable all of its siblings
      // $(this)
      //   .closest('.subcategory')
      //   .find('input')
      //   .prop("checked", false)
      //   .removeClass('disabled');
    }

    var filter_args = {};

    // TODO:
    // Right now we are looping through every single checkbox on the
    // page, but instead, it might be a better idea to loop through
    // the different subcategories and used the :checked jQuery
    // selector to help with performance and save some loop time.
    $.each($(checkboxes), function() {
      var currentAttributeName  = this.name;
      var currentAttributeValue = $( $(this).siblings()[0] ).text();

      if (this.checked) {
        filter_args[currentAttributeName] = currentAttributeValue;
      }
    });

    filterProducts(filter_args);

  });

  // Select box Event Handler
  // This fires every time the Category select box is changed.
  $(selectBox).on('change', function(e) {
    var options      = this.options;
    var index        = e.target.selectedIndex;
    var categoryName = options[index].value;
    var fullReqURL   = requestLocation + '?category=' + categoryName;



    if (categoryName === 'choose' && originalHTML !== null) {
      showSidebarElements();
      replaceProducts(); // calling w/o args shows original products
    }
    else if (categoryName !== 'choose') {
      if (originalHTML === null)
        originalHTML = $(products).html();

      filterProducts(null);
      showSidebarElements(categoryName);
      // replaceProducts(fullReqURL);
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

    // When the filter is used and the products are replaced in the store,
    // we need to ensure that the listeners on the Add to Cart buttons
    // are re-added to the new HTML.
    addAjaxCartListeners();
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

          // A formatted string containing the information about all
          // of the other filter options that should be disabled
          // is stored in a hidden input with the id of '#exclusion-string'.
          // If it exists, we need to pull it out, parse it, and add
          // a 'disabled' class to all of the appropriate filter
          // attributes.
          var exclusionString = $(obj).find('#exclusion-string-div').html();


          $('.subcategory div').removeClass('disabled');

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

// ---------------------------------------------------------------------------------

/**
 * Handle the banner on the homepage
 */

(function($) {
  setTimeout(function() {
    var bannerContainer = document.querySelector('.lcgc-homepage .banner');

    if (bannerContainer) {
      var numBanners = bannerContainer.children.length;

      function rotateBanner(currentBanner) {
        $( $(bannerContainer).children()[currentBanner] ).fadeOut(function() {
          if (currentBanner < numBanners - 1) { currentBanner++;   }
          else                                { currentBanner = 0; }

          $( $(bannerContainer).children()[currentBanner] ).fadeIn(function() {
            setTimeout(function() {
              rotateBanner(currentBanner);
            }, 5000);
          })
        });
      }

      setTimeout(function() { rotateBanner(0); }, 5000);
    }
  }, 1000);
})(jQuery);
