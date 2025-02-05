<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-05 11:45:49
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-07-03 15:25:21
 */

namespace api\controllers;

use api\models\DdApiAccessToken;
use api\models\DdMember;
use api\models\LoginForm;
use common\helpers\ErrorsHelper;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use common\models\DdMember as ModelsDdMember;
use common\models\DdWebsiteContact;
use common\models\forms\EdituserinfoForm;
use common\models\forms\PasswdForm;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;

class UserController extends AController
{
    public $modelClass = '';

    protected array $authOptional = ['login', 'signup', 'register', 'repassword', 'sendcode', 'forgetpass', 'refresh', 'smsconf', 'relations'];

    /**
     * 手机号注册
     * @return array
     * @throws ErrorException
     * @date 2023-07-03
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function actionSignup(): array
    {
        $DdMember = new DdMember();
        $data = Yii::$app->request->post();
        $username = $data['username'];
        $mobile = $data['mobile'];
        $password = $data['password'];


        $code = $data['code'];
        $sendcode = Yii::$app->cache->get($mobile . '_code');


        if (empty($username) && empty($mobile)) {
            return ResultHelper::json(401, '用户名或手机号不能为空');
        }

        if (empty($password)) {
            return ResultHelper::json(401, '密码不能为空');
        }

        if (empty($code)) {
            return ResultHelper::json(401, 'code不能为空');
        }

        if ($code != $sendcode) {
            return ResultHelper::json(401, '验证码错误');
        }

        $res = $DdMember->signup($username, $mobile, $password);

        return ResultHelper::json(200, '注册成功', (array)$res);
    }

    /**
     * 账户注册
     * @return array
     * @throws ErrorException
     * @date 2023-07-03
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function actionRegister(): array
    {
        $register_type = Yii::$app->request->input('register_type');

        if (!in_array($register_type,['mobile','username'])){
            return ResultHelper::json(401, 'register_type 不在可用范围');
        }

        $DdMember = new DdMember([
            'scenario' => $register_type
        ]);

        $data = Yii::$app->request->post();
        $mobile = $data['mobile']??'';
        $username = $data['username']??'';

        switch ($register_type){
            case 'mobile':
                if (empty($mobile)) {
                    return ResultHelper::json(401, '手机号不能为空');
                }

                break;
            case 'username':
                if (empty($username)) {
                    return ResultHelper::json(401, '用户名不能为空');
                }
                break;
        }

        $password = $data['password'];


        if (empty($password)) {
            return ResultHelper::json(401, '密码不能为空');
        }

        $res = $DdMember->signup($username, $mobile, $password);

        return ResultHelper::json(200, '注册成功', (array)$res);
    }


    /**
     * 账号登录
     * @return array
     */
    public function actionLogin(): array
    {
        $data = Yii::$app->request->input();
        $login_type = Yii::$app->request->input('login_type','username');

        if (!in_array($login_type,['mobile','username'])){
            return ResultHelper::json('401', ' login_type 值错误');
        }

        $model = new LoginForm([
            'scenario'=> $login_type
        ]);

        if ($model->load($data, '') && $userinfo = $model->login()) {
            return ResultHelper::json(200, '登录成功', (array)$userinfo);
        } else {
            $message = ErrorsHelper::getModelError($model);

            return ResultHelper::json('401', $message);
        }
    }

    /**
     * 重置密码
     * @return array
     * @throws ErrorException
     * @throws Exception
     */
    public function actionRepassword(): array
    {
        $model = new PasswdForm();
        if ($model->load(Yii::$app->request->post(), '')) {
            if (!$model->validate()) {
                $res = ErrorsHelper::getModelError($model);

                return ResultHelper::json(404, $res);
            }

            $data = Yii::$app->request->post();
            $mobile = $data['mobile'];
            $code = $data['code'];
            $sendcode = Yii::$app->cache->get($mobile . '_code');
            if ($code != $sendcode) {
                return ResultHelper::json(401, '验证码错误');
            }

            $member = DdMember::findByMobile($data['mobile']);

            $member->password_hash = Yii::$app->security->generatePasswordHash($model->newpassword);
            $member->generatePasswordResetToken();
            if ($member->save()) {
                Yii::$app->user->logout();
                $service = Yii::$app->service;
                $service->namespace = 'api';
                $userinfo = $service->AccessTokenService->getAccessToken($member, 1);
                // 清除验证码
                Yii::$app->cache->delete($mobile . '_code');

                return ResultHelper::json(200, '修改成功', $userinfo);
            }

            return ResultHelper::json(404, $this->analyErr($member->getFirstErrors()));
        } else {
            $res = ErrorsHelper::getModelError($model);

            return ResultHelper::json(404, $res);
        }
    }

