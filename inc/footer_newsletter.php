<?php

// Newsletter shortcode function
function fn_footer_newsletter() { 
    $string = '<script src="https://url.xcpro.ch/js/xccaptcha.jsp" type="text/javascript"></script><div id="form-content">
            <form id="form-form" method="post" action="https://url.xcpro.ch/dispatcher/service">
                <input type="hidden" name="ac" value="reg" />
                <input type="hidden" name="clientCode" value="g7dkiJFutI5Q7ZWT03" />
                <input type="hidden" name="doubleOptin" value="1" />
                <input type="hidden" name="xp_sendBackParams" value="0" />
                <input type="hidden" name="SprachCode" value="de" />
                <input type="hidden" name="xclFormId" value="221108110824761127" />
                <input type="hidden" name="rental_flag" value="0" />
                <div class="main-heading" style=""><h1>
        Newsletter abonnieren
    </h1></div>
                <div class="form-group">
                    <select name="Anrede">
                        <option value="">Anrede</option>
                        <option value="Frau">Frau</option>
                        <option value="Herr">Herr</option>
                    </select>
                </div>
                <div class="form-group">
                        <input id="item-3" type="text" name="Vorname"  placeholder="Vorname"   />
                </div>
                <div class="form-group">
                        <input id="item-4" type="text" name="Nachname"  placeholder="Nachname"   />
                </div>
                <div class="form-group">
                        <input id="item-5" type="email" name="Email"  placeholder="E-Mail-Adresse"  required   />
                </div>
                <div class="form-group form-checkbox">
                    <input id="item-6" type="checkbox" name="profiling_allowed" value="1"   required />
                    <a href="https://homepage.braunvieh.ch/datenschutzerklaerung" target="_blank">Ich erkl&auml;re meine Einwilligung zur Erhebung, Verarbeitung oder Nutzung meiner personenbezogenen Daten gem&auml;ss der Datenschutzerkl&auml;rung.</a>
                </div>
                <input id="item-7" type="hidden" name="Newsletter" value="1" />
                <div class="xc-captcha-challenge" style="display:none" data-sitekey="eiafow8zsmxVk9VaCJu6jw"></div>
                <div class="btn-container">
                    <button type="submit">Senden</button>
                </div>
                    <div class="xc-captcha" style="position: fixed;right: 1rem;bottom: 1rem;" data-sitekey="eiafow8zsmxVk9VaCJu6jw"></div>
            </form>
        </div>
    
        <div id="form-landing-page" style="display:none;">
            <h1>Vielen Dank f&uuml;r Ihre Registration</h1>
            <p>Wir haben Ihnen eine E-Mail geschickt, um Ihre Newsletter-Registrierung zu bestätigen.</p>
        </div>
    
        <script type="text/javascript">
    const xcCaptcha = new XcCaptcha();
    $("#form-form").submit(function(e) {
        e.preventDefault();
        var form = $(this);
    
        xcCaptcha.onConfirm(function() {
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
        }, $("button"));
    });
    function fillInputs(dataMap) {
        if (!dataMap) {
            return;
        }
        dataMap.forEach(function(value, key) {
            var el = document.getElementsByName(key);
            if (el.length) {
                el.forEach(function (inp) {
                    if ("INPUT" == inp.tagName) {
                        if ("checkbox" == inp.type) {
                            if (Array.isArray(value)) {
                                inp.checked = value.indexOf(inp.value) > -1;
                            } else {
                                inp.checked = inp.value == value;
                            }
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
        });
    }
    var query = new URLSearchParams(window.location.search);
    fillInputs(query);
    if (query.get("lp.f")) {
        $("#form-content").hide();
        $("#form-landing-page").show();
    }
        </script>';

        // English uses a different newsletter provider form (SprachCode/xclFormId), not just translated labels.
        // TranslatePress sets the WP locale per language; revisit if English becomes a separate page tree.
        if ( strncmp( get_locale(), 'en', 2 ) === 0 || ( function_exists( 'braunvieh_is_english' ) && braunvieh_is_english() ) ) {
            $string = '<script src="https://url.xcpro.ch/js/xccaptcha.jsp" type="text/javascript"></script><div id="form-content">
            <form id="form-form" method="post" action="https://url.xcpro.ch/dispatcher/service">
                <input type="hidden" name="ac" value="reg" />
                <input type="hidden" name="clientCode" value="g7dkiJFutI5Q7ZWT03" />
                <input type="hidden" name="doubleOptin" value="1" />
                <input type="hidden" name="xp_sendBackParams" value="0" />
                <input type="hidden" name="SprachCode" value="en" />
                <input type="hidden" name="rental_flag" value="0" />
                <input type="hidden" name="xclFormId" value="0j3VhOTiTohhZc6PDQZg" />
                <div class="main-heading" style=""><h1>
        Subscribe to our newsletter
    </h1></div>
                <div class="form-group">
                    <select name="Anrede">
                        <option value="">Salutation</option>
                        <option value="Frau">Women</option>
                        <option value="Herr">Men</option>
                    </select>
                </div>
                <div class="form-group">
                        <input id="item-3" type="text" name="Vorname"  placeholder="First name"   />
                </div>
                <div class="form-group">
                        <input id="item-4" type="text" name="Nachname"  placeholder="Last name"   />
                </div>
                <div class="form-group">
                        <input id="item-5" type="email" name="Email"  placeholder="E-Mail"  required   />
                    <div role="alert" id="item-5-error" aria-hidden="true" class="item-error"></div>
                    <input type="hidden" name="Email__origval" />
                </div>
                <div class="form-group form-checkbox">
                    <input id="item-6" type="checkbox" name="profiling_allowed" value="1"   required />
                    <label for="item-6"  class="required"  >
    <a href="https://homepage.braunvieh.ch/datenschutzerklaerung" target="_blank">I hereby give my consent to the collection, processing or use of my personal data in accordance with the privacy policy.</a>
                    </label>
                </div>
                    <input id="item-7" type="hidden" name="Newsletter" value="1"  />
                <div class="btn-container">
                    <button type="submit" class="" style="">Send</button>
                </div>
                <div class="xc-captcha-challenge" style="display:none" data-sitekey="eiafow8zsmxVk9VaCJu6jw"></div>
                    <div class="xc-captcha" style="position: fixed;right: 1rem;bottom: 1rem;" data-sitekey="eiafow8zsmxVk9VaCJu6jw"></div>
            </form>
        </div>

        <div id="form-landing-page" style="display:none;">
            <h1>Thank you for your registration</h1>
            <p>We have sent you an e-mail to confirm your newsletter subscription.</p>
        </div>
    
        <script type="text/javascript">
    const xcCaptcha = new XcCaptcha();
$("#form-form").submit(function(e) {
	e.preventDefault();
	var form = $(this);

	xcCaptcha.onConfirm(function() {
		$("button").prop("disabled", true);
		// email check first
		var params = form.serialize();
		$.ajax({
			type: "POST",
			url: form.attr("action"),
			data: params + "&echck=1",
			dataType: "json",
			success : function(data) {
				if (data.failed) {
					if (data.fieldResults) {
						data.fieldResults.forEach(function (field) {
							$("input[name=" + field.name + "]").addClass("invalid");
							let errorMsg = $("input[name=" + field.name + "]").next("div.item-error");
							$("input[name=" + field.name + "__origval]").val(field.value);
							errorMsg.text(field.message);
							errorMsg.show();
							$("input[name=" + field.name + "]").on("keyup", function() {
								if ($("input[name=" + field.name + "]").val() != $("input[name=" + field.name + "__origval]").val()) {
									errorMsg.hide();
									$("input[name=" + field.name + "]").removeClass("invalid");
								} else {
									errorMsg.show();
									$("input[name=" + field.name + "]").addClass("invalid");
								}
							});
						});
					}
				} else {
				// proper form post
				$("button").prop("disabled", true);
				$.ajax({
					type: "POST",
					url: form.attr("action"),
					data: params,
					dataType: "json",
					success : function(data) {
						if (data.responseText && data.responseText.length > 0) {
							document.write(data.responseText);
						} else {
							$("#form-content").hide();
							$("#form-landing-page").show();
						}
					},
					error : function(data) {
						console.log(data);
						if (data.responseText && data.responseText.length > 0) {
							document.write(data.responseText);
						}
					},
					complete : function(data) {
						$("button").prop("disabled", false);
					}
				});
				}
			},
			error : function(data) {
				// display errors if any
				console.log(data);
				if (data.responseText && data.responseText.length > 0) {
					document.write(data.responseText);
				}
			},
			complete : function(data) {
				$("button").prop("disabled", false);
			}
		});
	}, $("button"));
});



function fillInputs(dataMap) {
	if (!dataMap) {
		return;
	}
	dataMap.forEach(function(value, key) {
		var el = document.getElementsByName(key);
		if (el.length) {
			el.forEach(function (inp) {
				if ("INPUT" == inp.tagName) {
					if ("checkbox" == inp.type) {
						if (Array.isArray(value)) {
							inp.checked = value.indexOf(inp.value) > -1;
						} else {
							inp.checked = inp.value == value;
						}
					} else if ("radio" == inp.type) {
						inp.checked = inp.value == value;
					} else if ("hidden" == inp.type) {
						inp.value = value.replaceAll(" ", "+");
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
	});
}
var query = new URLSearchParams(window.location.search);
fillInputs(query);
if (query.get("lp.f")) {
	$("#form-content").hide();
	$("#form-landing-page").show();
}
        </script>';
        }
    
    return $string; 
    }
    add_shortcode('footer_newsletter', 'fn_footer_newsletter');