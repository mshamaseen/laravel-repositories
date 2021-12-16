<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shamaseen
 * Date: 09/10/18
 * Time: 01:01 م.
 */

namespace Shamaseen\Repository\Utility;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class BaseRequests.
 */
class Request extends FormRequest
{
    public AbstractRepository $repository;

    public function __construct(AbstractRepository $repository, array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->repository = $repository;
    }

    /**
     * Define all the global rules for this request here.
     *
     * @var array
     */
    protected array $rules = [

    ];

    public function rules(): array
    {
        $rules = [] + $this->rules;

        $method = $this->method();

        $httpValidatorName = 'Http'.ucfirst(Str::lower($method)).'Rules';

        if (method_exists($this, $httpValidatorName)) {
            $rules += $this->{$httpValidatorName}();
        }

        $route = $this->route();
        // make sure that it is accessed by a route and not command or job
        if ($route) {
            $routeName = $route->action['uses'];
            if ($routeName) {
                $lastPart = last(explode("@", $routeName));
                $validatorName = $lastPart."Rules";

                $reflection = new \ReflectionClass($this);
                // make sure the method is exists in the user defined request
                if ($reflection->hasMethod($validatorName) && $reflection->getMethod($validatorName)->class === $reflection->getName()) {
                    $rules += $this->{$validatorName}();
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
