<?php namespace DealerLive\LaravelTheme;

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
        // override core view finder
        $this->app['view.finder'] = $this->app->share(function($app) {
            $paths = $app['config']['view.paths'];

            return new FileViewFinder($app['files'], $paths);
        });

        $this->app['theme'] = $this->app->share(function($app) {
            $theme = new Theme($app['view.finder']);
            return $theme;
        });

       \Event::listen('reporting.goals.triggers', function(){
            return array(
                'social_facebook' => 'Facebook social icon click',
                'social_twitter' => 'Twitter social icon click',
                'social_google_plus' => 'Google Plus social icon click',
                'social_youtube' => 'Youtube social icon click'
                );
        });

       \Event::listen('cms.edit-mode', function($data)
        {   
            if(\Auth::check() && !$data->isHidden())
            {
                \Theme::addEditButton('<a class="btn btn-default btn-sm" href="'.$data->getURL().'" style="position: fixed; bottom: '.(\Theme::getEdittNavigationCount()+20).'px; left: 20px; opacity: 0.7">'.$data->getName().'</a>');
                \Theme::increaseEditNavigationCount(35);
            }
        });

       \View::addNamespace('Theme', __DIR__.'/../../views/');
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
