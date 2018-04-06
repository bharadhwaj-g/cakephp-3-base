<?php

namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use App\Controller\Component\RedisManager;
use App\Error\Exception\ApiException;

class UsersController extends AppController {

    const IN_ACTIVE = 0;
    const ACTIVE = 1;
    const TOKEN_EXPIRED = '604800';
    const ADMIN_ROLE = 'admin';

    private $restrictedActions = ['delete', 'index'];

    public function initialize() {
        parent::initialize();
        $this->Auth->allow(['add', 'token', 'index']); // TODO - Have to remove Index action
    }

    /**
     * 
     *  @SWG\Swagger(
     *     swagger="2.0",
     *     basePath="/api",
     *     @SWG\Info(title="Cake base", version="0.1")
     *     
     * )
     * 
     * 
     *  
     * @SWG\SecurityScheme(
     *      securityDefinition="Basic",
     *      type="apiKey",
     *      in="header",
     *      name="Authorization"
     *  )
     * 
     * @SWG\SecurityScheme(
     *      securityDefinition="Jwt",
     *      type="apiKey",
     *      in="header",
     *      name="Authorization"
     *  )
     * @SWG\SecurityScheme(
     *      securityDefinition="Bearer",
     *      type="apiKey",
     *      in="header",
     *      name="Authorization"
     *  )
     * 
     */
    
    /**
     * @SWG\Put(
     *     path="/users/{ID}",
     *     description="Update user's info",
     *     tags={"Users"},
     *     operationId="edit",
     *     produces={"application/json", "application/xml"},
     *     @SWG\Parameter(
     *         name="ID",
     *         in="path",
     *         description="The required user id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                property="success",
     *                type="boolean",
     *                default=true
     *              ),
     *              @SWG\Property(
     *                property="data",
     *                type="array",
     *                @SWG\Items(ref="#/definitions/Users")
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Failed",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="User not found",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     )
     * )
     */
    /**
     * @SWG\Get(
     *     path="/users/{ID}",
     *     description="Retrieves a paginated list of countries",
     *     tags={"Users"},
     *     operationId="view",
     *     produces={"application/json", "application/xml"},
     *     @SWG\Parameter(
     *         name="Id",
     *         in="path",
     *         description="Number of the page",
     *         required=false,
     *         type="integer",
     *         defaultValue=1 
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Returns a list of countries present in our system.",
     *         @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                property="success",
     *                type="boolean",
     *                default=true
     *              ),
     *              @SWG\Property(
     *                property="data",
     *                type="array",
     *                @SWG\Items(ref="")
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Failed",
     *         @SWG\Schema(ref="")
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="Page out of range",
     *         @SWG\Schema(ref="")
     *     )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/users",
     *     description="Retrieves a paginated list of countries",
     *     tags={"Users"},
     *     operationId="index",
     *     produces={"application/json", "application/xml"},
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Number of the page",
     *         required=false,
     *         type="integer",
     *         defaultValue=1 
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Returns a list of countries present in our system.",
     *         @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                property="success",
     *                type="boolean",
     *                default=true
     *              ),
     *              @SWG\Property(
     *                property="data",
     *                type="array",
     *                @SWG\Items(ref="#/definitions/Users")
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Failed",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *          response=404,
     *          description="Page out of range",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     )
     * )
     */
    public function index() {
        $this->Crud->on('beforePaginate', function(Event $event) {
            if (isset($this->request->params["user_id"])) {
                $event->subject->query->where(["user_id" => $this->request->params["user_id"]]);
            }
            $event->subject->query->where(["active" => self::ACTIVE]);
        });

        $this->Crud->execute();
    }

    /**
     * @SWG\Post(
     *     path="/users",
     *     description="Add new user",
     *     tags={"Users"},
     *     operationId="add",
     *     produces={"application/json", "application/xml"},
     *    @SWG\Parameter(
     *         name="data",
     *         in="body",
     *         description="User object",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Users")
     *     ),
     *     @SWG\Response(
     *         response=201,
     *         description="User was successfully added",
     *         @SWG\Schema(ref="#/definitions/GenericAddSuccess")
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Missing parameters. Will provide a list of missing parameters in the request",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *         response=409,
     *         description="Rules Error. Happens when data is in the correct format but are wrong according to application rules",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *         response=412,
     *         description="Validation Error. Happens when the data we receive is not in the proper format",
     *         @SWG\Schema(ref="#/definitions/GenericValidationError")
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="unexpected error",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     )
     * )
     */

