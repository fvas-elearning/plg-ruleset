<?php
namespace Rs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CompanyViewHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Company\View
     */
    protected $controller = null;



    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Company\View) {
            $plugin = \Rs\Plugin::getInstance();
            $pluginData = \Tk\Db\Data::create($plugin->getName() . '.subject.course', $controller->getCourseId());
            if ($pluginData->get('plugin.active')) {
                $this->controller = $controller;
            }
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Dom\Exception
     * @throws \Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;

        $companyRules = \Rs\Calculator::findCompanyRuleList($this->controller->getCompany(), $this->controller->getSubject());
        $template = $this->controller->getTemplate();

        $repeat = $template->getRepeat('infoData');
        $repeat->insertText('label', 'Default Assessment Credit:');
        $html = '';
        foreach ($companyRules as $rule) {
            $html .= sprintf('<li>%s</li>', $rule->name) . "\n";
        }
        $html = rtrim($html , "\n");
        if ($html) $html = sprintf('<ul>%s</ul>', $html);
        $repeat->insertHtml('data', $html);
        $repeat->addCss('data', 'force-block');
        $repeat->appendRepeat();

    }

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }

    
}