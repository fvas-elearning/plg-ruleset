<?php
namespace Rs\Listener;

use Rs\Plugin;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onRequest($event)
    {
        $dispatcher = \App\Config::getInstance()->getEventDispatcher();
        $plugin = Plugin::getInstance();

//        $institution = \Uni\Config::getInstance()->getInstitution();
//        if($institution && $plugin->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init client plugin stuff: ' . $institution->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_INSTITUTION, $institution->getId()));
//        }

//        $subject = \Uni\Config::getInstance()->getSubject();
//        if ($subject && $plugin->isZonePluginEnabled(Plugin::ZONE_SUBJECT, $subject->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init subject plugin stuff: ' . $subject->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_SUBJECT, $subject->getId()));
//        }

        $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
        $dispatcher->addSubscriber(new \Rs\Listener\StatusMailHandler());

        if ($plugin->isZonePluginEnabled(Plugin::ZONE_COURSE, \App\Config::getInstance()->getCourseId())) {
            //\Tk\Log::debug($plugin->getName() . ': Sample init subject profile plugin stuff: ' . $profile->name);
            $dispatcher->addSubscriber(new \Rs\Listener\CourseEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\SubjectEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\SubjectDashboardHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\PlacementManagerHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementViewHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementConfirmHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementValidateHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\AssessmentUnitsHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\CompanyViewHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\StaffSideMenuHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\CompanyEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementImportHandler());
        }

    }

    /**
     * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
     * @throws \Exception
     */
    public function onCommand(\Symfony\Component\Console\Event\ConsoleCommandEvent $event)
    {
        $config = \Uni\Config::getInstance();
        $dispatcher = $config->getEventDispatcher();

        $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
        $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());


    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onRequest', -10),
            \Symfony\Component\Console\ConsoleEvents::COMMAND  => array('onCommand', -10)
        );
    }
    
    
}