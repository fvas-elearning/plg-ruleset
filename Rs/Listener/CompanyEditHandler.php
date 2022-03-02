<?php
namespace Rs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 *
 * @note: The auto approve enable checkbox is linked to the company and not the subject...
 */
class CompanyEditHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Company\Edit
     */
    protected $controller = null;



    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Company\Edit && $controller->getConfig()->getAuthUser()->isStaff()) {
            $plugin = \Rs\Plugin::getInstance();
            $pluginData = \Tk\Db\Data::create($plugin->getName() . '.subject.course', $controller->getCourseId());
            if ($pluginData->get('plugin.active')) {
                $this->controller = $controller;
            }
        }
    }

    /**
     * TODO: this autoApprove value whould be part of the company object and not in the Rs plugin
     *
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        if (!$this->controller) return;

        /** @var \Tk\Form $form */
        $form = $event->getForm();

        $form->appendField(new \Tk\Form\Field\Checkbox('autoApprove'), 'web')->setValue('autoApprove')->setTabGroup('Details')
            ->setCheckboxLabel('Placement requests with this company can be Auto-Approved.');

        if ($form->getField('update'))
            $form->getField('update')->appendCallback(array($this, 'doSubmit'));
        if ($form->getField('save'))
            $form->getField('save')->appendCallback(array($this, 'doSubmit'));

    }

    /**
     * TODO: this autoApprove value whould be part of the company object and not in the Rs plugin
     *
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormLoad(\Tk\Event\FormEvent $event)
    {
        if (!$this->controller) return;
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        $company = $this->controller->getCompany();
        $data = array('autoApprove' => $company->getData()->get('autoApprove', 'autoApprove'));
        $form->load($data);

    }

    /**
     * TODO: this autoApprove value would be part of the company object and not in the Rs plugin
     *
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit(\Tk\Form $form, \Tk\Form\Event\Iface $event)
    {
        // Load the object with data from the form using a helper object
        $this->controller->getCompany()->getData()->set('autoApprove', $form->getFieldValue('autoApprove'));
        $this->controller->getCompany()->getData()->save();
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;
        //$template = $this->controller->getTemplate();

    }



    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0),
            \Tk\Form\FormEvents::FORM_LOAD => array('onFormLoad', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }

    
}