    /**
     *  Description : Method to register user and send token in the response object
     * @return type
     */
    public function add() {

        // Set the active = 1 // Actually its not needed, why because the database default value is 1 for the active column
        $this->Crud->on('beforeSave', function(Event $event) {
            $event->subject->entity->active = self::ACTIVE;
        });

        // Send the token after save 
        $this->Crud->on('afterSave', function(Event $event) {
            if ($event->subject->created) {
                $this->set('data', [
                    'id' => $event->subject->entity->id,
                    'token' => JWT::encode(
                            [
                        'sub' => $event->subject->entity->id,
                        'exp' => time() + self::TOKEN_EXPIRED
                            ], Security::salt())
                ]);
                $this->Crud->action()->config('serialize.data', 'data');
            }
        });
        
        $result = $this->Crud->execute();
        return $result;
    }

    /**
     * @SWG\Delete(
     *     path="/users/{ID}}",
     *     description="Method to delete the user, this will only soft delete i.e set user as inactive",
     *     tags={"Users"},
     *     operationId="delete",
     *     produces={"application/json", "application/xml"},
     *     @SWG\Parameter(
     *         name="ID",
     *         in="path",
     *         description="Required user ID",
     *         required=true,
     *         type="integer",
     *     ),
     *       @SWG\Response(
     *         response=200,
     *         description="Returns authentication token.",
     *         @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                property="success",
     *                type="boolean",
     *                default=true
     *              ),
     *              @SWG\Property(
     *                property="data",
     *                type="object",
     *                @SWG\Items(ref="")
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Requested user does not exists",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="unexpected error",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     )
     * )
     */

    /**
     * Description : Method to set the user as inactive
     * @return type
     */
    public function delete() {
        $this->Crud->on('beforeDelete', function(\Cake\Event\Event $event) {
            $event->stopPropagation();
            $quote = $this->Users->get($event->subject->entity->id);
            $quote->active = self::IN_ACTIVE;

            if ($this->Users->save($quote)) {
                $this->set("success", true);
            } else {
                $this->set("success", false);
            }
            $this->set("_serialize", ["success"]);
        });
        return $this->Crud->execute();
    }

    /**
     * For now remove the token from redis
     */
    public function logout() {
        $token = $this->getAuthorizationToken();

        // Delete the token in Redis 
        try {
            $objRedis = new RedisManager();
            $isDelete = $objRedis->deleteKey($token);
            if ($isDelete) {
                $this->set("success", true);
            } else {
                $this->set("success", false);
            }
            $this->set("_serialize", ["success"]);
        } catch (Exception $ex) {
            $this->log($ex->getMessage, LOG_ERR);
            throw new ApiException('Error while saving token in the Redis.', 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/users/token",
     *     description="Get auth token",
     *     tags={"Users"},
     *     operationId="token",
     *     produces={"application/json", "application/xml"},
     *    @SWG\Parameter(
     *         name="data",
     *         in="body",
     *         description="User object",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Users/token")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="User was successfully added",
     *         @SWG\Schema(ref="#/definitions/GenericAddSuccess")
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Missing parameters. Will provide a list of missing parameters in the request",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Invalid username or password",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="unexpected error",
     *         @SWG\Schema(ref="#/definitions/GenericError")
     *     )
     * )
     */

    /**
     * Description : Method to send Authorization Token  ( simply we can call this as login method)
     * @throws UnauthorizedException
     */
    // nothing but login method
    public function token() {
        $user = $this->Auth->identify();
        if (!$user) {
            throw new UnauthorizedException('Invalid username or password');
        }

        $token = JWT::encode([
                    'sub' => $user['id'],
                    'exp' => time() + self::TOKEN_EXPIRED
                        ], Security::salt());

        // Save token in redis 
        $this->setTokenInRedis($token);

        $this->set([
            'success' => true,
            'data' => [
                'token' => $token
            ],
            '_serialize' => ['success', 'data']
        ]);
    }

    private function setTokenInRedis($token) {
        try {
            $objRedis = new RedisManager();
            $objRedis->setKey($token);
            return TRUE;
        } catch (Exception $ex) {
            $this->log($ex->getMessage, LOG_ERR);
            throw new ApiException('Error while saving token in the Redis.', 500);
        }
    }

    /**
     * Method to verify the user authorization to access the actions
     * @param type $user
     * @return boolean : Returns true if authorized to access otherwise false
     */
    public function isAuthorized($user) {
        parent::isAuthorized($user);
        // Only admin user can delete user
        if (in_array($this->request->action, $this->restrictedActions)) {
            if (isset($user['role']) && $user['role'] === self::ADMIN_ROLE) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Method to verify the session is expired or not.
     * @param Null
     * @return boolean : Returns true if authorized to access otherwise false
     */
    public function checkSession() {
        $this->isAuthorized();
    }

}
