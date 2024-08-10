<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\authclient\AuthAction;
use yii\authclient\ClientInterface;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;
use app\models\Question;
use app\models\Answer;
use yii\bootstrap\ActiveForm; // добавлено

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
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

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'auth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function onAuthSuccess(ClientInterface $client)
    {
        $attributes = $client->getUserAttributes();
        $email = $attributes['email'];

        $user = User::findOne(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->name = $attributes['name'];
            $user->email = $email;
            $user->photo = $attributes['picture'];
            $user->save();
        }

        Yii::$app->user->login($user);
    }

    public function actionIndex($skip = false)
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $currentQuestionId = Yii::$app->request->get('questionId');
        if (is_null($currentQuestionId)) {
            $currentQuestionId = Yii::$app->session->get('currentQuestionId');
        }

        if (!$currentQuestionId || $skip) {
            $question = Question::find()->where(['not in', 'id', Answer::find()->select('questionId')->where(['userId' => Yii::$app->user->id])])->orderBy(new \yii\db\Expression('RANDOM()'))->one();
            if (is_null($question)) {
                Yii::$app->session->set('currentQuestionId', null);
                return $this->render('win');
            }
        } else {
            $question = Question::findOne($currentQuestionId);
        }
        Yii::$app->session->set('currentQuestionId', $question->id);

        $answer = new Answer([
            'questionId' => $question->id,
            'userId' => Yii::$app->user->id,
            'score' => 0,
            'isCorrect' => 0,
        ]);

        if (Yii::$app->request->isAjax && $answer->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($answer);
        }

        if ($answer->load(Yii::$app->request->post()) && $answer->save()) {
            Yii::$app->session->set('currentQuestionId', null);
            return $this->redirect(['answer/view', 'id' => $answer->id]);
        }

        return $this->render('index', [
            'question' => $question,
            'answer' => $answer,
        ]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('contactFormSubmitted');

                return $this->refresh();
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}

