<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
namespace OCA\DataExporter;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class Serializer {

	/** @var \Symfony\Component\Serializer\Serializer  */
	private $serializer;

	public function __construct() {
		$encoders = [new JsonEncoder()];
		$normalizers = [
			new DateTimeNormalizer(),
			new ArrayDenormalizer(),
			new ObjectNormalizer(null, null, null, new PhpDocExtractor())
		];

		$this->serializer = new \Symfony\Component\Serializer\Serializer($normalizers, $encoders);
	}

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param mixed $data Any data
	 *
	 * @return string
	 */
	public function serialize($data) {
		return $this->serializer->serialize($data, 'json', []);
	}

	/**
	 * Deserializes data into the given type.
	 *
	 * @param mixed $data
	 * @param string $type
	 *
	 * @return object
	 */
	public function deserialize($data, $type) {
		return $this->serializer->deserialize($data, $type, 'json', []);
	}
}
