<?php

namespace console\controllers;

use yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use common\models\DeviceType;
use common\models\Device;
use common\models\Properties;
use common\models\PValues;
use common\models\Objects;
use src\helpers\SysHelper;
use common\models\SchedulerJobTemplate;
use common\models\SchedulerJobs;
use common\models\Tasks;

chdir(GIRAR_BASE_DIR);
date_default_timezone_set('Europe/Moscow');
set_time_limit(0);




class SchedulerController extends Controller
{
	public function actionRun()
	{
		$tasks = SchedulerJobTemplate::find()->where(["<=", 'next_run_time', "now()"])->andWhere(["is_active" => true]) ->all();
		foreach ($tasks as $task)
		{
			$obj = Objects::find()->where(['id' => $task->object_id])->one();
			$prop = Properties::find()->where(['id' => $task->property_id])->one();


			$connection = Yii::$app->getDb();
			$transaction = $connection->beginTransaction();

			try
			{

				switch ($task->type_action) {
					case SchedulerJobTemplate::TASK_ACTION_CHANGE:
						 $obj->setValueByPropertyId($task->property_id, $task->value_at_start);

						  $t = new Tasks();
				          $t->type = Tasks::TYPE_SET_OBJECT_VAL;
				          $t->params = json_encode(['name' => $obj->title, 'property_name' => $prop->title, 'value' => $task->value_at_end]);
				          $t->date_to_run = date('Y-m-d H:i:s', time() + $task->work_time);
				          $t->groups = md5($obj->title . "|" . $prop->title);
				          $t->save();

				          $sj = new SchedulerJobs();
				          $sj->scheduler_job_template_id = $task->scheduler_job_template_id;
				          $sj->task_end_id = $t->task_id;
				          $sj->save();

				          $cron = \Cron\CronExpression::factory($task->start_at);
				          $task->next_run_time = $cron->getNextRunDate()->format('Y-m-d H:i:s');
				          $task->save();

					break;
					
					case SchedulerJobTemplate::TASK_ACTION_REVERT:
						$cron = \Cron\CronExpression::factory($task->start_at);
						$task->next_run_time = $cron->getNextRunDate()->format('Y-m-d H:i:s');
						$task->save();
						
					break;

					default:
						# code... UNKNOWN TYPE!
						break;
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollback(); 
			}
		}	


		// Works with predefined scheduling definitions
          
          //echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');

          /*
          $cron = \Cron\CronExpression::factory('3-59/15 2,6-12 15 1 2-5');
          echo $cron->getNextRunDate()->format('Y-m-d H:i:s');

          // Calculate a run date two iterations into the future
          $cron = \Cron\CronExpression::factory('@daily');
          echo $cron->getNextRunDate(null, 2)->format('Y-m-d H:i:s');

          // Calculate a run date relative to a specific time
          $cron = \Cron\CronExpression::factory('@monthly');
          echo $cron->getNextRunDate('2010-01-12 00:00:00')->format('Y-m-d H:i:s');
          */
	}


}



/*
$connection = Yii::$app->getDb();
$transaction = $connection->beginTransaction();

try
{


	$transaction->commit();
}
catch (\Exception $e)
{
	$transaction->rollback(); 
}

*/