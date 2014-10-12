<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link         http://code.pialog.org for the Pi Engine source repository
 * @copyright    Copyright (c) Pi Engine http://pialog.org
 * @license      http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Article\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Pi\Paginator\Paginator;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Entity;

/**
 * Category controller
 * 
 * Feature list:
 * 
 * 1. List article of a category
 * 
 * @author Zongshu Lin <lin40553024@163.com>
 */
class CategoryController extends ActionController
{
    /**
     * Parse action name
     * 
     * @param string  $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $module = Pi::service('module')->current();
        $pages  = Pi::registry('page', $module)->read();
        
        $name = '';
        foreach ($pages as $page) {
            if ($action === $page['name']) {
                $name = $page['action'];
                break;
            }
        }
 
        return parent::getMethodFromAction($name ?: $action);
    }
    
    /**
     * List articles of a category
     */
    public function listAction()
    {
        $module = $this->getModule();
        $modelCategory = $this->getModel('category');

        $category   = $this->params('category', '');
        $categoryId = Pi::api('category', $module)->slugToId($category);
        $page       = $this->params('p', 1);
        $page       = $page > 0 ? $page : 1;

        $config = Pi::config('', $module);
        $limit  = (int) $config['page_limit_all'] ?: 40;
        $where  = array();
        
        $route  = Pi::api('api', $module)->getRouteName();

        // Get category nav
        $rowset = Pi::model('category', $module)->enumerate(null, null);
        $rowset = array_shift($rowset);
        $navs   = $this->canonizeCategory($rowset['child'], $route);
        $allNav['all'] = array(
            'label'      => __('All'),
            'route'      => $route,
            'controller' => 'list',
            'params'     => array(
                'category'   => 'all',
            ),
        );
        $navs = $allNav + $navs;
        
        // Get all categories
        $categories = array(
            'all' => array(
                'id'    => 0,
                'title' => __('All articles'),
                'image' => '',
                'url'   => Pi::service('url')->assemble(
                    'article',
                    array(
                        'module'        => $module,
                        'controller'    => 'list',
                        'action'        => 'all',
                        'list'          => 'all',
                    )
                ),
            ),
        );
        $rowset = Pi::model('category', $module)->enumerate(null, null, true);
        foreach ($rowset as $row) {
            if ('root' == $row['name']) {
                continue;
            }
            $url = Pi::service('url')->assemble('', array(
                'module'        => $module,
                'controller' => 'category',
                'action'     => 'list',
                'category'   => $row['id'],
            ));
            $categories[$row['id']] = array(
                'id'    => $row['id'],
                'title' => $row['title'],
                'image' => $row['image'],
                'url'   => $url,
            );
        }
        $categoryIds = $modelCategory->getDescendantIds($categoryId);
        if (empty($categoryIds)) {
            return $this->jumpTo404(__('Invalid category id'));
        }
        $where['category'] = $categoryIds;
        $categoryInfo      = $categories[$categoryId];
        
        // Get subcategories article count
        $modelArticle = $this->getModel('article');
        $select       = $modelArticle->select()
            ->where(array(
                'category' => $categoryIds,
                'active'   => 1,
                'time_publish < ?' => time(),
            ))
            ->columns(array('category', 'count' => new Expression('count(*)')))
            ->group(array('category'));
        $resultCount  = $modelArticle->selectWith($select);
        $counts       = array();
        foreach ($resultCount as $row) {
            $counts[$row['category']] = $row['count'];
        }

        // Get articles
        $columns = array(
            'id', 'subject', 'time_publish', 'category', 'summary', 'author',
            'image', 
        );
        $resultsetArticle  = Entity::getAvailableArticlePage(
            $where,
            $page,
            $limit,
            $columns,
            null,
            $module
        );
        
        $articleCategoryIds = $authorIds = array();
        foreach ($resultsetArticle as $row) {
            $authorIds[]   = $row['author'];
            $articleCategoryIds[] = $row['category'];
        }
        
        // Get author
        $authors = array();
        if (!empty($authorIds)) {
            $rowAuthor = $this->getModel('author')
                ->select(array('id' => $authorIds));
            foreach ($rowAuthor as $row) {
                $authors[$row->id] = $row->toArray();
            }
        }
        
        // Total count
        $where = array_merge($where, array(
            'time_publish <= ?' => time(),
            'status'            => Article::FIELD_STATUS_PUBLISHED,
            'active'            => 1,
        ));
        $totalCount = $modelArticle->count($where);

        // Pagination
        $paginator = Paginator::factory($totalCount, array(
            'limit'       => $limit,
            'page'        => $page,
            'url_options' => array(
                'page_param'    => 'p',
                'params'        => array(
                    'module'        => $module,
                    'category'      => $category,
                ),
            ),
        ));
        
        $seo = Pi::api('page', $module)->getSeoMeta($this->params('action'));

        $this->view()->assign(array(
            'title'         => __('Article List in Category'),
            'articles'      => $resultsetArticle,
            'paginator'     => $paginator,
            'categories'    => $categories,
            'categoryInfo'  => $categoryInfo,
            'category'      => $category,
            'config'        => $config,
            'counts'        => $counts,
            'categoryId'    => array_shift($categoryIds),
            'subCategoryId' => $categoryIds,
            'route'         => $route,
            'elements'      => $config['list_item'],
            'authors'       => $authors,
            'length'        => $config['list_summary_length'],
            'navs'          => $this->config('enable_list_nav') ? $navs : '',
            'seo'           => $seo,
        ));
        
        $this->view()->setTemplate('category-list');
    }
    
    /**
     * Canonize category structure
     * 
     * @params array  $categories
     * @params string $route
     */
    protected function canonizeCategory(&$categories, $route)
    {
        foreach ($categories as &$row) {
            $row['label']      = $row['title'];
            $row['controller'] = 'category';
            $row['action']     = 'list';
            $row['params']     = array(
                'category' => $row['slug'] ?: $row['id'],
            );
            $row['route']      = $route;
            if (isset($row['child'])) {
                $row['pages'] = $row['child'];
                unset($row['child']);
                $this->canonizeCategory($row['pages'], $route);
            }
        }
        
        return $categories;
    }
}
