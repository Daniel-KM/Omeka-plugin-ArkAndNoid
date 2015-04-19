<?php
class Ark_IdentifiersTest extends Ark_Test_AppTestCase
{
    /**
     * Tests to check the default ark of a record.
     */
    public function testGetRecordArk()
    {
        $arkAndTitles = array(
            'ark:/12345/c01' => 'Title of Collection #1',
            // This ark is automatically created.
            'ark:/12345/02' => 'Title of Collection #2',
            'ark:/12345/b1' => 'Title of Item #1',
        );
        foreach ($arkAndTitles as $ark => $title) {
            $recordFrom = $this->_view->getRecordFromArk($ark);
            $this->assertTrue(!empty($recordFrom), sprintf('Cannot get a record from ark "%s".', $ark));
            $record = $this->getRecordByTitle($title);
            $arkRecordFrom = $this->_view->ark($record);
            $this->assertEquals($ark, $arkRecordFrom , sprintf('Ark "%s" is not the one of the record ("%s").', $ark, $arkRecordFrom));
        }
    }

    /**
     * Tests that doesn't get a record  from a bad ark.
     */
    public function testGetNoRecordFromBadArk()
    {
        $arks = array(
            "who's bad",
            'ark:/54321/c02',
        );

        foreach ($arks as $ark) {
            $record = $this->_view->getRecordFromArk($ark);
            $this->assertTrue(is_null($record), sprintf('Should not get a record from bad ark "%s".', $ark));
        }
    }
}

