<?php

namespace QuantaQuirk\Foundation\Testing\Concerns;

use QuantaQuirk\Support\Facades\View as ViewFacade;
use QuantaQuirk\Support\MessageBag;
use QuantaQuirk\Support\ViewErrorBag;
use QuantaQuirk\Testing\TestComponent;
use QuantaQuirk\Testing\TestView;
use QuantaQuirk\View\View;

trait InteractsWithViews
{
    /**
     * Create a new TestView from the given view.
     *
     * @param  string  $view
     * @param  \QuantaQuirk\Contracts\Support\Arrayable|array  $data
     * @return \QuantaQuirk\Testing\TestView
     */
    protected function view(string $view, $data = [])
    {
        return new TestView(view($view, $data));
    }

    /**
     * Render the contents of the given Blade template string.
     *
     * @param  string  $template
     * @param  \QuantaQuirk\Contracts\Support\Arrayable|array  $data
     * @return \QuantaQuirk\Testing\TestView
     */
    protected function blade(string $template, $data = [])
    {
        $tempDirectory = sys_get_temp_dir();

        if (! in_array($tempDirectory, ViewFacade::getFinder()->getPaths())) {
            ViewFacade::addLocation(sys_get_temp_dir());
        }

        $tempFileInfo = pathinfo(tempnam($tempDirectory, 'quantaquirk-blade'));

        $tempFile = $tempFileInfo['dirname'].'/'.$tempFileInfo['filename'].'.blade.php';

        file_put_contents($tempFile, $template);

        return new TestView(view($tempFileInfo['filename'], $data));
    }

    /**
     * Render the given view component.
     *
     * @param  string  $componentClass
     * @param  \QuantaQuirk\Contracts\Support\Arrayable|array  $data
     * @return \QuantaQuirk\Testing\TestComponent
     */
    protected function component(string $componentClass, $data = [])
    {
        $component = $this->app->make($componentClass, $data);

        $view = value($component->resolveView(), $data);

        $view = $view instanceof View
            ? $view->with($component->data())
            : view($view, $component->data());

        return new TestComponent($component, $view);
    }

    /**
     * Populate the shared view error bag with the given errors.
     *
     * @param  array  $errors
     * @param  string  $key
     * @return $this
     */
    protected function withViewErrors(array $errors, $key = 'default')
    {
        ViewFacade::share('errors', (new ViewErrorBag)->put($key, new MessageBag($errors)));

        return $this;
    }
}
