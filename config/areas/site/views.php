<?php

use Kirby\Cms\App;
use Kirby\Cms\Find;

return [
	'page' => [
		'pattern' => 'pages/(:any)',
		'action'  => fn (string $path) => Find::page($path)->panel()->view()
	],
	'page.preview' => [
		'pattern' => 'pages/(:any)/preview',
		'action'  => function (string $path) {
			return [
				...Find::page($path)->panel()->view(),
				'component' => 'k-preview-view'
			];
		}
	],
	'page.file' => [
		'pattern' => 'pages/(:any)/files/(:any)',
		'action'  => function (string $id, string $filename) {
			return Find::file('pages/' . $id, $filename)->panel()->view();
		}
	],
	'site' => [
		'pattern' => 'site',
		'action'  => fn () => App::instance()->site()->panel()->view()
	],
	'site.preview' => [
		'pattern' => 'site/preview',
		'action'  => function () {
			return [
				...App::instance()->site()->panel()->view(),
				'component' => 'k-preview-view'
			];
		}
	],
	'site.file' => [
		'pattern' => 'site/files/(:any)',
		'action'  => function (string $filename) {
			return Find::file('site', $filename)->panel()->view();
		}
	],
];
