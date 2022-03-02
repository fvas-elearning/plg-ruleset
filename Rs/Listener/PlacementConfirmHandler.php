<?php
namespace Rs\Listener;

use App\Controller\Student\Placement\Create;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementConfirmHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Placement\Edit
     */
    protected $controller = null;

    /**
     * @var null|\App\Db\Placement
     */
    protected $placement = null;


    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Student\Placement\Confirm) {
            // TODO: Why did I repeat this fpor both, it is in the create handler
            //       Make sure there are no weird bugs caused by changing this
        //if ($controller instanceof \App\Controller\Student\Placement\Create || $controller instanceof \App\Controller\Student\Placement\Confirm) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            if ($form->getField('submitForApproval'))
                $form->addEventCallback('submitForApproval', array($this, 'doSubmit'));

        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $selectedRules = \App\Config::getInstance()->getSession()->get(Create::SID.'_rules', []);
        if (!count($selectedRules))
            $selectedRules = \Rs\Calculator::findPlacementRuleList($this->placement, false)->toArray('id');
        if (!count($selectedRules))
            $selectedRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject(), false)->toArray('id');

        if($this->placement->getId() && count($selectedRules) && !$form->hasErrors()) {
            \Rs\Db\RuleMap::create()->removeFromPlacement($this->placement);
            foreach ($selectedRules as $ruleId) {
                \Rs\Db\RuleMap::create()->addPlacement($ruleId, $this->placement->getVolatileId());
            }
            \App\Config::getInstance()->getSession()->remove(Create::SID.'_rules');
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0)
        );
    }
    
}