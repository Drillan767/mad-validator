<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RedditHandler;

    class RedditController extends AbstractController
    {

        private RedditHandler $reddit_handler;

        public function __construct(RedditHandler $reddit_handler)
        {
            $this->reddit_handler = $reddit_handler;
        }

        /**
         * @Route("/", name="reddit_main")
         * @return Response
         */
        public function index () : Response
        {
            $datas = $this->reddit_handler->loadSubreddit();
            return $this->render('reddit/index.html.twig');
        }

    }

?>
