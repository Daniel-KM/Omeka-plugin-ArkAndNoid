<?php
/**
 * Ark
 *
 * Creates and manages unique, universel and persistent ark identifiers.
 *
 * @copyright Daniel Berthereau, 2015
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * The Ark plugin.
 * @package Omeka\Plugins\Ark
 */
class ArkPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'uninstall',
        'config_form',
        'config',
        'define_routes',
        'after_save_collection',
        'after_save_item',
        // TODO Save ark for files?
    );

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = array(
        'ark_format_names',
        'ark_format_qualifiers',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        // 12345 means example and 99999 means test.
        'ark_naan' => '99999',
        'ark_format_name' => 'base',
        'ark_prefix_collection' => '',
        'ark_suffix_collection' => '',
        'ark_prefix_item' => '',
        'ark_suffix_item' => '',
        'ark_control_key' => '',
        'ark_length' => 4,
        'ark_alphabet' => 'alphanumeric_no_vowel',
        'ark_options' => '',
        'ark_format_qualifier' => 'order',
        'ark_note' => '',
        'ark_policy_statement' => 'erc-support:
who: Our Institution
what: Permanent: Stable Content:
when: 20150101
where: http://example.com/ark:/12345/',
        // From the policy statement of the California Digital Library.
        'ark_policy_main' => 'Our institution assigns identifiers within the ARK domain under the NAAN 12345 and according to the following principles:

* No ARK shall be re-assigned; that is, once an ARK-to-object association has been made public, that association shall be considered unique into the indefinite future.
* To help them age and travel well, the Name part of our institution-assigned ARKs shall contain no widely recognizable semantic information (to the extent possible).
* Our institution-assigned ARKs shall be generated with a terminal check character that guarantees them against single character errors and transposition errors.',
        'ark_use_public' => true,
        'ark_use_admin' => false,
        'ark_file_variants' => 'original fullsize thumbnail square_thumbnail',
    );

    /**
     * Add the translations and shortcode.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        add_shortcode('ark', array($this, 'shortcodeArk'));
    }

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/ark-config-form.php',
            array(
                'format_names' => $this->_getListOfFormats('ark_format_names'),
                'format_qualifiers' => $this->_getListOfFormats('ark_format_qualifiers'),
            )
        );
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    /**
     * Defines routes.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];
        $router->addConfig(new Zend_Config_Ini(dirname(__FILE__) . '/routes.ini', 'routes'));

        // Add a main policy route.
        $router->addRoute('ark_policy_short', new Zend_Controller_Router_Route(
            'ark/policy',
            array(
                'module' => 'ark',
                'controller' => 'index',
                'action' => 'policy',
                'naan' => get_option('ark_naan'),
            )
        ));
    }

    /**
     * Create or check an ark when a collection is saved, with the id.
     */
    public function hookAfterSaveCollection($args)
    {
        $this->_afterSaveRecord($args);
    }

    /**
     * Create or check an ark when an item is saved, with the id.
     */
    public function hookAfterSaveItem($args)
    {
        $this->_afterSaveRecord($args);
    }

    /**
     * Create or check an ark when a record is saved, with the id.
     */
    protected function _afterSaveRecord($args)
    {
        $record = $args['record'];

        // Check if an ark exists (no automatic change or update).
        $ark = get_view()->ark($record);
        // If no ark, create one.
        if (empty($ark)) {
            $formats = apply_filters('ark_format_names', array());

            // Check the selected format (avoid issue for extra plugin class).
            $format = get_option('ark_format_name');
            if (!isset($formats[$format])) {
                throw new Ark_ArkException(__('Ark format for names "%s" is missing.', $format));
            }
            $class = $formats[$format]['class'];
            // TODO Check class (issue with Zend autoload).
            // if (!class_exists($format)) {
            //     throw new Ark_ArkException(__('Ark class "%s" is missing.', $class));
            // }

            $ark = new $class(array(
                'naan' => get_option('ark_naan'),
                'prefix' => get_option('ark_prefix_' . strtolower(get_class($record))),
                'suffix' => get_option('ark_suffix' . strtolower(get_class($record))),
                'control_key' => get_option('ark_control_key'),
                'length' => get_option('ark_length'),
                'alphabet' => get_option('ark_alphabet'),
                'options' => get_option('ark_options'),
            ));
            $ark = $ark->create($record);
            if ($ark) {
                $element = $record->getElement('Dublin Core', 'Identifier');
                $record->addTextForElement($element, $ark, false);
                $record->saveElementTexts();
            }
        }
    }

    /**
     * Add the formats that are available for names.
     *
     * @param array $formatNames Array of formats for names.
     * @return array Filtered formats array.
    */
    public function filterArkFormatNames($formatNames)
    {
        // Available default formats in the plugin.
        $formatNames['omeka_id'] = array(
            'class' => 'Ark_Name_OmekaId',
            'description' => __('Omeka Id'),
        );
        $formatNames['base'] = array(
            'class' => 'Ark_Name_Base',
            'description' => __('Base change'),
        );
        $formatNames['noid'] = array(
            'class' => 'Ark_Name_Noid',
            'description' => __('NOID ("nice opaque identifiers", if installed)'),
        );
        return $formatNames;
    }

    /**
     * Add the formats that are available for qualifiers.
     *
     * @param array $formatQualifiers Array of formats for qualifiers.
     * @return array Filtered formats array.
    */
    public function filterArkFormatQualifiers($formatQualifiers)
    {
        // Available default formats in the plugin.
        $formatQualifiers['omeka_id'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka Id'),
        );
        $formatQualifiers['order'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Order'),
        );
        $formatQualifiers['filename'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka filename'),
        );
        $formatQualifiers['filename_without_extension'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka filename without extension'),
        );
        $formatQualifiers['original_filename'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Original filename'),
        );
        $formatQualifiers['original_filename_without_extension'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Original filename without extension'),
        );
        return $formatQualifiers;
    }

    /**
     * Get the simple list of formats (name and description).
     *
     * @param string $filter Name of the filter.
     * @return array Associative array of the name and description of formats.
     */
    protected function _getListOfFormats($filter)
    {
        $values = apply_filters($filter, array());
        foreach ($values as $name => &$value) {
            if (class_exists($value['class'])) {
                $value = $value['description'];
            }
            else {
                unset($values[$name]);
            }
        }
        return $values;
    }

    /**
     * Shortcode to display the ark of a record.
     *
     * @param array $args
     * @param Omeka_View $view
     * @return string
     */
    public function shortcodeArk($args, $view)
    {
        // Check required arguments
        if (empty($args['record_id'])) {
            return '';
        }
        $recordId = (integer) $args['record_id'];

        $recordType = isset($args['record_type']) ? $args['record_type'] : 'Item';
        $recordType = ucfirst(strtolower($recordType));

        // Quick checks.
        $record = get_record_by_id($recordType, $recordId);
        if (!$record) {
            return '';
        }

        // Get display values (link or text).
        $display = isset($args['display']) ? $args['display'] : null;

        return $view->ark($record, $display);
    }
}
