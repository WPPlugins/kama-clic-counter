=== Plugin Name ===
Stable tag: 3.6.1
Contributors: Tkama
Tags: analytics, statistics, count, count clicks, clicks, counter, download, downloads, link, kama
Tested up to: 4.7.3
Requires at least: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Count clicks on any link all over the site. Creates beautiful file download block in post content - shortcode [download url="any file URL"]. Has widget.


== Description ==

Using this plugin you will have statistics on clicks on your file download or any other link (not file download).

Plugin don't have any additional instruments for you to physically uploads files - it's no need! All files uploaded using standard wordpress media uploader. To create download block, file URL are used.

In additional, plugin has:

* Button in visual editor, to fast insert file download block shortcode.
* Customizable widget, that allows output "Top Downloads/clicks" list.




== TODO ==
* set filename in shortcode itself. Можно ли как то сделать чтобы в шорткод вставлялась и ссылка с именем файла, чтобы не на отдельной странице имя файла править

* detail statistic on each day (PRO version)

* tiny mce button click show url field and button to select file from media library

* Когда пользователь нажимает на кнопку DW, появляющаяся адресная строка вводит любого пользователя в ступор, в итоге все пользуются стандартной кнопкой, а плагин неиспользуется вообще.. Диалог редактирования ссылки из настроек прикрутить бы к кнопке DW в редакторе.. И в самом диалоге прикрутить стандартный диалог прикрепления файла (в нем же можно и с локального компьютера и из медиатеки цеплять - пользователи же уже привыкли).. Страница статистики расположенная в Настройках - нелогичное решение, несмотря на то, что там и настройки тоже есть. Ее место либо вообще в главном меню (чего я сам не люблю), либо в Инструменты или Медиафайлы. И сама аббревиатура DW на кнопке неинтуитивная, иконку бы, могу поискать..

hotlink protection
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /
RewriteCond %{HTTP_REFERER} !^$
RewriteCond %{HTTP_REFERER} !^https?://.*wptest\.ru/ [NC]
RewriteRule \.(zip|7z|exe)$ - [NC,F,L]
</IfModule>




== Frequently Asked Questions ==

= How can I customize download block with CSS? =

Just customize CSS styles in plugin options page. Also you can add css styles into 'style.css' file of your theme.



== Screenshots ==
1. Statistics page.
2. Plugin settings page.
3. Single link edit page.
4. TinyMce visual editor downloads button.




== Changelog ==


= 3.6.1 =
ADD: 'title' attribute to [download] shortcode. Ex: [download url="URL" title="my file title"]
ADD: improve tinymce button insert shortcode modal window - now you can find files in media library.
FIX: It just counted the clicks done with the left-click-mouse-button and not counted clicks with the mouse-wheel and not with "open link..." from context menu opened with right-mouse-click.


= 3.6.0 =
CHG: class name 'KCClick' changed to 'KCCounter'. If you have external code for this plugin, change in it all 'KCClick::' or 'KCC::' to 'KCCounter::'!!!
CHG: Icon in Tinymce visual editor

= 3.5.1 =
CHG: move localisation to translate.wordpress.org
FIX: minor code fix

= 3.5.0 =
FIX: XSS valneruble
CHG: Change class name 'KCC' to 'KCClick'
CHG: Translate PHP code to english. Now Russian is localization file...

= 3.4.9 =
FIX: Remove link from Admin-bar for Roles who has no plugin access

= 3.4.8 =
ADD: "click per day" data to edit link screen

= 3.4.7 - 3.4.7.3 =
FIX: table structure to work fine with 'utf8mb4_unicode_ci' charset

= 3.4.6 =
ADD: 'get_url_icon' filter to manage icons.

= 3.4.5 =
ADD: Administrator option to set access to plugin to other WP roles.
ADD: Option to add link to KCC Stat in admin bar.
DEL: no HTTP_REFERER block on direct kcc url use.

= 3.4.4 =
CHANGE: is_file extention check method for url.
ADD: 'kcc_is_file' filter
ADD: widget option to set link to post instead of link to file
REMOVED: 'kcc_file_ext' filter

= 3.4.3 =
ADD hooks: 'parce_kcc_url', 'kcc_count_before', 'kcc_count_after'.
ADD: second parametr '$args' to 'kcc_insert_link_data' filter.
ADD: punycode support. Now links filter in admin table trying to find keyword in 'link_name' db column too, not only in 'link_url'.
FIX: It just count the clicks done with the left-click mouse button. Doesn't count clicks done with the mouse wheel, which opens in new tab. Also doesn't count clicks from mobile browsers. left click, mouse wheel, ctrl + left click, touch clicks (I test it in iphone – chrome and safari)

= 3.4.2 =
ADD: 'kcc_admin_access' filter. For possibility to change access capability.
FIX: redirect protection fix.

= 3.4.1 =
FIX: parse kcc url fix.

= 3.4.0 =
ADD: Hide url in download block option. See the options page.
ADD: 'link_url' column index in DB for faster plugin work.
ADD: 'get_kcc_url', 'kcc_redefine_redirect', 'kcc_file_ext', 'kcc_insert_link_data' hooks.
ADD: Now plugin replace its ugly URL with original URL, when link hover.
ADD: Replace 'edit link' text for download block to icon. It's more convenient.
FIX: Correct updates of existing URLs. In some cases there appeared duplicates, when link contain '%' symbol (it could be cyrillic url or so on...)
FIX: XSS attack protection.
FIX: Many structure fix in code.


= 3.3.2 =
FIX: php notice

= 3.3.1 =
Add: de_DE l10n, thanks to Volker Typke.

= 3.3.0 =
Add: l10n on plugin page.
Add: menu to admin page.
FIX: antivirus wrongly says that file infected.

= 3.2.34 =
FIX: Some admin css change

= 3.2.3.3 =
ADD: jQuery links become hidden. All jQuery affected links have #kcc anchor and onclick attr with countclick url
FIX: error with parse_url part. If url had "=" it was exploded...

= 3.2.3.2 =
FIX: didn't correctly redirected to url with " " character
ADD: round "clicks per day" on admin statistics page to one decimal digit

= 3.2.3.1 =
FIX: "back to stat" link on "edit link" admin page

= 3.2.3 =
FIX: redirects to https doesn't worked correctly
FIX: PHP less than 5.3 support
FIX: go back button on "edit link" admin page
FIX: localization

= 3.2.2 =
ADD: "go back" button on "edit link" admin page

= 3.2.1 =
Set autoreplace old shortcodes to new in DB during update: [download=""] [download url=""]

= 3.2 =
Widget has been added
