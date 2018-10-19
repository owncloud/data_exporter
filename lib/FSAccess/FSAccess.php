<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgeargroup.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
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
namespace OCA\DataExporter\FSAccess;

/**
 * Jail FS operations inside the $root folder
 * This root folder can be '/tmp', '/opt', '/tmp/oC/Folder', or even '/', as long as the folder exists
 * Note that PHP wrapper protocols should work for the most part ('vfs://virtual/folder', or
 * 'ssh://remote/folder'), but this class will use the basic FS actions (mkdir, fopen, file_exists, etc)
 *
 * Also note that concurrency isn't considered, so using this class could cause
 * bugs in a concurrent environment.
 */
class FSAccess {
	/** @var string */
	private $root;

	public function __construct(string $root) {
		$this->root = \rtrim($root, '/');
	}

	/**
	 * Get the root of this FSAccess instance
	 * @return string
	 */
	public function getRoot(): string {
		return $this->root;
	}

	/**
	 * Return a path making sure that the beginning '/' is set
	 */
	private function checkPath(string $path): string {
		if ($path === '') {
			return '/';
		}
		if ($path[0] !== '/') {
			return "/$path";
		}
		return $path;
	}

	/**
	 * Create the directory and all the parents. The function assumes that the $checkedPath
	 * is a path returned by the`"checkPath" function.
	 * Note that the directories will be created inside this FSAccess instance's root folder
	 * @param string $checkedPath the path returned by the "checkPath" function
	 * @return bool true if the directory is created properly, false otherwise. Note that this
	 * function won't return where the failure is located in case one of the parent folder
	 * can't be created
	 */
	private function recursiveDirCreation(string $checkedPath) {
		$splittedPath = \explode('/', \ltrim($checkedPath, '/'));
		$realPath = $this->root;
		foreach ($splittedPath as $item) {
			$realPath .= "/$item";
			if (!\file_exists($realPath)) {
				$result = \mkdir($realPath);
				if ($result === false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Make a directory. The directory will be created recirsively if needed
	 * inside this FSAccess instance's root folder (if possible)
	 * Note that in case of recursive creation, the operation might fail and some
	 * directories might have been created
	 * @param string $path the path representing the folder to be created
	 * @return bool true of the directory has been created (as well as the
	 * required upper folders), false otherwise
	 */
	public function mkdir(string $path): bool {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		if (\mkdir($realPath) === false) {
			if ($this->recursiveDirCreation(\dirname($checkedPath))) {
				return \mkdir($realPath);
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the file exists inside this FSAccess instance
	 * @param string $path the path to be checked
	 * @return bool true if the file exists, false otherwise
	 */
	public function fileExists(string $path): bool {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		return \file_exists($realPath);
	}

	/**
	 * Get a **read** stream to read from the file in that path
	 * @param string $path the path inside this FSAccess instance to read from
	 * @return resource|false a "fopen" resource or false (according to the "fopen" function)
	 */
	public function getStream(string $path) {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		return @\fopen($realPath, 'rb');
	}

	/**
	 * Write the string in a new file in $path. Note that previous content will be overwritten.
	 * This function won't append the string
	 * @param string $content the content to be written
	 * @param string $path the path inside the FSAccess instance where the content will be written
	 * @return int|false the number of bytes written or false in case of error
	 */
	public function copyContentToPath(string $content, string $path) {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		if (!\file_exists(\dirname($realPath))) {
			if (!$this->recursiveDirCreation(\dirname($checkedPath))) {
				return false;
			}
		}

		return @\file_put_contents($realPath, $content);
	}

	/**
	 * Get the contents of the file in $path. The whole content will be fetched as string
	 * @param string $path the path to get the contents from
	 * @return string|false the contents of the file or false in case of error
	 */
	public function getContentFromPath(string $path) {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		if (!\file_exists($realPath)) {
			return false;
		}
		return @\file_get_contents($realPath);
	}

	/**
	 * Copy the contents of the stream into a new file inside this FSAccess instance
	 * The stream needs to be opened, and the cursor should be placed properly (usually
	 * at the beginning of the stream) before using this function. Note that this function
	 * WON'T close the stream, so you'll need to close it on your own.
	 * The file corresponding to $path will be handled completely by this function.
	 * @param resource $stream the stream (typically fetched with "fopen") that will be copied
	 * @param string $path the path inside this FSAccess instance where the contents will be written
	 * @return int|false the number of bytes copied from the stream, or false if something went wrong
	 */
	public function copyStreamToPath($stream, string $path) {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;
		if (!\file_exists(\dirname($realPath))) {
			if (!$this->recursiveDirCreation(\dirname($checkedPath))) {
				return false;
			}
		}

		$dst = @\fopen($realPath, 'wb');
		if ($dst === false) {
			return false;
		}

		$result = \stream_copy_to_stream($stream, $dst);
		\fclose($dst);
		return $result;
	}

	/**
	 * Copy the contents of the file in the $path into the stream.
	 * The file must exists. Opening, reading and closing the file will be handled by this function
	 * The stream needs to be opened for writing and the cursor positioned properly, usually at the
	 * beginning of the file (you can write some content before if needed, or position the cursor at the
	 * end of the file)
	 * The stream WON'T be closed, so you'll need to close the stram on your own.
	 * @param string $path the path inside this FSAccess instance to read the contents from
	 * @param resource $stream the opened stream (typically fetched with "fopen") where the contents will
	 * be written
	 * @return int|false the number of bytes copied to the stream, or false if something went wrong
	 */
	public function copyPathToStream(string $path, $stream) {
		$checkedPath = $this->checkPath($path);
		$realPath = $this->root . $checkedPath;

		$src = @\fopen($realPath, 'rb');
		if ($src === false) {
			return false;
		}

		$result = \stream_copy_to_stream($src, $stream);
		\fclose($src);
		return $result;
	}
}
