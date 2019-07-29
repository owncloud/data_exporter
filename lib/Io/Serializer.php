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
namespace OCA\DataExporter\Io;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Lazy jsonl (de)serialization from streams.
 */
class Serializer {

	/** @var \Symfony\Component\Serializer\Serializer  */
	private $serializer;

	public function __construct() {
		$encoders = [new JsonEncoder(), new JsonLinesEncoder()];
		$normalizers = [
			new DateTimeNormalizer(),
			new ArrayDenormalizer(),
			new ObjectNormalizer(null, null, null, new PhpDocExtractor())
		];

		$this->serializer = new \Symfony\Component\Serializer\Serializer($normalizers, $encoders);
	}

	/**
	 * @param $jsonlStream
	 * @param $type
	 * @return \Generator
	 */
	public function deserializeStream($jsonlStream, $type) {
		foreach ($this->readLines($jsonlStream) as $jsonLine) {
			$jsonLine =  $this->serializer->decode($jsonLine, 'json');
			yield $this->serializer->denormalize($jsonLine, $type);
		}
	}

	/**
	 * @param $data
	 * @param $toStream
	 */
	public function serializeToStream($data, $toStream) {
		$ctx = [JsonLinesEncoder::class => ['type_hint' => \gettype($data)]];
		$norm = $this->serializer->normalize($data, 'jsonl');
		$jsonLine = $this->serializer->encode($norm, 'jsonl', $ctx);

		\fwrite($toStream, $jsonLine);
	}

	/**
	 * Lazily-reads a stream of lines in to a buffer, then blocks until
	 * the buffer is yielded completely.
	 *
	 * @param resource $stream
	 * @param int $lineBufSize Number of lines to buffer
	 * @return \Generator
	 */
	private function readLines($stream, $lineBufSize = 256) {
		$buf = [];
		while (($line = \fgets($stream)) !== false) {
			$buf[] = $line;
			//Buffer n lines then decode batch
			if (\sizeof($buf) >= $lineBufSize) {
				foreach ($buf as $k => $l) {
					yield $l;
					unset($buf[$k]);
				}
			}
		}

		// Empty the remaining buffer
		foreach ($buf as $k => $l) {
			yield $l;
			unset($buf[$k]);
		}
	}
}
