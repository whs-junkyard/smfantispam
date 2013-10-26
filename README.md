# SMF 2 Antispam

This is the antispam system behind [ubuntuclub forum](http://forum.ubuntuclub.com). It use keyword blocking and can work alongside existing antispam solutions such as Akismet, CAPTCHA, post throttle.

Developed on SMF 2.0.6

## Installation

1. Put `/antispam/` directory in your SMF installation such that it could be accessible from `http://yoursite/smf/antispam/`
2. Edit `Sources/Post.php`. Add the following codes *above* `// Check the subject and message.`

~~~~~~php
	// whs mod -> antispam
	// not an edit
	if(!isset($_REQUEST['msg'])){
		$q = $smcFunc['db_query']('', '
			SELECT text
			FROM {db_prefix}antispam
		');
		while($bannedText = $smcFunc['db_fetch_assoc']($q)){
			if(mb_stripos($_REQUEST['message'], $bannedText['text']) !== false){
				$post_errors[] = 'whs_antispam';
				break;
			}
			if(mb_stripos($_REQUEST['subject'], $bannedText['text']) !== false){
				$post_errors[] = 'whs_antispam';
				break;
			}
		}
		$smcFunc['db_free_result']($q);
	}
~~~~~~

3. Edit `Themes/default/languages/Errors.english.php`. Add following lines to the end of the file.

~~~~~~php
$txt['error_whs_antispam'] = 'Post is probably spam. Please rephrase your post.';
~~~~~~

4. Load antispam.sql into your database.
5. Enter `http://yoursite/smf/antispam/` and add some keywords. Test that it work by posting a topic or reply with blocked keyword in the topic name or message body.

## Note

- Does not block keywords in edit. This is intended.
- Use case insensitive, partial word matching.
- Affect administrator & mods to make sure normal users are not getting blocked while only mods can post.

## License

This modification is licensed 3-clause BSD license, same as the [SMF](http://www.simplemachines.org/about/smf/license.php) itself.