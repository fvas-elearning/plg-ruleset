<?php
namespace Rs\Listener;

use App\Event\SubjectEvent;
use Bs\DbEvents;
use Rs\Db\Rule;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;
use Uni\Db\Subject;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectEditHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Subject\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Subject\Edit) {
            if ($controller->getSubject()->getId() && $controller->getAuthUser()->isStaff() && $controller->getSubject()->getCourse()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->append(\Tk\Ui\Link::createBtn(\App\Db\Phrase::findValue('placement', $controller->getSubject()->getCourseId()) .
                    ' Rules', \Uni\Uri::createSubjectUrl('/ruleManager.html'), 'fa fa-check'));
            }
        }
    }


    /**
     * @param SubjectEvent $event
     * @throws \Exception
     */
    public function onSubjectPostClone(SubjectEvent $event)
    {
        $list = \Rs\Db\RuleMap::create()->findFiltered(['subjectId' => $event->getSubject()->getId()]);
        foreach ($list as $rule) {
            if ($rule->isActive($event->getSubject()->getId())) {
                \Rs\Db\RuleMap::create()->setActive($rule->getId(), $event->getClone()->getId(), true);
            }
        }
    }

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \App\AppEvents::SUBJECT_POST_CLONE => 'onSubjectPostClone'
        );
    }
    
}