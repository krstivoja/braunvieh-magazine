jQuery(document).ready(function ($) {
	//show sidebar menu on menu depth 3
	$('.current_page_item.level-2').parents('.menu-item-has-children.level-0').show();
	//close megamenu if anchor link
	$('a[href*=\\#]').on('click', function (event) {
		if(this.pathname === window.location.pathname){
			$('#fullscreen-menu.open .menu-toggle')[0].click();
		}
	});
	//open external links in new tab
	function externalLinks() {
	  for(var c = document.getElementsByTagName("a"), a = 0;a < c.length;a++) {
		var b = c[a];
		b.getAttribute("href") && b.hostname !== location.hostname && (b.target = "_blank")
	  }
	};
	externalLinks();
	//footer newsletter
	$("#form-form").submit(function(e) {
	e.preventDefault();
	var form = $(this);
	$("button").prop("disabled", true);
	$.ajax({
		type: "POST",
		url: form.attr("action"),
		data: form.serialize(),
		dataType: "json",
		success : function(data)
		{
			console.log(data);
			if (data.responseText && data.responseText.length > 0)
			{
				document.write(data.responseText);
			}
			else
			{
				$("#form-content").hide();
				$("#form-landing-page").show();
			}
		},
		error : function(data)
		{
			console.log(data);
			if (data.responseText && data.responseText.length > 0)
			{
				document.write(data.responseText);
			}
		},
		complete : function(data)
		{
			$("button").prop("disabled", false);
		}
	});
	});
	new URLSearchParams(window.location.search).forEach(function(value, key) {
		var el = document.getElementsByName(key);
		if (el.length) {
			el.forEach(function (inp) {
				if ("INPUT" == inp.tagName) {
					if ("checkbox" == inp.type) {
						inp.checked = inp.value == value;
					} else if ("radio" == inp.type) {
						inp.checked = inp.value == value;
					} else {
						inp.value = value;
					}
				} else if ("SELECT" == inp.tagName && inp.options.length) {
					for (var i = 0; i < inp.options.length; i++) {
						if (inp.options[i].value == value) {
							inp.options[i].selected = true;
						}
					}
				}
			});
		}
	})
	//
    if ($().slick) {
        // banner slider
        $('#banner').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: true,
            infinite: true,
            arrows: true,
            dots: false,
            autoplay: true,
            autoplaySpeed: 3000
        });
        $('#banner').css('opacity','1');
        // news mobile slider
        $('#news-mobile #slick-wrap').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: true,
            infinite: true,
            arrows: false,
            dots: false,
            centerMode: true
        });
        // shop mobile slider
        $('#shop-mobile #shop-wrap').slick({
            slidesToShow: 2,
            slidesToScroll: 1,
            adaptiveHeight: true,
            infinite: true,
            arrows: false,
            dots: false,
            centerMode: true
        });
    }
    // sidebar menu
    $('.page-template-page-modular #content .col-3 .menu a').click(function(){
        if($(this).parent().hasClass('menu-item-has-children')){
            $(this).parent().find('.sub-menu').slideToggle();
            $(this).toggleClass('open');
            return false;
        }
    });
    // switch language mobile
    $('.wpml-ls-current-language a').click(function(){
        let ww = $(window).width();
        if(ww<992){
            $(this).parent().parent().toggleClass('open');
            return false;
        }
    });
    $('#language-switcher').mouseleave(function(){
        let ww = $(window).width();
        if(ww<992){
            $(this).find('.open').removeClass('open');
        }
    });
    // last hovered
    $('#fullscreen-menu .menu li a').hover(function(){
        $(this).parent().parent().find('li').each(function(){
            $(this).find('a:eq(0)').removeClass('hovered');
        });
        $(this).addClass('hovered');
    });
    window_ratio();
    $('.search-toggle').click(function(){
        $(this).toggleClass('opened');
        $(this).parent().find('.search-form').toggle();
    });
    $('.expanding-title').click(function(){
        $(this).toggleClass('open');
        $(this).parent().find('.expanding-content').slideToggle();
    });
    $('.downloads-toggle').click(function(){
        $(this).toggleClass('open');
        $(this).parent().find('.download-links').slideToggle();
    });
    $('.menu-toggle').click(function(){
        $('#fullscreen-menu, body').toggleClass('open');
    });
    $('#fullscreen-menu .menu-item-has-children').each(function(){
        if($(this).hasClass('level-0')){
            $(this).find('a:first').addClass('toggler');
        }else{
            $(this).find('a:first').addClass('toggler-1');
        }
    });
    $('.menu-item-has-children.level-0 a').hover(function(){
        let ww = $(window).width();
        if(ww>991){
            if($(this).hasClass('toggler')){
                $('#fullscreen-menu .sub-menu').hide();
                $(this).parent().find('.sub-menu:eq(0)').show();
            }
        }
    });
    $('.menu-item-has-children.level-1 a').hover(function(){
        let ww = $(window).width();
        if(ww>991){
            if($(this).hasClass('toggler-1')){
                $('#fullscreen-menu .level-1 .sub-menu').hide();
                $(this).parent().find('.sub-menu:eq(0)').show();
            }
        }
    });
    // Mobile menu toggle
    $('.menu-item-has-children .toggler').click(function(){
        let ww = $(window).width();
        if(ww<992){
            if($(this).parent().hasClass('open')){
                $(this).parent().removeClass('open');
                $(this).parent().find('.sub-menu:eq(0)').slideToggle(100);
                $('.sub-menu .sub-menu').hide();
            }else{
                $(this).parent().parent().find('.open .sub-menu').slideToggle(100);
                $(this).parent().parent().find('.open').removeClass('open');
                $(this).parent().addClass('open');
                $(this).parent().find('.sub-menu:eq(0)').slideToggle(100);
                $('.sub-menu .sub-menu').hide();
            }
            return false;
        }
    });
    $('.menu-item-has-children .toggler-1').click(function(){
        let ww = $(window).width();
        if(ww<992){
            if($(this).parent().hasClass('open')){
                $(this).parent().removeClass('open');
                $(this).parent().find('.sub-menu').slideToggle(100);
            }else{
                $(this).parent().parent().find('.open .sub-menu').slideToggle(100);
                $(this).parent().parent().find('.open').removeClass('open');
                $(this).parent().addClass('open');
                $(this).parent().find('.sub-menu').slideToggle(100);
            }
            return false;
        }
    });
    // function link_is_external(link_element) {
    //     return (link_element.host !== window.location.host);
    // }
    // $('a').each(function() {
    //     if (link_is_external(this)) {
    //         $(this).addClass('external');
    //     }
    // });
    // Filter projects by tags
    $('#filter').submit(function(){
        var filter = $('#filter');
        $.ajax({
            url:filter.attr('action'),
            data:filter.serialize(),
            type:filter.attr('method'),
            beforeSend:function(xhr){
                $('.woocommerce-pagination').remove();
                $('.products.columns-4').css('opacity','0.5');
            },
            success:function(data){
                $('ul.products').html(data);
                $('.products.columns-4').css('opacity','1');
            }
        });
        return false;
    });
    $('.category-links a').click(function(){
        $('.category-links a').removeClass('active');
        $(this).addClass('active');
        let tag_name = $(this).text();
        $('#filter select option').each(function(){
            if($(this).text()===tag_name){
                let selected_value = $(this).attr('value');
                $('#filter select').val(selected_value);
                $('#filter').submit();
            }
        });
        return false;
    });
    parallax_height();
});
jQuery(window).resize(function ($) {
    window_ratio();
    parallax_height();
});
function window_ratio(){
    let ww = jQuery(window).width();
    let wh = jQuery(window).height();
    let ratio = ww/wh;
    if(ratio<1.6){
        jQuery('#content').addClass('low-ratio');
    }else{
        jQuery('#content').removeClass('low-ratio');
    }
}
// home parallax height
function parallax_height(){
    let ww = jQuery(window).width();
    let paralax_height = ww*0.32;
    if(ww<992){
        paralax_height = ww*0.64;
    }
    jQuery('.parallax-image').css('min-height',paralax_height);
}