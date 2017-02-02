<?php
namespace frontend\controllers;

use Yii;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\Commands;
use common\models\ProjectModules;
use common\models\Objects;
use common\models\Properties;
use common\models\Device;
use common\models\DeviceType;
use common\models\SchedulerJobTemplate;


chdir(GIRAR_BASE_DIR);


/**
 * Site controller
 */
class SchedulerController extends Controller
{

  protected $lang;
  protected $task_types = [
    'Change' => SchedulerJobTemplate::TASK_ACTION_CHANGE,
    'Revert' => SchedulerJobTemplate::TASK_ACTION_REVERT,
    ];


  public function __construct($id, $module, $config = [])
  {
      $this->lang = Yii::$app->params['lang'];
      parent::__construct($id, $module, $config);
  }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }



    public function actionAdmin($title = null, $url = null, $save_qry = null)
    {
 
      $out = array_merge([], 
        [
         'lang' => Yii::$app->params['lang'],
         'csrfToken' => Yii::$app->request->getCsrfToken(),
         'action_name' => strtolower(Yii::$app->controller->action->id),
        ]
        );
      return $this->renderPartial('admin.twig', $out);


    }

    public function actionDelete($task_id)
    {
      $rec = SchedulerJobTemplate::find()->where(['scheduler_job_template_id' => $task_id])->one();
      $rec->delete();
    }


    public function actionEdit($task_id = null)
    {
      $methods = [];
      $rec = ($task_id) ? SchedulerJobTemplate::find()->where(['scheduler_job_template_id' => $task_id])->one() : new SchedulerJobTemplate();

      foreach (Objects::find()->all() as $obj)
      {
        $props = $obj->getProperties();
        $methods[$obj->title] = array_map(function ($x) use(&$obj) { return [
          'title' => $x['title'],
          'prop_id' => $x['id'],
          'obj_id' => $obj->id,  
          ];}, 
          $props);
      }
      
      //var_dump($methods);die();
     
      $post = \Yii::$app->request->post();
      if (!empty($post))
      {
        if ($post['scheduler_job_template_id'])
        {
          $rec = SchedulerJobTemplate::find()->where(['scheduler_job_template_id' => $post['scheduler_job_template_id']])->one();
        }
        else
        {
          $rec = new SchedulerJobTemplate();
          $cron = \Cron\CronExpression::factory($post['start_at']);
          $rec->next_run_time = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        }

        $rec->name = $post['name'];
        $rec->description = $post['description'];
        //$rec->hint = $post['hint'];
        $rec->start_at = $post['start_at'];
        $work_time = strtotime($post['work_time'], 0);
        if ($work_time === false)
        {
            throw new \InvalidArgumentException();
        }
        $rec->work_time = $work_time;
        $rec->value_at_start = $post['value_at_start'];
        $rec->value_at_end = $post['value_at_end'];
        $rec->type_action = $post['type_action'];
        $rec->is_active = (isset($post['is_active']) && $post['is_active'] == 1) ? 1 : 0;
        list($rec->object_id, $rec->property_id) = explode("_", $post['method'], 2);
        $rec->save();

        $controller = Yii::$app->controller;
        $params = array_merge(["{$controller->id}/edit", "task_id" => $rec->scheduler_job_template_id]);
        $this->redirect(\Yii::$app->urlManager->createUrl($params));
      }

      $out = array_merge([], 
        [
         'task' => $rec->toArray(),
         'methods' => $methods,
         'action_name' => strtolower(Yii::$app->controller->action->id),
         'lang' => Yii::$app->params['lang'],
         'csrfToken' => Yii::$app->request->getCsrfToken(),
         'task_types' => $this->task_types,
        ]
        );
      return $this->renderPartial('edit.twig', $out);


    }


    public function actionGetTasks($order = 'asc', $offset = null, $limit = null)
    {
        $rec = SchedulerJobTemplate::find()->offset($offset)->limit($limit)->orderBy(['scheduler_job_template_id' => ($order == 'asc') ? SORT_ASC : SORT_DESC])->all();
        $r = array_map(function ($x) { return $x->toArray(); }, $rec);
        return json_encode($r);
    }


    public function actionValidate()
    {
        $param = array_keys($_GET)[0];
        switch ($param) {
          case 'work_time':
            $work_time = strtotime($_GET[$param], 0);
            if ($work_time === false)
              Yii::$app->response->statusCode = 500; 
            break;

          case 'start_at':
            try
            {
              $cron = \Cron\CronExpression::factory($_GET[$param]);
            }
            catch (\InvalidArgumentException $e)
            {
              Yii::$app->response->statusCode = 500;
            }
            break;

          default:
            Yii::$app->response->statusCode = 500; 
            break;
        }

        
    }


    /**********************/

    public function actionGetDates()
    {
        $time = $_POST['time'];
        $dates = self::getRunDates($time);
        if (empty($dates)) {
            echo 'Invalid expression';
            return;
        }
        echo '<ul>';
        foreach ($dates as $d) {
            /**
             * @var \DateTime $d
             */
            echo '<li>' . $d->format('Y-m-d H:i:s') . '</li>';
        }
        echo '</ul>';
    }


    public static function getRunDates($time, $count = 10)
    {
        try {
            $cron = \Cron\CronExpression::factory($time);
            $dates = $cron->getMultipleRunDates($count);
        } catch (\Exception $e) {
            return array();
        }
        return $dates;
    }

}
