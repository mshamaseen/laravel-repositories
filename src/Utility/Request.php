<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shamaseen
 * Date: 09/10/18
 * Time: 01:01 م.
 */

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Response;
use Symfony\Component\HttpFoundation\Response as jsonResponse;

/**
 * Class BaseRequests.
 */
class Request extends FormRequest
{
    /**
     * Define all the global rules for this request here.
     *
     * @var array
     */
    protected array $rules = [

    ];

    /**
     * @param Validator $validator
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $errors = (new ValidationException($validator))->errors();
            throw new HttpResponseException(Response::json(
                [
                    'success' => false,
                    'errors' => $errors
                ],
                jsonResponse::HTTP_UNPROCESSABLE_ENTITY
            ));
        }
        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        $rules = [] + $this->rules;

        $method = $this->method();

        if (method_exists($this, $method . "MethodRules")) {
            $rules += $this->{$method . "MethodRules"}();
        }

        $route = $this->route();
        // make sure that it is accessed by a route and not command or job
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                $lastPart = last(explode(".", $routeName));
                $methodName = $lastPart . "RouteRules";
                if (method_exists($this, $methodName)) {
                    $rules += $this->{$methodName}();
                }
            }
        }

        return $rules;
    }

    /**
     * Define all the rules for every get HTTP method on this request.
     *
     * @return array
     */
    public function getMethodRules(): array
    {
        return [];
    }

    /**
     * Define all the rules for every post HTTP method on this request.
     *
     * @return array
     */
    public function postMethodRules(): array
    {
        return [];
    }

    /**
     * Define all the rules for every patch HTTP method on this request.
     *
     * @return array
     */
    public function patchMethodRules(): array
    {
        return [];
    }

    /**
     * Define all the rules for every put HTTP method on this request.
     *
     * @return array
     */
    public function putMethodRules(): array
    {
        return [];
    }

    /**
     * Define all the rules for every delete HTTP method on this request.
     *
     * @return array
     */
    public function deleteMethodRules(): array
    {
        return [];
    }
}
