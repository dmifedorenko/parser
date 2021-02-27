<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    private KernelInterface $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    /**
     * @Route("/parser/{site}")
     */
    public function parse(string $site, KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:parse',
            'site' => $site,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        return new Response('<pre>' . $content);
    }

    /**
     * @Route("/parser")
     */
    public function index(): Response
    {
        $dir = $this->appKernel->getProjectDir() . '/src/Service/Site';

        $sites = [];
        foreach (glob($dir . '/*.php') as $path) {
            $name = ucfirst(basename($path, '.php'));
            if (!in_array($name, ['SiteParser', 'SiteParserInterface'])) {
                $sites[$path] = $name;
            }
        }

        return $this->render('parser/list.html.twig', ['sites' => $sites]);
    }
}
