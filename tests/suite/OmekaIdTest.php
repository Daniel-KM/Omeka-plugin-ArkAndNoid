<?php
/**
 * Tests to check internal Omeka id (static name derived from record id).
 */
class Ark_OmekaIdTest extends Ark_Test_AppTestCase
{
    public function testWithLength()
    {
        $recordId = 151;

        $tests = array(
            array(
                'prefixes' => array(
                    'Collection' => 'fkc',
                    'Item' => 'fk',
                ),
                'results' => array(
                    0 => array(
                        'Collection' => 'ark:/12345/fkc1512',
                        'Item' => 'ark:/12345/fk1515',
                    ),
                    4 => array(
                        'Collection' => 'ark:/12345/fkc01519',
                        'Item' => 'ark:/12345/fk01512',
                    ),
                ),
            ),

            array(
                'prefixes' => array(
                    'Collection' => 'a',
                    'Item' => 'a',
                ),
                'results' => array(
                    0 => array(
                        'Collection' => 'ark:/12345/a01515',
                        'Item' => 'ark:/12345/a1518',
                    ),
                    4 => array(
                        'Collection' => 'ark:/12345/a001512',
                        'Item' => 'ark:/12345/a01515',
                    ),
                ),
            ),

            array(
                'prefixes' => array(
                    'Collection' => '',
                    'Item' => '',
                ),
                'results' => array(
                    0 => array(
                        'Collection' => 'ark:/12345/01518',
                        'Item' => 'ark:/12345/1511',
                    ),
                    4 => array(
                        'Collection' => 'ark:/12345/001515',
                        'Item' => 'ark:/12345/01518',
                    ),
                ),
            ),

            array(
                'prefixes' => array(
                    'Collection' => '0',
                    'Item' => '',
                ),
                'results' => array(
                    3 => array(
                        'Collection' => 'ark:/12345/01518',
                        'Item' => 'ark:/12345/1511',
                    ),
                    4 => array(
                        'Collection' => 'ark:/12345/001515',
                        'Item' => 'ark:/12345/01518',
                    ),
                ),
            ),
        );

        $this->_processTests($tests, $recordId);
    }

    /**
     * @expectedException Ark_ArkException
     */
    public function testErrorPrefix()
    {
        $recordId = 151;

        $tests = array(
            array(
                'prefixes' => array(
                    'Collection' => '1',
                    'Item' => '',
                ),
                'results' => array(
                    0 => array(
                        'Collection' => 'ark:/12345/fkc01519xxxx',
                        'Item' => 'ark:/12345/fk01512xxxx',
                    ),
                ),
            ),
        );

        $this->_processTests($tests, $recordId, true);
    }

    /**
     * @expectedException Ark_ArkException
     */
    public function testErrorPrefixBis()
    {
        $recordId = 151;

        $tests = array(
            array(
                'prefixes' => array(
                    'Collection' => '1',
                    'Item' => '',
                ),
                'results' => array(
                    4 => array(
                        'Collection' => 'ark:/12345/101512',
                        'Item' => 'ark:/12345/01518',
                    ),
                ),
            ),
        );

        $this->_processTests($tests, $recordId, true);

        $this->markTestSkipped(
            __('Next assertion should be an error: duplicate ark for the item (bad prefix).')
        );

        // If the prefix is not the zero or outside the alphabet, warn.
        $this->_checkArk($parameters, 10151, array(
            'Collection' => 'ark:/12345/1101517',
            'Item' => 'ark:/12345/101512',
        ));
    }

    protected function _processTests($tests, $recordId, $isError = false)
    {
        foreach ($tests as $test) {
            set_option('ark_id_prefix_collection', $test['prefixes']['Collection']);
            set_option('ark_id_prefix_item', $test['prefixes']['Item']);

            foreach (array_keys($test['results']) as $length) {
                $parameters = array(
                    'protocol' => 'ark:',
                    'naan' => '12345',
                    'prefix' => '',
                    'suffix' => '',
                    'length' => $length,
                    'pad' => '0',
                    'salt' => '',
                    'alphabet' => 'numeric',
                    'control_key' => true,
                    'command' => '',
                    'identifix' => $test['prefixes']['Collection'] === $test['prefixes']['Item'],
                );

                $this->_checkArk($parameters, $recordId, $test['results'][$length]);
            }
        }
    }

    protected function _checkArk($parameters, $recordId, $result, $isError = false)
    {
        $results = array();
        foreach ($result as $recordType => $value) {
            $parameters['prefix'] = get_option('ark_id_prefix_' . strtolower($recordType));

            $processor = new Ark_Name_OmekaId($parameters);
            $record = new $recordType;
            $record->id = $recordId;

            // If testing error, the process returns after this function.
            $ark = $processor->create($record);

            $results[$recordType] = $ark;

            $this->assertEquals($value, $ark,
                __('Error with prefix "%s" and record type "%s".', $parameters['prefix'], $recordType));

            release_object($record);
        }

        // Of course, because they are good…
        $unique = array_unique($results);
        $this->assertTrue(count($unique) == count($results),
            __('Arks are identical for collection and item (prefix "%s").', $parameters['prefix']));
    }
}
