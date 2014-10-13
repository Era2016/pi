<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\System\Form;

use Pi;
use Zend\InputFilter\InputFilter;
use Module\System\Validator\Username as UsernameValidator;

/**
 * Account form filter
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class AccountFilter extends InputFilter
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $config = Pi::user()->config();

        $this->add(array(
            'name'          => 'identity',
            'required'      => true,
            'filters'       => array(
                array(
                    'name'  => 'StringTrim',
                ),
            ),
            'validators'    => array(
                array(
                    'name'      => 'StringLength',
                    'options'   => array(
                        'encoding'  => 'UTF-8',
                        'min'       => $config['uname_min'],
                        'max'       => $config['uname_max'],
                    ),
                ),
                new UsernameValidator(array(
                    'format'            => $config['uname_format'],
                    'blacklist'         => $config['uname_blacklist'],
                    'check_duplication' => true,
                )),
            ),
        ));

        $this->add(array(
            'name'          => 'name',
            'required'      => false,
            'filters'       => array(
                array(
                    'name'  => 'StringTrim',
                ),
            ),
        ));

        $this->add(array(
            'name'          => 'id',
        ));
    }
}
