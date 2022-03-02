<?php
namespace Rs;

use Rs\Db\Rule;


/**
 * Calculate rules and max/total/min
 *
 */
class Calculator extends \Tk\ObjectUtil
{

    /**
     * @var \Uni\Db\UserIface
     */
    protected $user = null;

    /**
     * @var \Uni\Db\SubjectIface
     */
    protected $subject = null;

    /**
     * @var \Tk\Db\Map\ArrayObject
     */
    protected $placementList = null;

    /**
     * @var \Tk\Db\Map\ArrayObject
     */
    protected $ruleList = null;

    /**
     * @var array
     */
    protected $minTargets = null;

    /**
     * @var array
     */
    protected $maxTargets = null;

    /**
     * @var array
     */
    protected $totals = array();

    /**
     * @var array
     */
    protected $ruleTotals = array();


    /**
     * @param \Uni\Db\SubjectIface $subject
     * @param \Uni\Db\UserIface $user
     * @throws \Exception
     */
    protected function __construct($subject, $user)
    {
        $this->subject = $subject;
        $this->user = $user;
        $this->placementList = $this->findPlacementList($subject, $user);
        $this->ruleList = self::findSubjectRuleList($subject);
    }

    /**
     * calculate and return an instance of this object holding the calculated data
     *
     * @param \Uni\Db\SubjectIface $subject
     * @param \Uni\Db\UserIface $user
     * @return Calculator
     * @throws \Exception
     */
    public static function create($subject, $user)
    {
        $calc = new self($subject, $user);
        $calc->init();
        return $calc;
    }

    /**
     * Calculate and return an instance of this object holding the calculated data
     *
     * @param \Tk\Db\Map\ArrayObject $placementList
     * @return Calculator
     * @throws \Exception
     */
    public static function createFromPlacementList($placementList)
    {
        $calc = null;
        /** @var \App\Db\Placement $placement */
        $placement = $placementList->rewind()->current();
        if ($placement) {
            $calc = new self($placement->getSubject(), $placement->getUser());
            $calc->init();
        }
        return $calc;
    }

    /**
     * init()
     * @todo: This all may need to be refactored
     * @throws \Exception
     */
    private function init()
    {
        $totals = array();
        $termTot = 0;

        /* @var $placement \App\Db\Placement */
        foreach ($this->placementList as $placement) {
            $placeRules = self::findPlacementRuleList($placement);
            $units = $placement->units;
            if (!$placement->getPlacementType() || !$placement->getPlacementType()->gradable) {
                $units = 0;
            }

            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                if (!isset($totals[$rule->getLabel()])) {
                    $totals[$rule->getLabel()]['total'] = 0;
                    $totals[$rule->getLabel()]['completed'] = 0;
                    $totals[$rule->getLabel()]['approved'] = 0;
                    $totals[$rule->getLabel()]['evaluating'] = 0;
                    $totals[$rule->getLabel()]['pending'] = 0;
                }
                if (self::hasRule($rule, $placeRules)) {
                    $totals[$rule->getLabel()]['total'] += $units;
                    if ($placement->status == \App\Db\Placement::STATUS_COMPLETED) {
                        $totals[$rule->getLabel()]['completed'] += $units;
                    } else if ($placement->status == \App\Db\Placement::STATUS_PENDING) {
                        $totals[$rule->getLabel()]['pending'] += $units;
                    } else if ($placement->status == \App\Db\Placement::STATUS_APPROVED) {
                        $totals[$rule->getLabel()]['approved'] += $units;
                    } else if ($placement->status != \App\Db\Placement::STATUS_CANCELLED) {
                        $totals[$rule->getLabel()]['evaluating'] += $units;
                    }
                }
            }

            if (!isset($totals['total'])) {
                $totals['total'] = 0;
                $totals['completed'] = 0;
                $totals['pending'] = 0;
                $totals['approved'] = 0;
                $totals['evaluating'] = 0;
            }
            $totals['total'] += $units;
            if ($placement->status == \App\Db\Placement::STATUS_COMPLETED) {
                $totals['completed'] += $units;
            } else if ($placement->status == \App\Db\Placement::STATUS_PENDING) {
                $totals['pending'] += $units;
            } else if ($placement->status == \App\Db\Placement::STATUS_APPROVED) {
                $totals['approved'] += $units;
            } else if ($placement->status != \App\Db\Placement::STATUS_CANCELLED) {
                $totals['evaluating'] += $units;
            }
            $termTot += $units;
        }

