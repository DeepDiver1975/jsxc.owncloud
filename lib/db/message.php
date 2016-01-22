<?php

namespace OCA\OJSXC\Db;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

/**
 * Class Message
 *
 * @package OCA\OJSXC\Db
 * @method void setType(string $type)
 * @method void setValue(array $value)
 * @method string getType()
 * @method array getValue()
 */
class Message extends Stanza implements XmlSerializable{

	public $type;
	public $value;

	public function xmlSerialize(Writer $writer) {
		$writer->write([
			[
				'name' => 'message',
				'attributes' => [
					'to' => $this->to,
					'from' => $this->from,
					'type' => $this->type,
					'xmlns' => 'jabber:client',
					'id' => uniqid() . '-msg'
				],
				'value' => $this->value
			]
		]);
	}

}