<?php
namespace Rs\Controller;

use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleEdit extends \App\Controller\AdminEditIface
{

    /**
     * @var \Rs\Db\Rule
     */
    protected $rule = null;

    /**
     * @var \App\Db\Profile
     */
    protected $profile = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Placement Rule Edit');
    }

    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->profile = \App\Factory::getProfile();

        if (!$this->rule) {
            $this->rule = new \Rs\Db\Rule();
            $this->rule->profileId = $this->profile->getId();
            if ($request->get('ruleId')) {
                $this->rule = \Rs\Db\RuleMap::create()->find($request->get('ruleId'));
            }
        }

        $this->buildForm();

        $this->form->load(\Rs\Db\RuleMap::create()->unmapForm($this->rule));
        $this->form->execute($request);

    }

    /**
     *
     */
    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('ruleEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Input('name'));
        $this->form->addField(new Field\Input('label'));
        $this->form->addField(new \App\Form\Field\MinMax('min', 'max'));
//        $this->form->addField(new Field\Input('min'));
//        $this->form->addField(new Field\Input('max'));
        $this->form->addField(new Field\Input('description'));
        $this->form->addField(new Field\Textarea('script'))->addCss('tkCode');

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', \App\Factory::getCrumbs()->getBackUrl()));
    }

    /**
     * @param \Tk\Form $form
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \Rs\Db\RuleMap::create()->mapForm($form->getValues(), $this->rule);

        $form->addFieldErrors($this->rule->validate());

        if ($form->hasErrors()) {
            return;
        }

        $this->rule->save();

        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('ruleId', $this->rule->getId())->redirect();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getParam('renderer')->show()->getTemplate());

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
<div>
    
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-folder-open-o"></i> <span var="panel-title">Company Category Edit</span></h4>
    </div>
    <div class="panel-body">
      <div var="form"></div>
    </div>
  </div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}