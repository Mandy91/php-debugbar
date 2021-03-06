<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge\Twig;

use Twig_Template;
use Twig_TemplateInterface;

/**
 * Wraps a Twig_Template to add profiling features
 */
class TraceableTwigTemplate extends Twig_Template implements Twig_TemplateInterface
{
    protected $template;

    /**
     * @param TraceableTwigEnvironment $env
     * @param Twig_Template $template
     */
    public function __construct(TraceableTwigEnvironment $env, Twig_Template $template)
    {
        $this->env = $env;
        $this->template = $template;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->template, $name), $arguments);
    }

    public function doDisplay(array $context, array $blocks = array())
    {
        return $this->template->doDisplay($context, $blocks);
    }

    public function getTemplateName()
    {
        return $this->template->getTemplateName();
    }

    public function display(array $context, array $blocks = array())
    {
        $start = microtime(true);
        $this->template->display($context, $blocks);
        $end = microtime(true);

        if ($timeDataCollector = $this->env->getTimeDataCollector()) {
            $name = sprintf("twig.render(%s)", $this->template->getTemplateName());
            $timeDataCollector->addMeasure($name, $start, $end);
        }

        $this->env->addRenderedTemplate(array(
            'name' => $this->template->getTemplateName(),
            'render_time' => $end - $start
        ));
    }

    public function render(array $context)
    {
        $level = ob_get_level();
        ob_start();
        try {
            $this->display($context);
        } catch (Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

    public static function clearCache()
    {
        Twig_Template::clearCache();
    }
}
