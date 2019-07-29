<?php

namespace OCA\DataExporter\Io;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class JsonLinesEncoder implements EncoderInterface {
	const FORMAT = 'jsonl';

	/** @var JsonEncode  */
	private $jsonEncoder;

	public function __construct() {
		//single jsonl lines are valid json
		$this->jsonEncoder = new JsonEncode();
	}

	/**
	 * Encodes data into the given format.
	 *
	 * @param mixed $data Data to encode
	 * @param string $format Format name
	 * @param array $context Options that normalizers/encoders have access to
	 *
	 * @return string|int|float|bool
	 *
	 * @throws UnexpectedValueException
	 */
	public function encode($data, $format, array $context = []) {
		$typeHint = $this->getEncodingTypeHint($context);

		if (!\in_array($typeHint, ['object', 'array'])) {
			throw new \InvalidArgumentException('Only objects and arrays supported for jsonl encoding');
		}

		if ($typeHint === 'object') {
			return $this->jsonEncoder->encode($data, 'json') . PHP_EOL;
		}

		$jsonLines = '';

		if ($typeHint === 'array' && \count($data) > 0) {
			foreach ($data as $line) {
				$jsonLines .= $this->jsonEncoder->encode($line, 'json') . PHP_EOL;
			}
		}

		return $jsonLines;
	}

	private function getEncodingTypeHint($context) {
		if (!isset($context[self::class]['type_hint'])) {
			throw new \InvalidArgumentException('Missing typehint for jsonl encoder');
		}

		return $context[self::class]['type_hint'];
	}

	/**
	 * Checks whether the serializer can encode to given format.
	 *
	 * @param string $format Format name
	 *
	 * @return bool
	 */
	public function supportsEncoding($format) {
		return $format === self::FORMAT;
	}
}
