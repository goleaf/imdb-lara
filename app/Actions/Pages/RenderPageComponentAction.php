<?php

namespace App\Actions\Pages;

use Illuminate\Http\Response;
use Livewire\Component;
use Livewire\Features\SupportPageComponents\PageComponentConfig;
use Livewire\Features\SupportPageComponents\SupportPageComponents;
use ReflectionMethod;

class RenderPageComponentAction
{
    /**
     * @param  class-string<Component>  $componentClass
     * @param  array<string, mixed>  $mountParameters
     */
    public function handle(string $componentClass, array $mountParameters = []): Response
    {
        /** @var Component $component */
        $component = app($componentClass);

        if (method_exists($component, 'mount')) {
            $this->callMount($component, $mountParameters);
        }

        $view = app()->call([$component, 'render']);
        $layoutConfig = $view->layoutConfig ?? new PageComponentConfig;

        $layoutConfig->normalizeViewNameAndParamsForBladeComponents();

        $response = response(
            SupportPageComponents::renderContentsIntoLayout($view->render(), $layoutConfig)
        );

        if (is_callable($layoutConfig->response)) {
            call_user_func($layoutConfig->response, $response);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $mountParameters
     */
    private function callMount(Component $component, array $mountParameters): void
    {
        $reflection = new ReflectionMethod($component, 'mount');
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $mountParameters)) {
                $arguments[] = $mountParameters[$parameter->getName()];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;

                continue;
            }

            $arguments[] = app()->make($parameter->getType()?->getName() ?? throw new \RuntimeException(
                'Unable to resolve mount parameter ['.$parameter->getName().'] for ['.$component::class.'].',
            ));
        }

        $component->mount(...$arguments);
    }
}