        $this->totals = $totals;

        if (count($totals)) {
            /* @var $rule \Rs\Db\Rule */
            foreach ($this->ruleList as $rule) {
                $this->ruleTotals[$rule->getLabel()] = array();
                $this->ruleTotals[$rule->getLabel()]['ruleTotal'] = $rule->getMaxTarget() ? $rule->getMaxTarget() : $rule->getMinTarget();
                $this->ruleTotals[$rule->getLabel()]['total'] = $totals[$rule->getLabel()]['total'];
                $this->ruleTotals[$rule->getLabel()]['pending'] = $totals[$rule->getLabel()]['pending'];
                $this->ruleTotals[$rule->getLabel()]['evaluating'] = $totals[$rule->getLabel()]['evaluating'];
                $this->ruleTotals[$rule->getLabel()]['completed'] = $totals[$rule->getLabel()]['completed'];
                $this->ruleTotals[$rule->getLabel()]['validCompleted'] = $rule->isTotalValid($totals[$rule->getLabel()]['completed']);
                $this->ruleTotals[$rule->getLabel()]['validCompletedMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['completed']);
                $this->ruleTotals[$rule->getLabel()]['validTotal'] = $rule->isTotalValid($totals[$rule->getLabel()]['total']);
                $this->ruleTotals[$rule->getLabel()]['validMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['total']);
                $this->ruleTotals[$rule->getLabel()]['assessmentRule'] = $rule;
            }
        }

        $this->ruleTotals['total'] = array();
        $this->ruleTotals['total']['ruleTotal'] = $this->subject->getMaxUnitsTotal() ? $this->subject->getMaxUnitsTotal() : $this->subject->getMinUnitsTotal();
        $this->ruleTotals['total']['total'] = $totals['total'];
        $this->ruleTotals['total']['pending'] = $totals['pending'];
        $this->ruleTotals['total']['evaluating'] = $totals['evaluating'];
        $this->ruleTotals['total']['completed'] = $totals['completed'];
        $this->ruleTotals['total']['validCompleted'] = Rule::validateUnits($totals['completed'], $this->subject->getMinUnitsTotal(), $this->subject->getMaxUnitsTotal());
        $this->ruleTotals['total']['validCompletedMsg'] = Rule::getValidateMessage($totals['completed'], $this->subject->getMinUnitsTotal(), $this->subject->getMaxUnitsTotal());
        $this->ruleTotals['total']['validTotal'] = Rule::validateUnits($totals['total'], $this->subject->getMinUnitsTotal(), $this->subject->getMaxUnitsTotal());
        $this->ruleTotals['total']['validMsg'] = Rule::getValidateMessage($totals['total'], $this->subject->getMinUnitsTotal(), $this->subject->getMaxUnitsTotal());
        $this->ruleTotals['total']['assessmentRule'] = null;

        //vd($this->ruleTotals);

    }


    /**
     * Return an array with the term min target values
     *
     * @param bool $total
     * @return array
     * @throws \Exception
     */
    public function getMinTargets($total = true)
    {
        if (!$this->minTargets) {
            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                $this->minTargets[$rule->getLabel()] = $rule->min;
            }
            if ($total)
                $this->minTargets['total'] = $this->subject->getMinUnitsTotal();
        }
        return $this->minTargets;
    }

