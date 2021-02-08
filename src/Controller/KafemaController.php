<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class KafemaController extends AbstractController
{
    /**
     * @Route("/kafema")
     */
    public function index(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:parse',
            'site' => 'kafema',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        return new Response('<pre>' . $content);
    }
}