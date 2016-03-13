<?php
/**
 * The plugin controller for index pages.
 *
 * @package Ark
 */
class Ark_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Route the url to the original record.
     */
    public function indexAction()
    {
        $naan = $this->getParam('naan');
        $naanArk = get_option('ark_naan');

        // Check are kept, because the file "routes.ini" may be used.
        if ($naan !== $naanArk) {
            throw new Omeka_Controller_Exception_404;
        }

        // Check special name (empty, "?" or "??")).
        $name = $this->getParam('name');
        if (empty($name) || $name == '?' || $name == '??') {
            return $this->forward('policy', 'index', 'ark', array(
                'module' => 'Ark',
                'controller' => 'index',
                'action' => 'policy',
                'naan' => $naan,
            ));
        }

        $qualifier = $this->getParam('qualifier');

        $record = $this->view->getRecordFromArk(array(
            'naan' => $naan,
            'name' => $name,
            'qualifier' => $qualifier,
        ), 'type and id');
        if (empty($record)) {
            throw new Omeka_Controller_Exception_404;
        }

        $recordType = $record['record_type'];
        $recordId = $record['record_id'];

        // Manage special uris.
        $last = empty($_SERVER['REQUEST_URI'])
            ? ''
            : substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);
        switch ($last) {
            case '?':
                return $this->forward('metadata', 'index', 'ark', array(
                    'module' => 'Ark',
                    'controller' => 'index',
                    'action' => 'metadata',
                    'type' => $recordType,
                    'id' => $recordId,
                ));

            case '??':
                return $this->forward('metadata', 'index', 'ark', array(
                    'module' => 'Ark',
                    'controller' => 'index',
                    'action' => 'metadata',
                    'type' => $recordType,
                    'id' => $recordId,
                    'policy' => true,
                ));
        }

        $variant = $this->getParam('variant');
        // Only variants of files are managed.
        if ($recordType == 'File' && !empty($variant)
                && in_array($variant, explode(' ', get_option('ark_file_variants')))
            ) {
            $record = get_record_by_id($recordType, $recordId);
            $url = $record->getWebPath($variant);
            return $this->redirect($url);
        }

        $controller = str_replace('_', '-', Inflector::tableize($recordType));
        return $this->forward('show', $controller, 'default', array(
            'module' => null,
            'controller' => $controller,
            'action' => 'show',
            'record_type' => $recordType,
            'id' => $recordId,
        ));
    }

    /**
     * Returns a brief machine- and eye-readable metadata record.
     *
     * @todo Implements erc.
     * @link http://dot.ucop.edu/specs/ercspec.html
     */
    public function metadataAction()
    {
        $type = $this->getParam('type');
        $id = $this->getParam('id');

        $record = get_record_by_id($type, $id);
        if (empty($record)) {
            throw new Omeka_Controller_Exception_404;
        }

        $this->view->record = $record;
        $this->view->absolute_ark = $this->view->ark($record, 'absolute');
        $this->view->policy = $this->getParam('policy')
            ? get_option('ark_policy_statement')
            : '';

        $this->getResponse()
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
    }

    /**
     * Returns the main policy for the institution.
     */
    public function policyAction()
    {
        $naan = $this->getParam('naan');
        $naanArk = get_option('ark_naan');

        // Check are kept, because the file "routes.ini" may be used.
        if ($naan !== $naanArk) {
            throw new Omeka_Controller_Exception_404;
        }

        $last = empty($_SERVER['REQUEST_URI'])
            ? ''
            : substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);
        switch ($last) {
            case '?':
            case '??':
                $this->getResponse()
                    ->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
                $this->view->policy = get_option('ark_policy_main');
                $this->view->html = false;
                break;

            default:
                $this->view->policy = nl2br(get_option('ark_policy_main'));
                $this->view->html = true;
        }
    }
}
