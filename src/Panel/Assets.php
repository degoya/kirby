<?php

namespace Kirby\Panel;

use Kirby\Cms\App;
use Kirby\Exception\Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

/**
 * The Assets class collects all js, css, icons and other
 * files for the Panel. It pushes them into the media folder
 * on demand and also makes sure to create proper asset URLs
 * depending on dev mode
 *
 * @package   Kirby Panel
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class Assets
{
	protected bool $dev;
	protected App $kirby;
	protected string $nonce;
	protected Plugins $plugins;
	protected string $url;
	protected bool $vite;

	public function __construct()
	{
		$this->kirby   = App::instance();
		$this->nonce   = $this->kirby->nonce();
		$this->plugins = new Plugins();
		$this->vite    = is_file($this->kirby->roots()->panel() . '/.vite-running') === true;

		// get the assets from the Vite dev server in dev mode;
		// dev mode = explicitly enabled in the config AND Vite is running
		$this->dev = $this->kirby->option('panel.dev', false) !== false && $this->vite === true;

		// get the base URL
		$this->url = $this->url();
	}

	/**
	 * Get all CSS files
	 */
	public function css(): array
	{
		$css = [
			'index'   => $this->url . '/css/style.css',
			'plugins' => $this->plugins->url('css'),
			'custom'  => $this->custom('panel.css'),
		];

		if ($this->dev === true) {
			$css['index'] = null;
		}

		return array_filter($css);
	}

	/**
	 * Check for a custom asset file from the
	 * config (e.g. panel.css or panel.js)
	 */
	public function custom(string $option): string|null
	{
		if ($path = $this->kirby->option($option)) {
			$asset = new Asset($path);

			if ($asset->exists() === true) {
				return $asset->url() . '?' . $asset->modified();
			}
		}

		return null;
	}

	/**
	 * Generates an array with all assets
	 * that need to be loaded for the panel (js, css, icons)
	 */
	public function external(): array
	{
		return [
			'css'   => $this->css(),
			'icons' => $this->favicons(),
			// loader for plugins' index.dev.mjs files – inlined, so we provide the code instead of the asset URL
			'plugin-imports' => $this->plugins->read('mjs'),
			'js' => $this->js()
		];
	}

	/**
	 * Returns array of favicon icons
	 * based on config option
	 *
	 * @throws \Kirby\Exception\InvalidArgumentException
	 */
	public function favicons(): array
	{
		$icons = $this->kirby->option('panel.favicon', [
			'apple-touch-icon' => [
				'type' => 'image/png',
				'url'  => $this->url . '/apple-touch-icon.png',
			],
			'alternate icon' => [
				'type' => 'image/png',
				'url'  => $this->url . '/favicon.png',
			],
			'shortcut icon' => [
				'type' => 'image/svg+xml',
				'url'  => $this->url . '/favicon.svg',
			]
		]);

		if (is_array($icons) === true) {
			return $icons;
		}

		// make sure to convert favicon string to array
		if (is_string($icons) === true) {
			return [
				'shortcut icon' => [
					'type' => F::mime($icons),
					'url'  => $icons,
				]
			];
		}

		throw new InvalidArgumentException('Invalid panel.favicon option');
	}

	/**
	 * Load the SVG icon sprite
	 * This will be injected in the
	 * initial HTML document for the Panel
	 */
	public function icons(): string
	{
		$dir = $this->dev ? 'public' : 'dist';
		return F::read($this->kirby->root('panel') . '/' . $dir . '/img/icons.svg');
	}

	/**
	 * Get all js files
	 */
	public function js(): array
	{
		$js = [
			'vue' => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/vue.js'
			],
			'vendor'       => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/vendor.js',
				'type'  => 'module'
			],
			'pluginloader' => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/plugins.js',
				'type'  => 'module'
			],
			'plugins'      => [
				'nonce' => $this->nonce,
				'src'   => $this->plugins->url('js'),
				'defer' => true
			],
			'custom'       => [
				'nonce' => $this->nonce,
				'src'   => $this->custom('panel.js'),
				'type'  => 'module'
			],
			'index'        => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/index.js',
				'type'  => 'module'
			],
		];

		// during dev mode, add vite client and adapt
		// path to `index.js` - vendor and stylesheet
		// don't need to be loaded in dev mode
		if ($this->dev === true) {
			$js['vite'] = [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/@vite/client',
				'type'  => 'module'
			];

			$js['index'] = [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/src/index.js',
				'type'  => 'module'
			];

			// load the development version of Vue
			$js['vue']['src'] = $this->url . '/node_modules/vue/dist/vue.js';

			// remove the vendor script
			$js['vendor']['src'] = null;
		}

		return array_filter($js, function ($js) {
			return empty($js['src']) === false;
		});
	}

	/**
	 * Links all dist files in the media folder
	 * and returns the link to the requested asset
	 *
	 * @throws \Kirby\Exception\Exception If Panel assets could not be moved to the public directory
	 */
	public function link(): bool
	{
		$mediaRoot   = $this->kirby->root('media') . '/panel';
		$panelRoot   = $this->kirby->root('panel') . '/dist';
		$versionHash = $this->kirby->versionHash();
		$versionRoot = $mediaRoot . '/' . $versionHash;

		// check if the version already exists
		if (is_dir($versionRoot) === true) {
			return false;
		}

		// delete the panel folder and all previous versions
		Dir::remove($mediaRoot);

		// recreate the panel folder
		Dir::make($mediaRoot, true);

		// copy assets to the dist folder
		if (Dir::copy($panelRoot, $versionRoot) !== true) {
			throw new Exception('Panel assets could not be linked');
		}

		return true;
	}

	/**
	 * Get the base URL for all assets depending on dev mode
	 */
	public function url(): string
	{
		// vite is not running, use production assets
		if ($this->dev === false) {
			return $this->kirby->url('media') . '/panel/' . $this->kirby->versionHash();
		}

		// explicitly configured base URL
		if (is_string($dev = $this->kirby->option('panel.dev', false)) === true) {
			return $dev;
		}

		// port 3000 of the current Kirby request
		return rtrim($this->kirby->request()->url([
			'port'   => 3000,
			'path'   => null,
			'params' => null,
			'query'  => null
		])->toString(), '/');
	}
}
