<?php

// доступные шоткоды в шаблонах ссылок
function kcc_tpl_available_tags(){
	?>
	<small>
		<?php _e('Shortcodes that can be used in template:', 'kama-clic-counter') ?><br />
		<?php _e('[icon_url] - URL to file icon', 'kama-clic-counter') ?><br />
		<?php _e('[link_url] - URL to download', 'kama-clic-counter') ?><br />
		<?php _e('[link_name]', 'kama-clic-counter') ?><br />
		<?php _e('[link_title]', 'kama-clic-counter') ?><br />
		<?php _e('[link_clicks] - number of clicks', 'kama-clic-counter') ?><br />
		<?php _e('[file_size]', 'kama-clic-counter') ?><br />
		<?php _e('[link_date:d.M.Y] - date in "d.M.Y"  format', 'kama-clic-counter') ?><br />
		<?php _e('[link_description]', 'kama-clic-counter') ?><br />
		<?php _e('[edit_link] - URL to edit link in admin', 'kama-clic-counter') ?>
	</small>
	<?php
}
