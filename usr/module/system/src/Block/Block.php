<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\System\Block;

use Module\User\Form\LoginForm;
use Pi;
use Module\System\Form\LoginForm as LoginFormSystem;
use Module\User\Form\LoginForm as LoginFormUser;

/**
 * Block renderer
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Block
{
    /**
     * Site information block
     *
     * @return array
     */
    public static function site()
    {
        return array(
            'caption'   => sprintf(_b('About %s'), Pi::config('sitename')),
            'items'     => array(
                _b('Site name') => Pi::config('sitename'),
                _b('Slogan')    => Pi::config('slogan'),
            ),
        );
    }

    /**
     * User link block
     *
     * @return bool|array
     */
    public static function user()
    {
        // Check user is login
        if (!Pi::service('user')->hasIdentity()) {
            return false;
        }
        // Get uid
        $uid = Pi::user()->getId();
        // Get user
        $parameters = array('id', 'identity', 'name', 'email');
        // User module installed
        if (Pi::service('module')->isActive('user')) {
            $parameters[] = 'first_name';
        }
        // Get user
        $user = Pi::user()->get($uid, $parameters);
        $user['profileUrl'] = Pi::service('user')->getUrl('profile');
        $user['avatar'] = Pi::service('user')->avatar($uid, 'large' , array(
            'alt' => $user['name'],
            'class' => 'img-thumbnail'
        ));
        if (Pi::service('module')->isActive('user')) {
            $user['accountUrl'] = Pi::service('user')->getUrl('user' , array('controller' => 'account'));
            $user['avatarUrl'] = Pi::service('user')->getUrl('user' , array('controller' => 'avatar'));
        }
        return $user;
    }

    /**
     * User bar
     *
     * Render types: js, dropdown, flat
     *
     * @param array $options
     *
     * @return array
     */
    public static function userbar($options = array())
    {
        $hasIdentity = Pi::service('user')->hasIdentity();

        if (!empty($options['type'])) {
            $type = $options['type'];
        } else {
            $type = '';
        }

        if (!empty($options['params'])) {
            $params = $options['params'];
        } else {
            $params = array();
        }

        $result = array(
            'type'           => $type,
            'float'          => $options['float'],
            'show_title'     => $options['show_title'],
            'show_message'   => $options['show_message'],
            'show_order'     => $options['show_order'],
            'show_credit'    => $options['show_credit'],
            'show_support'   => $options['show_support'],
            'show_favourite' => $options['show_favourite'],
            'count'          => 0,
        );

        if (!$hasIdentity) {
            $user = array(
                'uid'       => 0,
                'login'     => Pi::url(Pi::service('authentication')->getUrl('login', $params)),
                'register'  => Pi::url(Pi::service('user')->getUrl('register', $params)),
            );
        } else {
            $uid    = Pi::service('user')->getUser()->get('id');
            $name   = Pi::service('user')->getUser()->get('name');
            $avatar = Pi::service('user')->getPersist('avatar-small');

            if (!$avatar) {
                $avatar = Pi::service('user')->avatar($uid, 'small', array('width' => 16, 'height' => 16));
                Pi::service('user')->setPersist('avatar-small', $avatar);
            }

            // User module installed
            $firstName = '';
            $dashboard = Pi::service('user')->getUrl('profile', $params);
            if (Pi::service('module')->isActive('user')) {
                $firstName = Pi::service('user')->getUser()->first_name;
                $dashboard = Pi::service('url')->assemble(
                    'user',
                    array(
                        'module'        => 'user',
                        'controller'    => 'dashboard',
                        'action'        => 'index',
                    )
                );
            }

            $user = array(
                'uid'        => Pi::service('user')->getId(),
                'name'       => $name,
                'first_name' => $firstName,
                'avatar'     => $avatar,
                'profile'    => Pi::url(Pi::service('user')->getUrl('profile', $params)),
                'logout'     => Pi::url(Pi::service('authentication')->getUrl('logout', $params)),
                'dashboard'  => Pi::url($dashboard),
            );
        }

        if ($options['show_message'] != 'none' && Pi::service('module')->isActive('message') && $hasIdentity) {
            switch ($options['show_message']) {
                case 'boot':
                    $countMessage = Pi::api('api', 'message')->getUnread($user['uid'], 'message');
                    $countNotification = Pi::api('api', 'message')->getUnread($user['uid'], 'notification');
                    $user['message_url'] = Pi::url(Pi::service('user')->message()->getUrl());
                    $user['notification_url'] = Pi::url(Pi::service('url')->assemble(
                        'default',
                        array(
                            'module'        => 'message',
                            'controller'    => 'notify',
                            'action'        => 'index',
                        )
                    ));
                    $user['message_count'] = _number($countMessage);
                    $user['notification_count'] = _number($countNotification);
                    $result['count'] = $result['count'] + $countMessage + $countNotification;
                    break;

                case 'message':
                    $count = Pi::api('api', 'message')->getUnread($user['uid'], 'message');
                    $user['message_url'] = Pi::url(Pi::service('user')->message()->getUrl());
                    $user['message_count'] = _number($count);
                    $result['count'] = $result['count'] + $count;
                    break;

                case 'notification':
                    $count = Pi::api('api', 'message')->getUnread($user['uid'], 'notification');
                    $user['notification_url'] = Pi::url(Pi::service('url')->assemble(
                        'default',
                        array(
                            'module'        => 'message',
                            'controller'    => 'notify',
                            'action'        => 'index',
                        )
                    ));
                    $user['notification_count'] = _number($count);
                    $result['count'] = $result['count'] + $count;
                    break;

                case 'merge':
                    $count = Pi::api('api', 'message')->getUnread($user['uid']);
                    $user['message'] = Pi::url(Pi::service('user')->message()->getUrl());
                    $user['count'] = _number($count);
                    $result['count'] = $result['count'] + $count;
                    break;
            }

        }

        if ($options['show_order'] && Pi::service('module')->isActive('order') && $hasIdentity) {
            $user['order'] = Pi::url(Pi::service('url')->assemble(
                'order',
                array(
                    'module'        => 'order',
                    'controller'    => 'index',
                    'action'        => 'index',
                )
            ));
        }

        if ($options['show_credit'] && Pi::service('module')->isActive('order') && $hasIdentity) {
            $orderConfig = Pi::service('registry')->config->read('order');
            if ($orderConfig['credit_active']) {
                $credit = Pi::api('credit', 'order')->getCredit();
                $user['amount'] = $credit['amount'];
                $user['amount_view'] = $credit['amount_view'];
                $user['credit'] = Pi::url(Pi::service('url')->assemble(
                    'order',
                    array(
                        'module'        => 'order',
                        'controller'    => 'credit',
                        'action'        => 'index',
                    )
                ));
            }
        }

        if ($options['show_support'] && Pi::service('module')->isActive('support') && $hasIdentity) {
            $count = Pi::api('ticket', 'support')->getCount();
            $user['support_count'] = _number($count);
            $user['support_url'] = Pi::url(Pi::service('url')->assemble(
                'support',
                array(
                    'module'        => 'support',
                    'controller'    => 'index',
                    'action'        => 'index',
                )
            ));
            $result['count'] = $result['count'] + $count;
        }

        if ($options['show_favourite'] && Pi::service('module')->isActive('favourite') && $hasIdentity) {
            if (Pi::service('module')->isActive('guide')) {
                $user['favourite_url'] = Pi::url(Pi::service('url')->assemble(
                    'guide',
                    array(
                        'module'        => 'guide',
                        'controller'    => 'favourite',
                        'action'        => 'index',
                    )
                ));
            } else {
                $user['favourite_url'] = Pi::url(Pi::service('url')->assemble(
                    'default',
                    array(
                        'module'        => 'favourite',
                        'controller'    => 'index',
                        'action'        => 'index',
                    )
                ));
            }
        }

        if (Pi::service('module')->isActive('guide')) {
            $user['dashboard_url'] = Pi::url(Pi::service('url')->assemble(
                'guide',
                array(
                    'module'        => 'guide',
                    'controller'    => 'manage',
                    'action'        => 'dashboard',
                )
            ));
        }

        $result['user'] = $user;

        if (Pi::service('module')->isActive('user')) {

            $enabledModal = Pi::user()->config('enable_modal');
            if($enabledModal){
                /*
                * Login form
                */
                $processPath = Pi::service('url')->assemble('user', array('module' => 'user', 'controller' => 'login', 'action' => 'process'));
                $loginForm = Pi::api('form', 'user')->loadForm('login');
                $loginForm->setAttribute('action', Pi::url($processPath));

                /**
                 * Register form
                 */
                $processPath = Pi::service('url')->assemble('user', array('module' => 'user', 'controller' => 'register'));
                $registerForm = Pi::api('form', 'user')->loadForm('register');
                $registerForm->setAttribute('action', Pi::url($processPath));

                $result['loginForm'] = $loginForm;
                $result['registerForm'] = $registerForm;
            }

            /**
             * Form validator JS
             */
            $view = Pi::service('view');
            $view->getHelper('footScript')->prependFile($view->getHelper('assetModule')->__invoke('front/validator.min.js', 'user'));

        }

        return $result;
    }

    /**
     * User login form block
     *
     * @param array $options
     *
     * @return bool|array
     */
    public static function login($options = array())
    {
        if (Pi::service('user')->hasIdentity()) {
            return false;
        }
        if (Pi::service('module')->isActive('user')) {
            $form = new LoginFormUser('login');
        } else {
            $form = new LoginFormSystem('login');
        }
        if (!empty($options['route'])) {
            $route = $options['route'];
            unset($options['route']);
        } else {
            $route = Pi::service('user')->getRoute();
        }
        if (!empty($options['action'])) {
            $action = $options['action'];
            unset($options['action']);
        } else {
            $action = Pi::service('url')->assemble(
                $route,
                array(
                    'module'        => 'system',
                    'controller'    => 'login',
                    'action'        => 'process',
                )
            );
        }
        if ($options) {
            $form->setData($options);
        }
        $form->setAttribute('action', $action);

        return array(
            'form'  => $form,
        );
    }

    /**
     * Get Pi Engine feature API
     *
     * @return string
     */
    public static function pi()
    {
        $featureApi =
            'https://raw.github.com/pi-engine/pi/master/doc/README.html';
        return array(
            'api'   => $featureApi,
        );
    }
}