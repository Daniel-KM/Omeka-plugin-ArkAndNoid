<?php
class Ark_RoutingTest extends Ark_Test_AppTestCase
{
    /**
     * Tests to check routing system.
     */
    public function testRoutingArkId()
    {
        foreach ($this->_recordsByType as $type => $recordsMetadata) {
            foreach ($recordsMetadata as $recordMetadata) {
                $record = $this->getRecordByTitle($recordMetadata['Title']);
                $url = record_url($record);
                $this->dispatch($url);
                $this->assertRoute('ark_id');
                $this->assertModule('default');
                $this->assertController(Inflector::tableize($type));
                $this->assertAction('show');
                $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
            }
        }
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier()
    {
        // Identifier is bad collection, but the naan is good.
        $url = '/ark%3A/12345/cxx';
        $this->setExpectedException('Omeka_Controller_Exception_404');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     *
     * Omeka manages only its ark (its naan).
     */
    public function testRoutingBadNaan()
    {
        // Identifier of the second collection, but not the good naan.
        $url = '/ark%3A/54321/c02';
        $this->setExpectedException('Zend_Controller_Dispatcher_Exception');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system.
     */
    public function testRoutingFileVariant()
    {
        $record = $this->getRecordByTitle('Title of File #2');
        $url = record_url($record, 'original');
        $this->assertEquals('/ark:/12345/b1/2.original', $url);
        $this->dispatch($url);
        $this->assertRoute('ark_file_variant');
    }

    /**
     * Tests to check routing system.
     */
    public function testRoutingBadFileVariant()
    {
        $record = $this->getRecordByTitle('Title of File #2');
        $url = record_url($record, 'bad');
        $this->assertEquals('/files/bad/' . $record->id, $url);
    }
}
