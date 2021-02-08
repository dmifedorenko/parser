<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/services.yaml')) {
            $container->import('../config/services.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        } elseif (is_file($path = \dirname(__DIR__).'/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import('../config/routes.yaml');
        } elseif (is_file($path = \dirname(__DIR__).'/config/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    public static function p0(): void
    {
        $cloner = new VarCloner();
        $cli = PHP_SAPI === 'cli';
        if (!$cli) {
            $dumper = new HtmlDumper();
            $dumper->setDisplayOptions([
                'maxDepth' => 100,
                'maxStringLength' => 160,
            ]);
            $dumper->setDumpBoundaries('<pre class=sf-dump id=%s>', '</pre>');

            static $headerIsDumped;

            if ($headerIsDumped) {
                $dumper->setDumpHeader('');
            } else {
                $headerIsDumped = true;
                $dumper->setDumpHeader('<style>
                pre.sf-dump { display: block; white-space: pre; padding: 5px; }
                pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; }
                pre.sf-dump span { display: inline; }
                pre.sf-dump .sf-dump-compact { display: none; }
                pre.sf-dump abbr { text-decoration: none; border: none; cursor: help; }
                pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; }
                pre.sf-dump .sf-dump-ellipsis { display: inline-block; text-overflow: ellipsis; max-width: 5em; white-space: nowrap; overflow: hidden; vertical-align: top; }
                pre.sf-dump .sf-dump-ellipsis+.sf-dump-ellipsis { max-width: none; } pre.sf-dump code { display:inline; padding:0; background:none; }
                .sf-dump-str-collapse .sf-dump-str-collapse { display: none; }
                .sf-dump-str-expand .sf-dump-str-expand { display: none; }
                .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; }
                .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; }
                pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#29282c; color:#FF8400; line-height: 2em; font: 15px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: normal}
                pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}
                pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}
                pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}
                pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}</style>');
            }
            $dumper->setDisplayOptions(['maxStringLength' => 0]);
        } else {
            $dumper = new CliDumper();
        }

        $dumper->setCharset('Utf-8');
        foreach (\func_get_args() as $var) {
            $dumper->dump($cloner->cloneVar($var));
        }
    }

    public static function pr(): void
    {
        @header('Content-type: text/html; charset=Utf-8', true);

        if (!headers_sent()) {
            header('Content-type: text/html; charset=Utf-8', true);
            header('Expires: Tue, 01 Jan 2000 00:00:00 GMT', true);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true);
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
            header('Cache-Control: post-check=0, pre-check=0', true);
            header('Pragma: no-cache', true);
        }

        $p = \func_get_args();
        \call_user_func_array(['self', 'p0'], $p);
        exit;
    }
}
