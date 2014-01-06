<?php namespace Bigecko\LaravelTheme;

use Illuminate\Support\ServiceProvider;

class LaravelThemeServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->package('bigecko/laravel-theme');

        // override core view finder
        $this->app['view.finder'] = $this->app->share(function($app) {
            $paths = $app['config']['view.paths'];

            return new FileViewFinder($app['files'], $paths);
        });

        $this->app['theme'] = $this->app->share(function($app) {
            $theme = new Theme($app['view.finder'], $app['url']);
            return $theme;
        });
    }

    public function boot()
    {
        $this->app->make('theme')->setTheme($this->app['config']->get('app.theme', 'default'));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