    /**
     * 修改密码
     * @return array
     * @throws ErrorException
     * @throws Exception
     */
    public function actionUpRepassword(): array
    {
        $newpassword = Yii::$app->request->input('password');
        $member_id = Yii::$app->user->identity->member_id ?? 0;
        if (empty($member_id)) {
            return ResultHelper::json(401, 'member_id为空');
        }
        $member = DdMember::findIdentity($member_id);
        if (empty($member)) {
            return ResultHelper::json(401, '用户不存在');
        }
        $member->password_hash = Yii::$app->security->generatePasswordHash($newpassword);
        $member->generatePasswordResetToken();
        if ($member->save()) {
            Yii::$app->user->logout();
            $service = Yii::$app->service;
            $service->namespace = 'api';
            $userinfo = $service->AccessTokenService->getAccessToken($member, 1);

            return ResultHelper::json(200, '修改成功', $userinfo);
        }

        return ResultHelper::json(404, $this->analyErr($member->getFirstErrors()));
    }

    /**
     * 用户信息
     * @return array
     */
    public function actionUserinfo(): array
    {

        $mobile = Yii::$app->request->input('mobile');

        $data = Yii::$app->request->post();

        $member_id = Yii::$app->user->identity->member_id ?? 0;

        if (!empty($mobile)) {
            $userobj = DdMember::findByMobile($data['mobile']);
        } else {
            $userobj = DdMember::findIdentity($member_id);
        }

        $userobj['avatarUrl'] = ImageHelper::tomedia($userobj['avatarUrl'], 'avatar.jpg');
        $userobj['avatar'] = ImageHelper::tomedia($userobj['avatar'], 'avatar.jpg');

        $service = Yii::$app->service;
        $service->namespace = 'api';
        $userinfo = [];
        if ($userobj) {
            $userinfo = $service->AccessTokenService->getAccessToken($userobj, 1);
        }

        return ResultHelper::json(200, '获取成功', ['userinfo' => $userinfo]);
    }

    /**
     * 用户信息
     * @return array|object[]|string[]
     */
    public function actionBindmobile()
    {

        $code = Yii::$app->request->input('code');
        $mobile = Yii::$app->request->input('mobile');
        $sendcode = Yii::$app->cache->get($mobile . '_code');

        if ($code != $sendcode) {
            return ResultHelper::json(401, '验证码错误');
        }

        $member_id = Yii::$app->user->identity->member_id ?? 0;
        $fields['mobile'] = $mobile;
        $res = Yii::$app->service->commonMemberService->editInfo($member_id, $fields);

        if ($res) {
            return ResultHelper::json(200, '绑定手机号成功', []);
        } else {
            return ResultHelper::json(401, '绑定手机号失败');
        }
    }

    /**
     * 修改用户信息
     * @return array
     */
    public function actionEdituserinfo(): array
    {
        $model = new EdituserinfoForm();
        if ($model->load(Yii::$app->request->post(), '')) {
            if (!$model->validate()) {
                $res = ErrorsHelper::getModelError($model);

                return ResultHelper::json(404, $res);
            }
            $userinfo = $model->edituserinfo();
            if ($userinfo) {
                return ResultHelper::json(200, '修改成功', (array)$userinfo);
            }

            return ResultHelper::json(404, $this->analyErr($model->getFirstErrors()));
        } else {
            $res = ErrorsHelper::getModelError($model);

            return ResultHelper::json(404, $res);
        }
    }

