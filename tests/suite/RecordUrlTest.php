<?php
class Ark_RecordUrlTest extends Ark_Test_AppTestCase
{
    /**
     * Tests to get the good record url of a record via core functions.
     */
    public function testRecordUrl()
    {
        foreach ($this->_recordsByType as $type => $recordsMetadata) {
            foreach ($recordsMetadata as $recordMetadata) {
                if (isset($recordMetadata['url'])) {
                    $record = $this->getRecordByTitle($recordMetadata['Title']);
                    $urlRecord = record_url($record, null, true);
                    $this->assertEquals($recordMetadata['url'], $urlRecord);
                }
            }
        }
    }
}
