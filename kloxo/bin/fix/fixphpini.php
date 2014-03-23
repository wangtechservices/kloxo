<?php 

include_once "lib/html/include.php"; 
initProgram('admin');

$list = parse_opt($argv);

$server = (isset($list['server'])) ? $list['server'] : 'localhost';
$client = (isset($list['client'])) ? $list['client'] : null;
$domain = (isset($list['domain'])) ? $list['domain'] : null;
$nolog  = (isset($list['nolog'])) ? $list['nolog'] : null;

$login->loadAllObjects('client');
$list = $login->getList('client');

$plist = $login->getList('pserver');

log_cleanup("Fixing php.ini/php-fpm.conf/php5.fcgi/.htaccess", $nolog);

foreach($plist as $s) {
	if ($client !== null) { continue; }
	if ($domain !== null) { continue; }

	if ($server !== 'all') {
		$sa = explode(",", $server);
		if (!in_array($s->syncserver, $sa)) { continue; }
	}

	$php = $s->getObject('phpini');

	$php->setUpdateSubaction('ini_update');

	log_cleanup("- '/etc/php.ini' at '{$php->syncserver}'", $nolog);
	log_cleanup("- '/etc/php-fpm.d/default.conf' at '{$php->syncserver}'", $nolog);
	log_cleanup("- '/home/kloxo/client/php5.fcgi' at '{$php->syncserver}'", $nolog);

	$php->was();
}

$clist = array();

foreach($list as $c) {
	if ($client) {
		$ca = explode(",", $client);

		if (!in_array($c->nname, $ca)) { continue; }
	}

	if ($server !== 'all') {
		$sa = explode(",", $server);

		if (!in_array($s->syncserver, $sa)) { continue; }
	}

	$dlist = $c->getList('domaina');

	foreach((array) $dlist as $l) {
		$web = $l->getObject('web');

		if ($domain) {
			$da = explode(",", $domain);
			if (!in_array($web->nname, $da)) { continue; }
		}

		if ($domain !== null) {
			$php = $web->getObject('phpini');
			$php->initPhpIni();
			$php->setUpdateSubaction('htaccess_update');

			log_cleanup("- '/home/{$c->nname}/{$web->docroot}/.htaccess' ('{$c->nname}') at '{$php->syncserver}'", $nolog);

			$php->was();
		} else {
			if (!in_array($c->nname, $clist)) {
				$php = $c->getObject('phpini');
				$php->initPhpIni();
				$php->setUpdateSubaction('ini_update');

				log_cleanup("- '/home/kloxo/client/{$c->nname}/php.ini' at '{$php->syncserver}'", $nolog);
				log_cleanup("- '/home/kloxo/client/{$c->nname}/php5.fcgi' at '{$php->syncserver}'", $nolog);
				log_cleanup("- '/etc/php-fpm.d/{$c->nname}.conf' at '{$php->syncserver}'", $nolog);
				log_cleanup("- '/home/{$c->nname}/kloxoscript/.htaccess' at '{$php->syncserver}'", $nolog);

				$php->was();

				$clist[] = $c->nname;
				array_unique($clist);
			}

			$php = $web->getObject('phpini');
			$php->initPhpIni();
			$php->setUpdateSubaction('htaccess_update');

			log_cleanup("- '/home/{$c->nname}/{$web->docroot}/.htaccess' ('{$c->nname}') at '{$php->syncserver}'", $nolog);

			$php->was();
		}

		$web->was();
	}
}