    /**
     * 忘记密码
     * @return array
     */
    public function actionForgetpass(): array
    {
        $data = Yii::$app->request->post();
        $mobile = $data['mobile'];
        $password = $data['password'];
        $code = $data['code'];
        $sendcode = Yii::$app->cache->get($mobile . '_code');
        if ($code != $sendcode) {
            return ResultHelper::json(401, '验证码错误');
        }
        $member = DdMember::findByMobile($mobile);
        $res = Yii::$app->service->apiAccessTokenService->forgetpassword($member, $mobile, $password);
        if ($res) {
            // 清除验证码
            Yii::$app->cache->delete($mobile . '_code');

            return ResultHelper::json(200, '修改成功', []);
        } else {
            return ResultHelper::json(401, '修改失败', []);
        }
    }

    /**
     * 发送验证码
     * @return array
     * @throws \Exception
     */
    public function actionSendcode(): array
    {
        $type = Yii::$app->request->input('type');
        if (!in_array($type, ['forgetpass', 'register', 'bindMobile'])) {
            return ResultHelper::json(401, '验证码请求不合法，请传入字段类型type');
        }

        $data = Yii::$app->request->post();
        $mobile = $data['mobile'];
        if (empty($mobile)) {
            return ResultHelper::json(401, '手机号不能为空');
        }

        $where = [];
        $where['mobile'] = $mobile;

        $bloc_id = yii::$app->params['bloc_id'];
        $store_id = yii::$app->params['store_id'];

        // 首先校验手机号是否重复
        $member = ModelsDdMember::find()->where([
            'mobile' => $mobile,
            'bloc_id' => $bloc_id,
            'store_id' => $store_id,
        ])->asArray()->one();

        if ($member && $type == 'register') {
            return ResultHelper::json(401, '手机号已经存在', []);
        }

        $code = random_int(1000, 9999);
        Yii::$app->cache->set((int)$mobile . '_code', $code);

        $usage = '忘记密码验证';

        $res = Yii::$app->service->apiSmsService->send($mobile, $code, $usage);

        return ResultHelper::json(200, '发送成功', $res);
    }

    /**
     * 刷下token
     * @return array
     */
    public function actionRefresh(): array
    {

        $refresh_token = Yii::$app->request->input('refresh_token');

        $user = DdApiAccessToken::find()
            ->where(['refresh_token' => $refresh_token])
            ->one();

        if (!$user) {
            return ResultHelper::json(403, '令牌错误，找不到用户!');
        }

        $access_token = Yii::$app->service->apiAccessTokenService->RefreshToken($user['member_id'], $user['group_id']);

        // findIdentity
        $member = DdMember::findIdentity($user['member_id']);
        $userinfo = Yii::$app->service->apiAccessTokenService->getAccessToken($member, 1);

        return ResultHelper::json(200, '发送成功', $userinfo);
    }

    /**
     * 反馈信息
     * @return array
     */
    public function actionFeedback(): array
    {

        $name = Yii::$app->request->input('name');
        $contact = Yii::$app->request->input('contact');
        $feedback = Yii::$app->request->input('feedback');
        $contacts = new DdWebsiteContact();

        $data = [
            'name' => $name,
            'contact' => $contact,
            'feedback' => $feedback,
        ];

        if ($contacts->load($data, '') && $contacts->save()) {
            return ResultHelper::json(200, '反馈成功', []);
        } else {
            $errors = ErrorsHelper::getModelError($contacts);

            return ResultHelper::json(401, $errors, []);
        }
    }

    public function actionSmsconf(): array
    {
        $sms = Yii::$app->params['conf']['sms'];

        return ResultHelper::json(200, '短信配置获取成功', ['is_login' => $sms['is_login']]);
    }

    /**
     * 留言
     * @return array
     */
    public function actionRelations(): array
    {
        $model = new DdWebsiteContact();
        $data = Yii::$app->request->post();
        if ($model->load($data, '') && $model->save()) {
            return ResultHelper::json(200, '留言成功');
        } else {
            return ResultHelper::json(400, '留言失败');
        }

    }
}
