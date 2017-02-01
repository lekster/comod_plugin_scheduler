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

class SchedulerJobs extends ActiveRecord
{

	public $_obj_property;

    public static function tableName()
    {
        return 'plugins.scheduler_jobs';
    }
}