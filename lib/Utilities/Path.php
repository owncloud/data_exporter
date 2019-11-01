<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General,
 * Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
namespace OCA\DataExporter\Utilities;

class Path {
	const REGEX = '#/+#';

	/**
	 * Joins paths, removes duplicate and adds missing slashes. Preservers
	 * double slashes in the scheme part of the path e.g vfs://foo/bar
	 *
	 * @return string
	 */
	public static function join() {
		$paths = [];

		foreach (\func_get_args() as $arg) {
			if ($arg !== '') {
				$paths[] = $arg;
			}
		}

		if (\count($paths) === 0) {
			return '';
		}

		$firstPart = $paths[0];
		$path = \preg_replace(self::REGEX, '/', \join('/', $paths));
		$scheme = \parse_url($firstPart, PHP_URL_SCHEME);
		$hasScheme =  \substr($firstPart, 0, \strlen("$scheme://")) === "$scheme://";
		$slashWasRemoved = \substr($path, 0, \strlen("$scheme:/")) == "$scheme:/";

		if ($hasScheme && $slashWasRemoved) {
			$path = "$scheme://" . \substr($path, \strlen("$scheme:/"));
		}

		return $path;
	}
}
