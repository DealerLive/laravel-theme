<?php namespace DealerLive\LaravelTheme;

class Theme
{
    private $theme;

    protected $finder;

    protected $options = array(
        'public_dirname' => 'themes',
        'views_path' => null,
    );

    public function __construct($finder)
    {
        $this->finder = $finder;
    }

    /**
     * init theme
     */
    public function init($name, array $options = array())
    {
        $this->theme = $name;
        $this->options = array_merge($this->options, $options);

        $this->updateFinder();
    }

    /**
     * Get current theme name.
     */
    public function name()
    {
        return $this->theme;
    }

    protected function updateFinder()
    {
        // add theme views path
        \View::addLocation($this->viewPath());
        //$this->finder->prependLocation($this->viewPath());
    }

    /**
     * Helper method to generate asset url based on current theme path.
     *
     * @param  string  $path  The asset path relative to theme path.
     * @return string  The full url for the asset.
     */
    public function asset($path = '')
    {
        return asset($this->options['public_dirname'] . '/' . $this->name() . '/' . trim($path, '/'));
    }

    /**
     * Get current theme view path.
     */
    public function viewPath()
    {
        return is_null($this->options['views_path'])
            ? public_path($this->options['public_dirname'] . '/' . $this->name() . '/views')
            : rtrim($this->options['views_path'], '/') . '/' . $this->name();
    }

    /**
     * Get the fully qualified path to the theme public directory.
     */
    public function publicPath($path = '')
    {
        return public_path($this->options['public_dirname'] . '/' . $this->name()
                    . (empty($path) ? '' : '/' . rtrim($path)));
    }
}
