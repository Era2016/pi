<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Pi\Application\Service;

use Pi;
use Pi\Form\View\Helper\AbstractEditor;

/**
 * Editor service
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Editor extends AbstractService
{
    /** {@inheritDoc} */
    //protected $fileIdentifier = 'editor';

    /**
     * Loads an editor view helper with configs
     *
     * @param string $type
     * @param array $options
     *
     * @return AbstractEditor
     */
    public function load($type = '', array $options = array())
    {
        if (empty($type)) {
            $type = Pi::config('editor') ?: 'pi';
        }
        switch ($type) {
            case 'html':
                $editor = Pi::config('editor') ?: 'ckeditor';
                break;
            /*
            case 'markitup':
            case 'markdown':
            case 'wiki':
            case 'bbcode':
                $options['set'] = $type;
                $editor = Pi::config('editor') ?: 'ckeditor';
                break;
            */
            default:
                $editor = $type;
                break;
        }
        $rendererClass =  sprintf(
            'Editor\%s\View\Helper\FormEditor%s',
            ucfirst($editor),
            ucfirst($editor)
        );
        if (!class_exists($rendererClass)
            || !is_subclass_of($rendererClass, 'Pi\Form\View\Helper\AbstractEditor')
        ) {
            $rendererClass = 'Pi\Form\View\Helper\FormEditorPi';
        }

        $renderer = new $rendererClass($options);

        return $renderer;
    }

    /**
     * Get available editor list
     *
     * @return array
     */
    public function getList()
    {
        $list = array('pi' => __('Pi Default Editor'));

        $filter = function ($fileinfo) use (&$list) {
            if (!$fileinfo->isDir()) {
                return false;
            }
            $name = $fileinfo->getFilename();
            if (preg_match('/[^a-z0-9_]/i', $name)) {
                return false;
            }
            $configFile = $fileinfo->getPathname() . '/config.php';
            if (!file_exists($configFile)) {
                $list[$name] = $name;
                return;
            }
            $info = include $configFile;
            if (!empty($info['disable'])) {
                return;
            }
            if (!empty($info['name'])) {
                $list[$name] = $info['name'];
            }
        };
        Pi::service('file')->getList(
            'usr/editor',
            $filter
        );

        return $list;
    }
}
