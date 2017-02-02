<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\web\IdentityInterface;


use common\models\Objects;
use common\models\Properties;

class SchedulerJobTemplate extends ActiveRecord
{

	const TASK_ACTION_CHANGE = 1;
	const TASK_ACTION_REVERT = 2;

	public $_obj_property;

    public static function tableName()
    {
        return 'plugins.scheduler_job_template';
    }

    public function get_obj_property ()
	{
	    return $this->_obj_property;
	}

	public function set_obj_property ($value)
	{
	    $this->_obj_property = $value;
	}

	public function fields()
	{
	    $fields = parent::fields();
	    $fields['_obj_property'] = function () {
	        return $this->object_id . '_' . $this->property_id;
		};

		$t1 =  Objects::find()->where(['id' => $this->object_id])->one();
		$t2 = Properties::find()->where(['id' => $this->property_id])->one();

		$t1 = ($t1) ? $t1->title : "";
		$t2 = ($t2) ? $t2->title : "";

		$fields['_obj_name'] = function () use($t1, $t2){
	        return $t1;
		};

		$fields['_prop_name'] = function () use($t1, $t2){
	        return $t2;
		};

		$fields['_full_prop_name'] = function () use($t1, $t2){
	        return "$t1#$t2" ;
		};

		$fields['work_time_str'] = function () {
	        return $this->secondsToTime($this->work_time);
		};

	    return $fields;
	}


	protected function secondsToTime($seconds) {
      if (!is_numeric($seconds))
      	$seconds = 0;
      $dtF = new \DateTime('0 seconds');
      $dtT = new \DateTime("$seconds seconds");
      return $dtF->diff($dtT)->format('%a days %h hours %i minutes %s seconds');
    }

}