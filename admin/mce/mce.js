tinymce.create('tinymce.plugins.KCC', {
	init : function( editor, url ){
		editor.addButton('kcc', {
			title : 'Click Counter Shortcode',
			onclick: function() {
				var $ = jQuery;
				/*
				var url = prompt('Download URL:', 'http://');
				if( url ){
					editor.selection.setContent('[download url="' + url + '"]');
				}
				*/
				var $bg = $('.kcc_shortcode_bg'),
					$el = $('.kcc_shortcode');

				if( $el.length ){
					$bg.add( $el ).show();
					return;
				}

				$bg = $('<div style="display:block;" id="wp-link-backdrop" class="kcc_shortcode_bg"></div>'),
				$el = $('\
<div id="wp-link-wrap" class="wp-core-ui kcc_shortcode" style="display:block; height:auto; padding:2em;">\
	<button type="button" class="button-link media-modal-close"><span class="media-modal-icon"></span></button>\
	<h3 style="margin-top:0;">'+ tinymce.translate('kcc modal title') +'</h3>\
	<p>\
		<input type="text" id="kcc_link" value="http://" placeholder="'+ tinymce.translate('kcc input link') +'" style="width:calc(100% - 50px);" />\
		<button type="button" class="button kcc_findfile" style="float:right;"><span class="dashicons dashicons-search" style="color:#999; margin-top:.1em;"></span></button>\
	</p>\
	<p><input type="text" id="kcc_title" value="" placeholder="'+ tinymce.translate('kcc input title') +'" style="width:100%;" /></p>\
	<div><input type="button" class="button-primary" value="'+ tinymce.translate('kcc button text') +'" /></div>\
\
</div>\
				');

				var $all = $bg.add( $el );

				$('body').append( $all );

				$all.show();

				// close
				$bg.add( $el.find('.media-modal-close') ).on('click', function(){
					$all.hide();
				});

				$el.find('input.button-primary').on('click', function(){
					var shortcode = '[download url=""]',
						url       = $el.find('#kcc_link').val(),
						title     = $el.find('#kcc_title').val();

					if( ! url || url === 'http://' ){ alert('no URL...'); return; }

					shortcode = shortcode.replace('url=""', 'url="'+ url +'"');

					if( title ) shortcode = shortcode.replace(']', ' title="'+ title +'"]');

					editor.selection.setContent( shortcode );

					$all.hide();
				});

				// media frame
				var frame;
				$el.find('.kcc_findfile').on('click', function( event ){
					event.preventDefault();

					var $el        = $(this),
						$urlinput  = $el.parent().find('#kcc_link'),
						foo = '';
						//post_id    = $el.data('post_id'),
						//$imgs_cont = $el.closest('.inside').find('.images__wrap');

					if( frame ){  frame.open();  return;  }

					// Create the media frame.
					frame = wp.media.frames.kccfindfile = wp.media({
						states: [
							new wp.media.controller.Library({
								title:     tinymce.translate('kcc find url frame title'),
								library:   wp.media.query({
									type: ['application','text','video','audio'] // exclude images...
									//post_parent: post_id
								}),
								multiple:  false,
								date:      false,
								//contentUserSetting: false, // вкладка загрузки файла по умолчанию...
								filterable:         'all' // фильтр по типу - селект слева
							})
						],

						// submit button.
						button: {
							text: tinymce.translate('kcc frame button title'), // Set the text of the button.
						}
					});

					var selectClose_func = function(){
						var selected = frame.state().get( 'selection' ).first();
						//console.log( selected );
						if( selected ){
							$urlinput.val( selected.attributes.url ).data('attach_id', selected.id );
						}
					};

					frame.on('select', selectClose_func, 'select' );

					frame.on('open', function(){
						var attach_id = $urlinput.data('attach_id');
						if( attach_id ) frame.state().get('selection').add( wp.media.attachment( attach_id ) );
					});

					frame.open();
				});


			},
			//classes: 'dashicons dashicons-download',
			icon: 'kcc dashicons-before dashicons-download'
			//image: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAMAAAC7IEhfAAAANlBMVEUAAABVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWZVXWasPF1RAAAAEXRSTlMA9JhZ5LR2FEjaJINqCi7Bpstlss4AAACaSURBVDjL7dPbCsMgDIDhaKzVeOjy/i87CYN0FJveDArrf6kfEi8CNwoXP1qiCV8sZRMuLIUH/g8kok4TUzjn7FjzE7gK0kKDSfQNO0xLe1fhpKrOm5twcRe2D6TDWCWO9H8tiEMVddyXBF7OVz3vPCqgOZn4CAGZN7CgFHMzoHZD6M5g2EGuCScl1BetfgWTuwQRIAV2RuwQ3tPcHk+HZ4iEAAAAAElFTkSuQmCC',
		});
	}
});
// Register plugin
tinymce.PluginManager.add( 'KCC', tinymce.plugins.KCC );
