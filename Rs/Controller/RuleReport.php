<?php
namespace Rs\Controller;

use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleReport extends \App\Controller\AdminManagerIface
{

    /**
     * @var array
     * @deprecated
     */
    static public $calcCache = array();

    /**
     * @var array
     */
    public $cache = array();


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Rule Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->setTable(\App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName()));
        $this->getTable()->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('Student Number');
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('name'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Email('email'))->addCss('key');

        $subject = $this->getSubject();
        $rules = \Rs\Db\RuleMap::create()->findFiltered(array('courseId' => $this->getCourseId(), 'subjectId' => $subject->getId()));

        foreach ($rules as $rule) {
            $this->getTable()->appendCell(new \Tk\Table\Cell\Text($rule->getLabel()))->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
                /** @var \Tk\Table\Cell\Text $cell  */
                /** @var \App\Db\User $obj  */
                $tblFilter = $cell->getTable()->getFilterValues();
                //if (empty(self::$calcCache[$obj->getId()])) {
                if (empty($this->cache[$obj->getId()])) {
                    $filter = array(
                        'userId' => $obj->getId(),
                        'subjectId' => $subject->getId(),
                        'status' => array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_EVALUATING, \App\Db\Placement::STATUS_COMPLETED)
                    );
                    $placementList = \App\Db\PlacementMap::create()->findFiltered($filter);
                    //self::$calcCache[$obj->getId()] = \Rs\Calculator::createFromPlacementList($placementList);
                    $this->cache[$obj->getId()] = \Rs\Calculator::createFromPlacementList($placementList);
                }
                /** @var \Rs\Calculator $calc */
                //$calc = self::$calcCache[$obj->getId()];
                $calc = $this->cache[$obj->getId()];

                $field = 'total';
                if (!empty($tblFilter['results']))
                    $field = $tblFilter['results'];

                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr[$cell->getProperty()]))
                        return $arr[$cell->getProperty()][$field];
                }
                return '';
            });

        }

        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('Pending'))->addCss('mh-totals')->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
            /** @var \Tk\Table\Cell\Text $cell  */
            /** @var \App\Db\User $obj  */
            if (isset($this->cache[$obj->getId()])) {
                $calc = $this->cache[$obj->getId()];
                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr['pending'])) {
                        return $arr['pending'];
                    }
                }
            }
            return '';
        });

        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('Approved'))->addCss('mh-totals')->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
            /** @var \Tk\Table\Cell\Text $cell  */
            /** @var \App\Db\User $obj  */
            if (isset($this->cache[$obj->getId()])) {
                $calc = $this->cache[$obj->getId()];
                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr['approved'])) {
                        return $arr['approved'];
                    }
                }
            }
            return '';
        });

        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('Evaluating'))->addCss('mh-totals')->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
            /** @var \Tk\Table\Cell\Text $cell  */
            /** @var \App\Db\User $obj  */
            if (isset($this->cache[$obj->getId()])) {
                $calc = $this->cache[$obj->getId()];
                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr['evaluating'])) {
                        return $arr['evaluating'];
                    }
                }
            }
            return '';
        });
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('Completed'))->addCss('mh-totals')->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
            /** @var \Tk\Table\Cell\Text $cell  */
            /** @var \App\Db\User $obj  */
            if (isset($this->cache[$obj->getId()])) {
                $calc = $this->cache[$obj->getId()];
                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr['completed'])) {
                        return $arr['completed'];
                    }
                }
            }
            return '';
        });
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('Total'))->addCss('mh-totals')->addOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
            /** @var \Tk\Table\Cell\Text $cell  */
            /** @var \App\Db\User $obj  */
            if (isset($this->cache[$obj->getId()])) {
                $calc = $this->cache[$obj->getId()];
                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr['total'])) {
                        return $arr['total'];
                    }
                }
            }
            return '';
        });




        // Filters
        $list = array('-- Status --' => '', 'Pending' => 'pending', 'Completed' => 'completed');
        $this->getTable()->appendFilter(new Field\Select('results', $list)); //->setLabel('Status');

        // Actions
        $this->getTable()->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->getTable()->appendAction(\Tk\Table\Action\Csv::create());

        $this->getTable()->setList($this->getList());
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->getTable()->getFilterValues();
        $filter['subjectId'] = $this->getSubject()->getId();
        $filter['type'] = \Uni\Db\User::TYPE_STUDENT;

        return \App\Db\UserMap::create()->findFiltered($filter, $this->table->getTool('a.name_first'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $css = <<<CSS
/*html body table tr td.mh-totals:first-child {*/
/*  border-left-style: double;*/
/*  border-left-color: #0c0c0c;*/
/*  background-color: #CCC;*/
/*} */
html body table tr td.mh-totals {
  background-color: #EFEFEF;
} 
CSS;
        $template->appendCss($css);

        $template->appendTemplate('panel', $this->getTable()->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-title="Rule Report" data-panel-icon="fa fa-check" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

