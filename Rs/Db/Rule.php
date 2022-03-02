<?php
namespace Rs\Db;


use Bs\Db\Traits\CreatedTrait;
use Bs\Db\Traits\OrderByTrait;
use Bs\Db\Traits\TimestampTrait;
use Rs\Plugin;
use Uni\Db\Traits\CourseTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Rule extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
    use CourseTrait;
    use TimestampTrait;
    use OrderByTrait;

    const VALID_NULL = 128;

    const VALID_BELOW = -1;
    const VALID_OK = 0;
    const VALID_OUT = 1;


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $uid = 0;

    /**
     * @var int
     */
    public $courseId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var float
     */
    public $min = 0.0;

    /**
     * @var float
     */
    public $max = 0.0;

    /**
     * if false then this rule can be selectable in the placement edit pages
     *
     * @var boolean
     */
    public $static = true;

    /**
     * @var string
     */
    public $assert = '';

    /**
     * @var string
     */
    public $script = '';

    /**
     * @var int
     */
    public $orderBy = 0;

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;



    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     * eval() and return the result of the script
     *
     * NOTE: I have removed subject and supervisor, this should still work as expected
     *
     * @param \App\Db\Company $company
     * @return boolean
     */
    public function evaluate($company)
    {
        if ($this->getScript()) {
            return eval($this->getScript());
        }
        return false;
    }


    /**
     * Use this to get the rule label
     * as it returns the name if no label
     * available.
     *
     * @return string
     */
    public function getLabel()
    {
        if (!$this->label) return $this->getName();
        return $this->label;
    }

    /**
     * return the target units required for this rule.
     *
     * @return int
     */
    public function getMinTarget()
    {
        if (!$this->getMin() && $this->getMax())
            return $this->getMax();
        return $this->getMin();
    }

    /**
     * return the target units required for this rule.
     *
     * @return int
     */
    public function getMaxTarget()
    {
        if (!$this->getMax() && $this->getMin())
            return $this->getMin();
        return $this->getMax();
    }

    /**
     * validate total for a student
     * Returns:
     *   o -n When units supplied are less than required units
     *   o  0 When units are equal to total or there is no problem with units in their current value
     *   o  n When units are over the total or maximum amount
     *
     * @param int $units
     * @return int
     */
    final public function isTotalValid($units)
    {
        return self::validateUnits($units, $this->getMin(), $this->getMax());
    }

    /**
     * Get the worded string representing the current rule state.
     *
     * @param int $units
     * @return string
     */
    final public function getValidMessage($units)
    {
        return self::getValidateMessage($units, $this->getMin(), $this->getMax());
    }


    /**
     * @param null|string $current
     * @return array
     */
    public static function getAssertList($current = null)
    {
        $path = \App\Config::getInstance()->getSitePath() . Plugin::getInstance()->getPluginPath().'/Rs/Assert';
        $list = array();
        foreach (scandir($path) as $i => $file) {
            if ($file[0] == '.') continue;
            if (strpos($file, 'Iface') === 0) continue;
            $class = str_replace('.php', '', $file);
            $label = $class;
            if ($current && $current == '\\Rs\\Assert\\' . $class)
                $label .= ' (selected)';
            $list[$label] = '\\Rs\\Assert\\' . $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * validate total for a student
     * Returns:
     *   o -n When units supplied are less than required units
     *   o  0 When units are equal to total or there is no problem with units in their current value
     *   o  n When units are over the total or maximum amount
     *
     * @param int $units
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function validateUnits($units, $min = 0, $max = 0)
    {
        $units = (float)$units;
        $min = (float)$min;
        $max = (float)$max;

        if (!$min && !$max) {
            return self::VALID_NULL;
        }
        if (!$min) {
            if ($units < $max) {
                return self::VALID_BELOW;
            } else if ($units >= $max) {
                return self::VALID_OUT;
            }
        } else if (!$max) {
            if ($units < $min) {
                return self::VALID_BELOW;
            } else if ($units >= $min) {
                return self::VALID_OK;
            }
        //} else if ($units <= $min) {
        } else if ($units < $min) {
            return self::VALID_BELOW;
        } else if ($units > $max) {
            return self::VALID_OUT;
        }
        return self::VALID_OK;
    }

    /**
     * Get the worded string representing the current rule state.(($max == 0) ? $min : $max)
     *
     * @param int $units
     * @param int $min
     * @param int $max
     * @return string
     */
    static function getValidateMessage($units, $min = 0, $max = 0)
    {
        $res = self::validateUnits($units, $min, $max);

        switch ($res) {
            case self::VALID_BELOW:
                $m = (($min == 0) ? $max : $min);
                return sprintf('You are below the minimum required units of %d', $m );
            case self::VALID_OUT:
                return sprintf('You have exceeded the maximum required units of %d', $max);
            case self::VALID_OK:
                return sprintf('You have reached the required number of units of %d', $max);
        }
        return '';
    }

    /**
     * @param $subjectId
     * @return bool
     */
    public function isActive($subjectId)
    {
        if (!$this->getId()) return false;
        return RuleMap::create()->isActive($this->getId(), $subjectId);
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     * @return Rule
     */
    public function setUid(int $uid): Rule
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Rule
     */
    public function setName(string $name): Rule
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Rule
     */
    public function setDescription(string $description): Rule
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return float
     */
    public function getMin(): float
    {
        return $this->min;
    }

    /**
     * @param float $min
     * @return Rule
     */
    public function setMin(float $min): Rule
    {
        $this->min = $min;
        return $this;
    }

    /**
     * @return float
     */
    public function getMax(): float
    {
        return $this->max;
    }

    /**
     * @param float $max
     * @return Rule
     */
    public function setMax(float $max): Rule
    {
        $this->max = $max;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * @param bool $static
     * @return Rule
     */
    public function setStatic(bool $static): Rule
    {
        $this->static = $static;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssert(): string
    {
        return $this->assert;
    }

    /**
     * @param string $assert
     * @return Rule
     */
    public function setAssert(string $assert): Rule
    {
        $this->assert = $assert;
        return $this;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param string $script
     * @return Rule
     */
    public function setScript(string $script): Rule
    {
        $this->script = $script;
        return $this;
    }

    /**
     * @return array
     */
    public function validate()
    {
        $errors = array();
        $errors = $this->validateCourseId($errors);

        if (!$this->getName()) {
            $errors['name'] = 'Please enter a valid value';
        }
        if (!$this->getLabel()) {
            $errors['label'] = 'Please enter a valid value';
        }
        if (!$this->getDescription()) {
            $errors['description'] = 'Please enter a valid value';
        }
        if (!preg_match('/^[0-9]*(.[0-9]*)?$/', $this->getMin())) {
            $errors['min'] = 'Invalid Min. units Value.';
        }
        if (!preg_match('/^[0-9]*(.[0-9]*)?$/', $this->getMax())) {
            $errors['max'] = 'Invalid Max. units Value.';
        }
//        if (!$this->getMin() && !$this->getMax()) {
//            $errors['min'] = 'Min and/or Max Units must have a valid value.';
//        }
        if ($this->getMin() && $this->getMax() && ($this->getMin() > $this->getMax())) {
            $errors['max'] = 'Max Unit must be greater than Min Units';
        }
        if (!$this->getScript()) {
            $errors['script'] = 'A rule code is required to make the rule active.';
        }

        return $errors;
    }

}