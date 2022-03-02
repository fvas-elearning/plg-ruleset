<?php
namespace Rs\Controller;

use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleEdit extends \App\Controller\AdminEditIface
{

    /**
     * @var \Rs\Db\Rule
     */
    protected $rule = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Rule Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->rule = \Rs\Db\RuleMap::create()->find($request->get('ruleId'));
        if (!$this->rule) {
            $this->rule = new \Rs\Db\Rule();
            $this->rule->setCourseId($request->get('courseId'));
        }


        $this->buildForm();
        if ($this->rule->getId() && $this->getConfig()->isSubjectUrl()) {
            $this->getForm()->load(array(
                'active' => $this->rule->isActive($this->getSubjectId())
            ));
        }
        $this->getForm()->load(\Rs\Db\RuleMap::create()->unmapForm($this->rule));
        $this->getForm()->execute($request);

    }

    /**
     *
     * @throws \Exception
     */
    protected function buildForm() 
    {
        $this->setForm(\App\Config::getInstance()->createForm('ruleEdit'));
        $this->getForm()->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->getForm()->appendField(new Field\Input('name'));
        $this->getForm()->appendField(new Field\Input('label'));
            $this->getForm()->appendField(Field\Checkbox::create('static')->setCheckboxLabel('Static rules are not selectable in the company and placement records'));
        $this->getForm()->appendField(new \App\Form\Field\MinMax('min', 'max'));
        $this->getForm()->appendField(new Field\Input('description'));
        if ($this->getConfig()->isSubjectUrl()) {
            $this->getForm()->appendField(new Field\Checkbox('active'))->setValue(true);
        }

        $list = \Rs\Db\Rule::getAssertList($this->rule->assert);
        $this->getForm()->appendField(new Field\Select('assert', $list))->prependOption('-- None --', '');
        $this->getForm()->appendField(new Field\Textarea('script'))->addCss('code')->setAttr('data-mode', 'text/x-php')
        ->setNotes('NOTE: This will be deprecated in the future in favor of the Assert field above.');

        $this->getForm()->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Rs\Db\RuleMap::create()->mapForm($form->getValues(), $this->rule);

        $form->addFieldErrors($this->rule->validate());

        if ($form->hasErrors()) {
            return;
        }

        $this->rule->save();

        if ($form->getField('active') && $this->getConfig()->isSubjectUrl()) {
            \Rs\Db\RuleMap::create()->setActive($this->rule->getId(), $this->getSubjectId(), $form->getFieldValue('active'));
        }

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('ruleId', $this->rule->getId()));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the panel
        $template->appendTemplate('panel', $this->getForm()->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-title="Rule Edit" data-panel-icon="fa fa-check" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}