<?php

namespace DealerLive\LaravelTheme;

use \DealerLive\Config\Helper;

class Theme
{

    protected $editModeButtons;
    protected $editNavigationCount;

    public function increaseEditNavigationCount($amount)
    {
        $this->editNavigationCount += $amount;
    }

    public function getEdittNavigationCount()
    {
        return $this->editNavigationCount;
    }

    private static function layoutDir()
    {
        return __DIR__.'/../../views/';
    }

    public function addEditButton($string)
    {
        $this->editModeButtons .= $string;
    }

    public function getEditButtons()
    {
        return $this->editModeButtons;

    }

    private $theme;

    protected $finder;
    protected $options = array(
        'public_dirname' => 'themes',
        'views_path' => null,
    );

    public function __construct($finder)
    {
        $this->finder = $finder;
        $this->editNavigationCount = 0;
        $this->editModeButtons = '';

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
        $file = $this->options['public_dirname'].'/'.$this->name().'/'.trim($path, '/');
        if(file_exists($file))
            return asset($this->options['public_dirname'] . '/' . $this->name() . '/' . trim($path, '/'));
        return asset($this->options['public_dirname'].'/.shared/'.trim($path, '/'));
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

    public static function getLanguageToggle()
    {
        if(!class_exists('\DealerLive\Cms\CmsmlServiceProvider'))
            return null;

        $markup = null;
        if(\App::getLocale() == "fr")
            $markup = '<a href="'.\URL::route('language', 'en').'" class="lang-toggle">View In English</a>';
        else
            $markup = '<a href="'.\URL::route('language', 'fr').'" class="lang-toggle">Voir en français</a>';
        
        return $markup;
    }

    public static function navigation()
    {
        
        echo \Theme::getEditButtons();

        if(file_exists(public_path().'/themes/yields/'.\App::getLocale().'_nav.blade.php'))
            include (public_path().'/themes/yields/'.\App::getLocale().'_nav.blade.php');
        elseif(file_exists(public_path().'/themes/yields/nav.blade.php'))
            include (public_path().'/themes/yields/nav.blade.php');
    }

    public static function mobileNavigation()
    {
        if(file_exists(public_path().'/themes/yields/'.\App::getLocale().'_mobile_nav.blade.php'))
            include (public_path().'/themes/yields/'.\App::getLocale().'_mobile_nav.blade.php');
        elseif(file_exists(public_path().'/themes/yields/mobile_nav.blade.php'))
            include (public_path().'/themes/yields/mobile_nav.blade.php');
    }

    public static function social($specific = null)
    {
        $result = null;
        $social = array(
            'facebook' => 'facebook.png',
            'twitter' => 'twitter.png',
            'google_plus' => 'google.png',
            'youtube' => 'youtube.png',
            'pinterest' => 'pinterest.png',
            'instagram' => 'instagram.png'
        );

        foreach($social as $socialName => $icon)
            if(((!is_null($specific) && $specific == $socialName) || is_null($specific)) && Helper::check($socialName))
                $result .= '<a target="_blank" href="'.Helper::check($socialName).'" data-goal="social_'.$socialName.'"><img src="'.\Theme::asset('img/icons/'.$icon).'"></a>';
        
        return $result;
    }

    public static function content($blade, $params = array())
    {
        if(\View::exists('Theme::content.'.$blade))
        {
            try
            {
                $markup = \View::make('Theme::content.'.$blade, compact('params'))->render();
            }
            catch(\Exception $ex)
            {
                return null;
            }
            
            return $markup;
        }
    }

    public function isResponsive()
    {
        if(Helper::check('theme_responsive') == 'true')
            return true;
        else
            return false;
        
    }

    public function check($property)
    {
        if(!file_exists($this->options['public_dirname'].'/'.$this->name().'/theme.json'))
            return null;

        $json = json_decode(file_get_contents($this->options['public_dirname'].'/'.$this->name().'/theme.json'));
       
        if(property_exists($json, $property))
            return $json->$property;

        return null;
    }

    public function getLayouts()
    {
        $path = Theme::viewPath().'/layouts';

        $results = scandir($path);
        $blacklist = array('.', '..', '.DS_Store');
        $layoutFiles = array_diff($results, $blacklist);

        $json = file_get_contents(Theme::publicPath().'/../layouts.json');
        $layoutData = json_decode($json);
        $layouts = array();
        foreach($layoutData as $l)
        {
            if(self::isvalidLayout($l, $layoutFiles))
                $layouts[] = $l;
        }

        return $layouts;
    }

    public static function isValidLayout(\stdClass $layout, $fileList)
    {
        foreach($fileList as $file)
        {
            if($file == $layout->file)
                return true;
        }

        return false;
    }

    public static function layoutViewName($layoutFile)
    {
        return str_replace('.blade.php', '', $layoutFile);
    }
}
