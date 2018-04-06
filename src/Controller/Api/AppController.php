<?php

namespace App\Controller\Api;

use Cake\Controller\Controller;
use App\Controller\Component\RedisManager;
use App\Error\Exception\ApiException;


class AppController extends Controller {
    const TOKEN_PREFIX = 'Bearer ';
 
use \Crud\Controller\ControllerTrait;

    public function initialize() {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Crud.Crud', [
            'actions' => [
                'Crud.Index',
                'Crud.View',
                'Crud.Add',
                'Crud.Edit',
                'Crud.Delete'
            ],
            'listeners' => [
                'Crud.Api',
                'Crud.ApiPagination',
                'Crud.ApiQueryLog'
            ]
        ]);
        $this->loadComponent('Auth', [
            'storage' => 'Memory',
            'authorize' => 'Controller',
            'authenticate' => [
                'Basic' => [
                    'fields' => ['username' => 'email', 'password' => 'password'],
                    'userModel' => 'Users'
                ],
                'Form' => [
                    'fields' => ['username' => 'email', 'password' => 'password'],
                    'userModel' => 'Users',
                    'scope' => ['Users.active' => 1]
                ],
                
                'ADmad/JwtAuth.Jwt' => [
                    'parameter' => 'token',
                    'userModel' => 'Users',
                    'scope' => ['Users.active' => 1],
                    'fields' => [
                        'username' => 'id'
                    ],
                    'queryDatasource' => true
                ]
            ],
            'unauthorizedRedirect' => false,
            'checkAuthIn' => 'Controller.initialize'
        ]);
    }
    
    /**
     * After Authorization is authorized, check the token is exists in Redis or not
     * @param type $user
     */
    // Nothig but checkSession method
    public function isAuthorized($user) {
        $token = $this->getAuthorizationToken();
        $this->validateTokenWithRedis($token);
        return;
    }
    
    /**
     * Method to find 
     * @param string $token
     * @throws ApiException
     * @throws \Cake\Network\Exception\BadRequestException : When token is not exists in redis server
     */
    private function validateTokenWithRedis($token) {
        try {
            $objRedis = new RedisManager();
            $isExist = $objRedis->getKey($token);
            if (!$isExist) {
                throw new \Cake\Network\Exception\BadRequestException('Invalid authorization token passed. Please login again.', 500); 
            }
        } catch (Exception $ex) {
            $this->log($ex->getMessage, LOG_ERR);
            throw new ApiException('Error while retrieveing token from the Redis.', 500); 
        }
    }
    
    /**
     * Description : Method to read authorization token
     * @return type
     */
    protected function getAuthorizationToken () {
        $token  = $this->request->header('Authorization');
        return str_replace(self::TOKEN_PREFIX, '', $token);
    }

}
