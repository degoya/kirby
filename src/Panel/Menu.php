<?php

namespace Kirby\Panel;

use Closure;
use Kirby\Cms\App;
use Kirby\Toolkit\A;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/**
 * The Menu class takes care of gathering
 * all menu entries for the Panel
 * @since 4.0.0
 *
 * @package   Kirby Panel
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class Menu
{
	public function __construct(
		protected array $areas = [],
		protected array $permissions = [],
		protected string|null $current = null
	) {
	}

	public function areas(): array
	{
		$kirby = App::instance();
		$areas = $kirby->option('panel.menu');

		if ($areas instanceof Closure) {
			$areas = $areas($kirby);
		}

		return match ($areas) {
			null    => $this->defaultAreas(),
			default => $this->customEntries($areas)
		};
	}

	protected function customEntries(array $config): array
	{
		$entries = [];

		foreach ($config as $id => $entry) {
			// keep separator as
			if ($entry === '-') {
				$entries[] = '-';
				continue;
			}

			// for a simple id, get global area definition
			if (is_numeric($id) === true) {
				$id    = $entry;
				$entry = $this->areas[$id] ?? null;
			} else {
				// add default current callback for
				// custom entries that define a link
				if ($link = $entry['link'] ?? null) {
					$entry['current'] ??= function (string $current) use ($link): bool {
						$path = App::instance()->request()->path()->toString();
						return Str::contains($path, $link);
					};
				}
			}

			// skip non-existing areas
			if (is_array($entry) === false) {
				continue;
			}

			// merge definition  with global area definition
			$entry = array_merge(
				$this->areas[$id] ?? [],
				['menu' => true],
				$entry
			);

			$entries[] = Panel::area($id, $entry);
		}

		return $entries;
	}

	protected function defaultAreas(): array
	{
		// ensure that some defaults are on top in the right order
		$defaults    = ['site', 'languages', 'users', 'system'];
		// add all other areas after that
		$additionals = array_diff(array_keys($this->areas), $defaults);

		return A::map(
			[...$defaults, ...$additionals],
			fn ($area) => $this->areas[$area]
		);
	}

	/**
	 * Transforms an area definition into a menu entry
	 * @internal
	 */
	public function entry(array $area): array|false
	{
		// areas without access permissions get skipped entirely
		if ($this->hasPermission($area['id']) === false) {
			return false;
		}

		// check menu setting from the area definition
		$menu = $area['menu'] ?? false;

		// menu setting can be a callback
		// that returns true, false or 'disabled'
		if ($menu instanceof Closure) {
			$menu = $menu($this->areas, $this->permissions, $this->current);
		}

		// false will remove the area/entry entirely
		//just like with disabled permissions
		if ($menu === false) {
			return false;
		}

		$menu = match ($menu) {
			'disabled' => ['disabled' => true],
			true       => [],
			default    => $menu
		};

		$entry = array_merge([
			'current'  => $this->isCurrent(
				$area['id'],
				$area['current'] ?? null
			),
			'icon'     => $area['icon'] ?? null,
			'link'     => $area['link'] ?? null,
			'dialog'   => $area['dialog'] ?? null,
			'drawer'   => $area['drawer'] ?? null,
			'text'     => $area['label'],
		], $menu);

		// unset the link (which is always added by default to an area)
		// if a dialog or drawer should be opened instead
		if (isset($entry['dialog']) || isset($entry['drawer'])) {
			unset($entry['link']);
		}

		return array_filter($entry);
	}

	/**
	 * Returns all menu entries
	 */
	public function entries(): array
	{
		$entries = [];
		$areas   = $this->areas();

		foreach ($areas as $area) {
			if ($area === '-') {
				$entries[] = '-';
			} elseif ($entry = $this->entry($area)) {
				$entries[] = $entry;
			}
		}

		$entries[] = '-';

		return array_merge($entries, $this->options());
	}

	/**
	 * Checks if the access permission to a specific area is granted.
	 * Defaults to allow access.
	 * @internal
	 */
	public function hasPermission(string $id): bool
	{
		return $this->permissions['access'][$id] ?? true;
	}

	/**
	 * Whether the menu entry should receive aria-current
	 * @internal
	 */
	public function isCurrent(
		string $id,
		bool|Closure|null $callback = null
	): bool {
		if ($callback !== null) {
			if ($callback instanceof Closure) {
				$callback = $callback($this->current);
			}

			return $callback;
		}

		return $this->current === $id;
	}

	/**
	 * Default options entries for bottom of menu
	 * @internal
	 */
	public function options(): array
	{
		$options = [
			[
				'icon'     => 'edit-line',
				'dialog'   => 'changes',
				'text'     => I18n::translate('changes'),
			],
			[
				'current'  => $this->isCurrent('account'),
				'icon'     => 'account',
				'link'     => 'account',
				'disabled' => $this->hasPermission('account') === false,
				'text'     => I18n::translate('view.account'),
			],
			[
				'icon' => 'logout',
				'link' => 'logout',
				'text' => I18n::translate('logout')
			]
		];

		return $options;
	}
}
