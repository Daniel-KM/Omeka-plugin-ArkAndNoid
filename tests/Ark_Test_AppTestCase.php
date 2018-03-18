<?php
/**
 * @copyright Daniel Berthereau, 2015
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package Ark
 */

/**
 * Base class for Ark tests.
 */
class Ark_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'Ark';

    protected $_isAdminTest = false;

    protected $_view;

    // All records are saved during start, so all identifiers and routes can be
    // checked together.
    protected $_recordsByType = array(
        'Collection' => array(
            array(
                'url' => 'http://www.example.com/ark%3A/12345/c01',
                'collection_id' => 1,
                'Title' => 'Title of Collection #1',
                'Identifier' => array(
                    // Another identifier.
                    'identifier 1',
                    // The ark.
                    'ark:/12345/c01',
            )),
            array(
                'collection_id' => 2,
                'Title' => 'Title of Collection #2',
                'Identifier' => array(
                    // An external ark, so the internal ark will be created
                    // automatically.
                    'ark:/54321/c02',
            )),
        ),

        'Item' => array(
            array(
                'url' => 'http://www.example.com/ark%3A/12345/b1',
                'collection_id' => 1,
                'item_id' => 1,
                'files' => 2,
                'Title' => 'Title of Item #1',
                'Identifier' => array(
                    'ark:/12345/b1',
            )),
        ),

        'File' => array(
            array(
                'url' => 'http://www.example.com/ark%3A/12345/b1/1',
                'item_id' => 1,
                'file_key' => 0,
                'Title' => 'Title of File #1',
                'Identifier' => array(
            )),
            array(
                'url' => 'http://www.example.com/ark%3A/12345/b1/2',
                'item_id' => 1,
                'file_key' => 1,
                'Title' => 'Title of File #2',
                'Identifier' => array(
            )),
        ),
    );

    public function setUp()
    {
        parent::setUp();

        $this->_view = get_view();
        $this->_view->addHelperPath(PLUGIN_DIR . '/Ark/views/helpers', self::PLUGIN_NAME . '_View_Helper_');

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp(self::PLUGIN_NAME);

        set_option('ark_format_name', 'omeka_id');

        set_option('ark_naan', '12345');
        set_option('ark_id_prefix_collection', '');
        set_option('ark_id_length', 0);
        set_option('ark_id_salt', '');
        set_option('ark_id_control_key', false);

        $this->_prepareRecords();
    }

    public function assertPreConditions()
    {
        $this->assertEquals('12345', get_option('ark_naan'), sprintf('The option "ark_naan" is not set.'));

        $records = $this->db->getTable('Collection')->findAll();
        $count = count($this->_recordsByType['Collection']);
        $this->assertEquals($count, count($records), sprintf('There should be %d collections.', $count));

        $collection = $records[1];
        $this->assertEquals('ark:/54321/c02', metadata($collection, array('Dublin Core', 'Identifier')), sprintf('The ark for the second collection is not set.'));

        $records = $this->db->getTable('Item')->findAll();
        $count = count($this->_recordsByType['Item']);
        $this->assertEquals($count, count($records), sprintf('There should be %d items.', $count));

        $records = $this->db->getTable('File')->findAll();
        $count = count($this->_recordsByType['File']);
        $this->assertEquals($count, count($records), sprintf('There should be %d files.', $count));
    }

    /**
     * Get a record by title.
     *
     * @internal This function allows a quick check of records, because id can
     * change between tests.
     */
    protected function getRecordByTitle($title)
    {
        $record = null;
        $elementSetName = 'Dublin Core';
        $elementName = 'Title';
        $element = $this->db->getTable('Element')->findByElementSetNameAndElementName($elementSetName, $elementName);
        $elementTexts = $this->db->getTable('ElementText')->findBy(array('element_id' => $element->id, 'text' => $title), 1);
        $elementText = reset($elementTexts);
        if ($elementText) {
            $record = get_record_by_id($elementText->record_type, $elementText->record_id);
        }
        return $record;
    }

    /**
     * Set some records with identifier to test.
     */
    protected function _prepareRecords()
    {
        // Remove default records.
        $this->_deleteAllRecords();

        $metadata = array('public' => true);
        $isHtml = false;

        $collections = array();
        $items = array();
        $files = array();
        foreach ($this->_recordsByType as $type => $recordsMetadata) {
            foreach ($recordsMetadata as $recordMetadata) {
                $identifiers = array();
                foreach ($recordMetadata['Identifier'] as $identifier) {
                    $identifiers[] = array('text' => $identifier, 'html' => $isHtml);
                }
                $elementTexts = array('Dublin Core' => array(
                    'Title' => array(array('text' => $recordMetadata['Title'], 'html' => $isHtml)),
                    'Identifier' => $identifiers,
                ));
                switch ($type) {
                    case 'Collection':
                        $collections[$recordMetadata['collection_id']] = insert_collection($metadata, $elementTexts);
                        break;
                    case 'Item':
                        $metadataItem = $metadata;
                        if (!empty($recordMetadata['collection_id'])) {
                            $metadataItem['collection_id'] = $collections[$recordMetadata['collection_id']]->id;
                        }
                        $record = insert_item($metadataItem, $elementTexts);
                        if (!empty($recordMetadata['files'])) {
                            $fileUrl = TEST_DIR . '/_files/test.jpg';
                            $files[$recordMetadata['item_id']] = insert_files_for_item($record, 'Filesystem', array_fill(0, $recordMetadata['files'], $fileUrl));
                        }
                        break;
                    case 'File':
                        $record = $files[$recordMetadata['item_id']][$recordMetadata['file_key']];
                        $record->addElementTextsByArray($elementTexts);
                        $record->save();
                        break;
                }
            }
        }
    }

    protected function _deleteAllRecords()
    {
        foreach (array('Collection', 'Item', 'File') as $recordType) {
            $records = get_records($recordType, array(), 0);
            foreach ($records as $record) {
                $record->delete();
            }
        }
    }
}
