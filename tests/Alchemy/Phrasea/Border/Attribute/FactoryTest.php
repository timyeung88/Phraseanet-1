<?php

namespace Alchemy\Phrasea\Border\Attribute;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class FactoryTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Attribute
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeMetadata()
    {
        $tag = new \PHPExiftool\Driver\Tag\IPTC\UniqueDocumentID();
        $value = new \PHPExiftool\Driver\Value\Mono('Unique');

        $metadata = new \PHPExiftool\Driver\Metadata\Metadata($tag, $value);

        $attribute = Factory::getFileAttribute(Attribute::NAME_METADATA, serialize($metadata));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeMetadataFail()
    {
        Factory::getFileAttribute(Attribute::NAME_METADATA, null);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeStory()
    {
        $attribute = Factory::getFileAttribute(Attribute::NAME_STORY, static::$records['record_story_1']->get_serialize_key());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Story', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeMetaField()
    {
        $databox_field = null;
        foreach (self::$collection->get_databox()->get_meta_structure() as $df) {
            $databox_field = $df;
            break;
        }

        if ( ! $databox_field) {
            $this->markTestSkipped('No databox field found');
        }

        $metafield = new MetaField($databox_field, 'value');

        $attribute = Factory::getFileAttribute(Attribute::NAME_METAFIELD, $metafield->asString());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\MetaField', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeStatus()
    {
        $attribute = Factory::getFileAttribute(Attribute::NAME_STATUS, '000100');

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Status', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeStoryFailsRecord()
    {
        Factory::getFileAttribute(Attribute::NAME_STORY, static::$records['record_1']->get_serialize_key());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeStoryFails()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        Factory::getFileAttribute(Attribute::NAME_STORY, self::$collection->get_databox()->get_sbas_id() . '_0');
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeFail()
    {
        Factory::getFileAttribute('nothing', 'nothong');
    }
}
