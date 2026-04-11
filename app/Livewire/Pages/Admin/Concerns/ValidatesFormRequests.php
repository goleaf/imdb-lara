<?php

namespace App\Livewire\Pages\Admin\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;

trait ValidatesFormRequests
{
    use AuthorizesRequests;

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $routeParameters
     * @return array<string, mixed>
     */
    protected function validateWithFormRequest(
        string $requestClass,
        array $data,
        array $routeParameters = [],
    ): array {
        $request = $this->makeFormRequest($requestClass, $data, $routeParameters);

        if (! $request->authorize()) {
            throw new AuthorizationException;
        }

        $validator = validator(
            $request->all(),
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        );

        foreach ($request->after() as $afterValidationHook) {
            $validator->after($afterValidationHook);
        }

        /** @var array<string, mixed> */
        return $validator->validate();
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $routeParameters
     */
    private function makeFormRequest(
        string $requestClass,
        array $data,
        array $routeParameters,
    ): FormRequest {
        [$input, $files] = $this->splitRequestData($data);

        /** @var FormRequest $request */
        $request = $requestClass::create('/', 'POST', $input, [], $files);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => auth()->user());
        $request->setRouteResolver(function () use ($routeParameters): RoutingRoute {
            $route = new RoutingRoute('POST', '/', []);

            foreach ($routeParameters as $parameter => $value) {
                $route->setParameter($parameter, $value);
            }

            return $route;
        });

        return $request;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, UploadedFile>}
     */
    private function splitRequestData(array $data): array
    {
        $input = [];
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                $files[$key] = $value;

                continue;
            }

            $input[$key] = $value;
        }

        return [$input, $files];
    }
}
