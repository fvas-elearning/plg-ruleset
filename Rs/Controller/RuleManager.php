<?php
namespace Rs\Controller;

use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleManager extends \App\Controller\AdminManagerIface
{


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Rule Manager');
    }

    /**
     * @param Request $request
     * @return \Tk\Response
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        if ($request->get('action') == 'update')
            return $this->doUpdate($request);

        $this->setTable(\App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName()));
        $this->getTable()->setRenderer(\App\Config::getInstance()->createTableRenderer($this->getTable()));

        $editUrl = null;
        if(!$this->getConfig()->isSubjectUrl())
            $editUrl = \Uni\Uri::createHomeUrl('/ruleEdit.html');

        if (!$this->getConfig()->isSubjectUrl()) {
            $this->getTable()->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'))->setIconOnly();
        }
        $this->getTable()->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl($editUrl);
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('description'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Boolean('static'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('label'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('assert'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('max'));

        $this->getTable()->appendCell(new \Tk\Table\Cell\Date('created'));

        if ($this->getConfig()->isSubjectUrl()) {
            $this->getTable()->appendCell(new \Tk\Table\Cell\Checkbox('activeCb'))->setLabel('Active')->setUseValue(true)
                ->addOnPropertyValue(function ($cell, $obj, $value) {
                    /** @var $cell \Tk\Table\Cell\Checkbox */
                    /** @var $obj \Rs\Db\Rule */
                    $subjectId = \App\Config::getInstance()->getSubjectId();
                    $cell->setAttr('data-url', \Tk\Uri::create()->set('active'));
                    $cell->setAttr('data-rule-id', $obj->getId());
                    $cell->setAttr('data-subject-id', $subjectId);
                    $cell->addCss('tk-ajax-checkbox');
                    return $obj->isActive($subjectId);
                });
        }
        // Filters
        $this->getTable()->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->getTable()->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->getTable()->appendAction(\Tk\Table\Action\Csv::create());
        if(!$this->getConfig()->isSubjectUrl()) {
            $this->getTable()->appendAction(\Tk\Table\Action\Delete::create());
        }

        $this->getTable()->setList($this->getList());
    }

    /**
     * @param Request $request
     * @return \Tk\Response
     */
    public function doUpdate(Request $request)
    {
        if ((int)$request->get('ruleId') && (int)$request->get('subjectId') && $request->has('value'))
            \Rs\Db\RuleMap::create()->setActive((int)$request->get('ruleId'), (int)$request->get('subjectId'), $request->get('value') == 'true');
        return \Tk\ResponseJson::createJson(array('status' => 'ok'));
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->getTable()->getFilterValues();
        $filter['courseId'] = $this->getCourseId();
        return \Rs\Db\RuleMap::create()->findFiltered($filter, $this->getTable()->getTool('a.order_by'));
    }

    /**
     *
     */
    public function initActionPanel()
    {
        if (!$this->getConfig()->isSubjectUrl())
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('New Rule',
                \Uni\Uri::createHomeUrl('/ruleEdit.html')->set('courseId', $this->getCourseId()), 'fa fa-check fa-add-action'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->getRenderer()->show());

        if ($this->getConfig()->isSubjectUrl()) {
            $template->setVisible('subjectUrl');
            $template->setAttr('courseEdit', 'href', \Uni\Uri::createHomeUrl('/courseEdit.html')->set('courseId', $this->getCourseId()));
            $js = <<<JS
jQuery(function ($) {
  
  // Fire off the href url as an ajax call, handy for updates and deletes
  $('.tk-ajax-checkbox input[type="checkbox"]').on('change', function (e) {
    var cb = $(this);
    var data = $(this).parent().data();
    var url = data.url;
    cb.attr('disabled', 'disabled');
    
    var params = $.extend({action: 'update', value: cb.prop('checked')}, data);
    $.post(url, params).done(function (data) {
      cb.removeAttr('disabled');
    });
    
    return false;
  });
    
});
JS;
            $template->appendJs($js);
        }

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
<div class="tk-panel" data-panel-title="Rule Manager" data-panel-icon="fa fa-check" var="panel">     
  <p choice="subjectUrl">
    NOTE: You can only activate and deactivate rules from here. 
    Use the <a herf="#" var="courseEdit">Course Edit page</a> to edit the rule records.
  </p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

