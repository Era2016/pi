<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 * @package         View
 */

namespace Pi\View\Helper;

use Pi;
use Zend\View\Helper\AbstractHelper;

/**
 * Return is user section status
 *
 * Usage inside a phtml template

 * ```
 *  // Load helper for current request URI
 *  $userSection = $this->isUserSection();
 * ```
 *
 * @author Frédéric TISSOT <contact@espritdev.fr>
 */
class IsUserSection extends AbstractHelper
{
    /**
     * Invoke helper
     *
     * @param $module
     * @return bool
     */
    public function __invoke($module)
    {
        $uid = Pi::user()->getId();
        $userSection = false;

        if (in_array($module, array('user', 'order', 'favourite', 'message', 'support')) && $uid > 0) {
            $userSection = true;
        } elseif ($module == 'guide' && $uid > 0) {
            $d = (array) Pi::service('url')->getRouteMatch();
            foreach ($d as $value) {
                $a[] = $value;
            }
            if ($a[1]['controller'] == 'manage') {
                $userSection = true;
            }
            if ($a[1]['controller'] == 'favourite') {
                $userSection = true;
            }
        }
        elseif ($module == 'event' && $uid > 0) {
            $d = (array) Pi::service('url')->getRouteMatch();
            foreach ($d as $value) {
                $a[] = $value;
            }
            if ($a[1]['controller'] == 'manage') {
                $userSection = true;
            }
        }

        return $userSection;
    }
}