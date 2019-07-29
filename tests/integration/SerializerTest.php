<?php

namespace OCA\DataExporter\Tests\Acceptance\SerializerTest;

use OCA\DataExporter\Io\Serializer;
use OCA\DataExporter\Model\File;
use Test\TestCase;

class SerializerTest extends TestCase {
	const TEST_JSONL = <<< JSONL
{"type":"file","path":"\/foo\/bar.txt","eTag":"12413rr","permissions":19}
{"type":"folder","path":"\/pics","eTag":"43t3t3g3g","permissions":20}

JSONL;

	/** @var Serializer */
	private $ser;
	private $testId;

	private $testObjects;

	public function setUp() {
		parent::setUp();
		$this->testId = \bin2hex(\random_bytes(4));
		$this->ser = new Serializer();

		$this->testObjects = [
			(new File())
				->setPermissions(19)
				->setETag('12413rr')
				->setType(File::TYPE_FILE)
				->setPath('/foo/bar.txt'),
			(new File())
				->setPermissions(20)
				->setETag('43t3t3g3g')
				->setType(File::TYPE_FOLDER)
				->setPath('/pics'),
		];
	}

	public function testSerialize() {
		$stream = \fopen('php://memory', 'rb+');

		// Serialize single objects
		foreach ($this->testObjects as $f) {
			$this->ser->serializeToStream($f, $stream);
		}

		\rewind($stream);

		$actual = \stream_get_contents($stream);
		$this->assertEquals(self::TEST_JSONL, $actual);

		\fclose($stream);
	}

	public function testDeserialization() {
		$stream = \fopen('php://memory', 'rb+');
		\fwrite($stream, self::TEST_JSONL);
		\rewind($stream);

		/** @var File[] $expected */
		$expected = $this->testObjects;
		/** @var File[] $actual */
		$actual = $this->ser->deserializeStream($stream, File::class);

		foreach ($actual as $key => $obj) {
			$this->assertEquals($expected[$key]->getETag(), $obj->getETag());
			$this->assertEquals($expected[$key]->getPath(), $obj->getPath());
			$this->assertEquals($expected[$key]->getType(), $obj->getType());
			$this->assertEquals($expected[$key]->getPermissions(), $obj->getPermissions());
		}

		\fclose($stream);
	}
}