    /**
     * Return an array with the term max target values
     *
     * @param bool $total
     * @return array
     */
    public function getMaxTargets($total = true)
    {
        if (!$this->maxTargets) {
            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                $this->maxTargets[$rule->getLabel()] = $rule->max;
            }
            if ($total)
                $this->maxTargets['total'] = $this->subject->getMinUnitsTotal();
        }
        return $this->maxTargets;
   }

    /**
     * Return an array with the placement totals for each rule set
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->totals;
    }

    /**
     * Return total placement units for term
     *
     * @return int
     */
    public function getTermTotal()
    {
        return $this->totals['total'];
    }

    /**
     * Get all rule validation information
     * calculated from the list
     *
     * @return array
     */
    public function getRuleTotals()
    {
        return $this->ruleTotals;
    }

    /**
     * @return \Uni\Db\UserIface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return \Uni\Db\SubjectIface
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return \Tk\Db\Map\ArrayObject
     */
    public function getPlacementList()
    {
        return $this->placementList;
    }

    /**
     * @return \Tk\Db\Map\ArrayObject
     */
    public function getRuleList()
    {
        return $this->ruleList;
    }

    /**
     * @param Rule $rule
     * @param \Tk\Db\Map\ArrayObject $ruleList
     * @return int
     */
    public static function hasRule($rule, $ruleList)
    {
        /** @var Rule $r */
        foreach ($ruleList as $r) {
            if ($rule->getId() == $r->getId()) return true;
        }
        return false;
    }

    /**
     * @param \App\Db\Placement $placement
     * @param bool|null $static
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public static function findPlacementRuleList($placement, $static = null)
    {
        $list = null;
        if ($placement->getId()) {
            $filter = [
                'placementId' => $placement->getVolatileId(),
                'subjectId' => $placement->getSubjectId()
            ];
            if (is_bool($static)) {
                $filter['static'] = $static;
            }
            $list = \Rs\Db\RuleMap::create()->findFiltered($filter, \Tk\Db\Tool::create('order_by'));
        } else {    // Get default rules based on the company and subject object
            $list = self::findCompanyRuleList($placement->getCompany(), $placement->getSubject(), $static);
        }
        return $list;
    }

    /**
     * @param \App\Db\Placement $placement
     * @return Rule
     * @throws \Exception
     */
    public static function findDefaultPlacementRule($placement)
    {
        $default = null;
        $list = self::findCompanyRuleList($placement->getCompany(), $placement->getSubject(), false);
        foreach ($list as $rule) {
            if (strtolower($rule->getLabel()) == strtolower($placement->getCompany()->getCategoryClass())) {
                $default = $rule;
                break;
            }
        }
        return $default;
    }

    /**
     * Buffer to store company rule list
     * @var array
     */
    public static $CO_RULE_BUFF = [];

    /**
     * @param \App\Db\Company $company
     * @param \Uni\Db\Subject|\Uni\Db\SubjectIface $subject
     * @param bool|null $static
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public static function findCompanyRuleList($company, $subject, $static = null)
    {
        // TODO: keep an eye on this and check it does not consume more mem than its worth
        $key = md5($company->getId().$subject->getId().$static);
        if (!empty(self::$CO_RULE_BUFF[$key])) {
            return self::$CO_RULE_BUFF[$key];
        }

        $filter = [
            'courseId' => $subject->getCourseId(),
            'subjectId' => $subject->getId()
        ];
        if (is_bool($static)) {
            $filter['static'] = $static;
        }

        $list = \Rs\Db\RuleMap::create()->findFiltered($filter, \Tk\Db\Tool::create('order_by'));
        $valid = array();
        /** @var \Rs\Db\Rule $rule */
        foreach ($list as $rule) {
            if ($rule->evaluate($company)) {
                $valid[] = $rule;
            }
        }
        $a = new \Tk\Db\Map\ArrayObject($valid);
        if (count(self::$CO_RULE_BUFF) > 100) $CO_RULE_BUFF = [];     // just to keep mem usage down
        self::$CO_RULE_BUFF[$key] = $a;
        return $a;
    }

    /**
     * @param \Uni\Db\SubjectIface $subject
     * @param bool|null $static
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public static function findSubjectRuleList($subject, $static = null)
    {
        $filter = [
            'courseId' => $subject->getCourseId(),
            'subjectId' => $subject->getId()
        ];
        if (is_bool($static)) {
            $filter['static'] = $static;
        }
        return \Rs\Db\RuleMap::create()->findFiltered($filter, \Tk\Db\Tool::create('order_by'));
    }

    /**
     * @param \Uni\Db\SubjectIface $subject
     * @param \Uni\Db\UserIface $user
     * @return \App\Db\Placement[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public static function findPlacementList($subject, $user)
    {
        return \App\Db\PlacementMap::create()->findFiltered(array(
            'userId' => $user->getId(),
            'subjectId' => $subject->getId(),
            'status' => array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_PENDING,
                \App\Db\Placement::STATUS_EVALUATING, \App\Db\Placement::STATUS_COMPLETED)
        ));
    }

}
